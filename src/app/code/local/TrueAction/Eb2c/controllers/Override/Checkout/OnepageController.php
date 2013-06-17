<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
require_once('Mage/Checkout/controllers/OnepageController.php');
class TrueAction_Eb2c_Override_Checkout_OnepageController extends Mage_Checkout_OnepageController
{
	/**
	 * Get Allocation instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Allocation
	 */
	protected function _getAllocation()
	{
		if (!$this->_allocation) {
			$this->_allocation = Mage::getModel('eb2cinventory/allocation');
		}
		return $this->_allocation;
	}

	/**
	 * Create order action - overriding for eb2c allocation
	 */
	public function saveOrderAction()
	{
		if (!$this->_validateFormKey()) {
			$this->_redirect('*/*');
			return;
		}

		if ($this->_expireAjax()) {
			return;
		}

		$result = array();
		try {
			$requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
			if ($requiredAgreements) {
				$postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
				$diff = array_diff($requiredAgreements, $postedAgreements);
				if ($diff) {
					$result['success'] = false;
					$result['error'] = true;
					$result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
					return;
				}
			}

			// Begin eb2c inventory allocation

			// get the quote
			$quote = $this->getOnepage()->getQuote();

			// flag for success/un-success allocation
			$isAllocated = true;

			// generate request and send request to eb2c allocation
			if ($allocationResponseMessage = $this->_getAllocation()->allocateQuoteItems($quote)) {
				// got a valid response from eb2c, then go ahead and update the quote with the eb2c information
				$allocatedErr = $this->_getAllocation()->processAllocation($quote, $allocationResponseMessage);

				// Got an allocation failure
				if (!empty($allocatedErr)) {
					$isAllocated = false;
					foreach ($allocatedErr as $error) {
						Mage::getSingleton('checkout/session')->addError($this->__($error));
					}
				}
			}

			if (!$isAllocated) {
				$result['success'] = false;
				$result['error'] = true;
				$result['error_messages'] = $this->__('Inventory allocation Error');
				$result['redirect'] = Mage::getUrl('checkout/cart');
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
				return;
			}
			// end eb2c inventory allocation

			$data = $this->getRequest()->getPost('payment', array());
			if ($data) {
				$data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
					| Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
					| Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
					| Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
					| Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
				$this->getOnepage()->getQuote()->getPayment()->importData($data);
			}

			$this->getOnepage()->saveOrder();

			$redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
			$result['success'] = true;
			$result['error']   = false;
		} catch (Mage_Payment_Model_Info_Exception $e) {
			$message = $e->getMessage();
			if (!empty($message)) {
				$result['error_messages'] = $message;
			}
			$result['goto_section'] = 'payment';
			$result['update_section'] = array(
				'name' => 'payment-method',
				'html' => $this->_getPaymentMethodsHtml()
			);
		} catch (Mage_Core_Exception $e) {
			Mage::logException($e);
			Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
			$result['success'] = false;
			$result['error'] = true;
			$result['error_messages'] = $e->getMessage();

			$gotoSection = $this->getOnepage()->getCheckout()->getGotoSection();
			if ($gotoSection) {
				$result['goto_section'] = $gotoSection;
				$this->getOnepage()->getCheckout()->setGotoSection(null);
			}
			$updateSection = $this->getOnepage()->getCheckout()->getUpdateSection();
			if ($updateSection) {
				if (isset($this->_sectionUpdateFunctions[$updateSection])) {
					$updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
					$result['update_section'] = array(
						'name' => $updateSection,
						'html' => $this->$updateSectionFunction()
					);
				}
				$this->getOnepage()->getCheckout()->setUpdateSection(null);
			}
		} catch (Exception $e) {
			Mage::logException($e);
			Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
			$result['success']  = false;
			$result['error']    = true;
			$result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
		}
		$this->getOnepage()->getQuote()->save();
		/**
		 * when there is redirect to third party, we don't want to save order yet.
		 * we will save the order in return action.
		 */
		if (isset($redirectUrl)) {
			$result['redirect'] = $redirectUrl;
		}

		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}
}
