<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once 'Enterprise/GiftCardAccount/controllers/CartController.php';

/**
 * Class EbayEnterprise_Eb2cPayment_Overrides_GiftCardAccount_CartController
 *
 * Convert Magento Enterprise Gift Card frontend into eBay Enterprise Retail Order Management Stored Value Card frontend
 * by overriding addAction and quickCheckAction.
 *
 * @see EbayEnterprise_Eb2cPayment_Overrides_Model_GiftCardAccount
 */
class EbayEnterprise_Eb2cPayment_Overrides_GiftCardAccount_CartController extends Enterprise_GiftCardAccount_CartController
{
	const EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_INVALID_PAN = 'EbayEnterprise_Eb2cPayment_GiftCard_Invalid_Pan';
	const EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_INVALID_PIN = 'EbayEnterprise_Eb2cPayment_GiftCard_Invalid_Pin';
	/**
	 * Maximal gift card pan length according to database table definitions (longer codes are truncated)
	 */
	const GIFT_CARD_PAN_MAX_LENGTH = 22;
	/**
	 * Maximal gift card pin length according to database table definitions (longer codes are truncated)
	 */
	const GIFT_CARD_PIN_MAX_LENGTH = 8;
	/**
	 * Overriding Enterprise add gift card to cart controller
	 * Add Gift Card to current quote
	 */
	public function addAction()
	{
		$data = $this->getRequest()->getPost();
		if (isset($data['giftcard_code']) && isset($data['giftcard_pin'])) {
			$code = $data['giftcard_code']; // interpreting code as eb2c pan
			$pin = $data['giftcard_pin']; // getting pin data from user input
			try {
				if (strlen($code) > static::GIFT_CARD_PAN_MAX_LENGTH) {
					throw Mage::exception('Mage_Core', Mage::helper('enterprise_giftcardaccount')->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_INVALID_PAN));
				}

				if (strlen($pin) > static::GIFT_CARD_PIN_MAX_LENGTH) {
					throw Mage::exception('Mage_Core', Mage::helper('enterprise_giftcardaccount')->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_INVALID_PIN));
				}

				// override this method to make eb2c stored value balance check request for actual valid gift card
				$giftCardAccount = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByCode($code);
				Mage::helper('eb2cpayment/giftcard')->synchStoreValue($code, $pin, $giftCardAccount);
				$giftCardAccount->addToCart();
				Mage::getSingleton('checkout/session')->addSuccess(
					$this->__('Gift Card "%s" was added.', Mage::helper('core')->escapeHtml($code))
				);
			} catch (Mage_Core_Exception $e) {
				Mage::dispatchEvent('enterprise_giftcardaccount_add', array('status' => 'fail', 'code' => $code));
				Mage::getSingleton('checkout/session')->addError(
					$e->getMessage()
				);
			} catch (Exception $e) {
				Mage::getSingleton('checkout/session')->addException($e, $this->__('Cannot apply gift card.'));
			}
		}
		$this->_redirect('checkout/cart');
	}

	/**
	 * Overriding gift card quick check
	 * Check a gift card account availability
	 */
	public function quickCheckAction()
	{
		$code = $this->getRequest()->getParam('giftcard_code', '');
		$pin = $this->getRequest()->getParam('giftcard_pin', '');
		$card = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
			->loadByCode($code);
		Mage::helper('eb2cpayment/giftcard')->synchStoreValue($code, $pin, $card);
		Mage::register('current_giftcardaccount', $card);
		try {
			$card->isValid(true, true, true, false);
		} catch (Mage_Core_Exception $e) {
			$card->unsetData();
		}

		$this->loadLayout();
		$this->renderLayout();
	}
}
