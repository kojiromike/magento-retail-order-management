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

class EbayEnterprise_Eb2cPayment_Model_Observer
{
	const EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_REMOVED = 'EbayEnterprise_Eb2cPayment_GiftCard_Removed';
	const EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_WRONG_ACCOUNT = 'EbayEnterprise_Eb2cPayment_GiftCard_Wrong_Account';
	const EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_NOT_REDEEMABLE = 'EbayEnterprise_Eb2cPayment_GiftCard_Not_Redeemable';

	const REDEEM_RESPONSE_CODE_FAIL = 'FAIL';

	/** @var EbayEnterprise_Eb2cPayment_Helper_Data $_paymentHelper */
	protected $_paymentHelper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data $_coreHelper */
	protected $_coreHelper;
	/** @var Mage_Core_Helper_Data $_mageCoreHelper */
	protected $_mageCoreHelper;
	/** @var Mage_Checkout_Model_Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;
	/**
	 * Inject dependent systems or create/get default instances
	 * @param mixed[] $params
	 */
	public function __construct(array $params=array())
	{
		$this->_paymentHelper = isset($params['payment_helper']) ? $params['payment_helper'] : Mage::helper('eb2cpayment');
		$this->_coreHelper = isset($params['core_helper']) ? $params['core_helper'] : Mage::helper('eb2ccore');
		$this->_mageCoreHelper = isset($params['mage_core_helper']) ? $params['mage_core_helper'] : Mage::helper('core');
		$this->_checkoutSession = isset($params['checkout_session']) ? $params['checkout_session'] : Mage::getSingleton('checkout/session');
		$this->_log = isset($params['log']) ? $params['log'] : Mage::helper('ebayenterprise_magelog');
	}
	/**
	 * Redeem any gift card when 'eb2c_redeem_giftcard' event is dispatched
	 *
	 * @param Varien_Event_Observer $observer
	 * @throws Mage_Core_Exception
	 */
	public function redeemGiftCard($observer)
	{
		$helper = $this->_paymentHelper;
		$order = $observer->getEvent()->getOrder();
		$giftCard = unserialize($order->getGiftCards());
		if ($giftCard) {
			foreach ($giftCard as $idx => $card) {
				if (isset($card['ba']) && isset($card['pan']) && isset($card['pin'])) {
					// We have a valid record, let's redeem gift card in eb2c.
					$svRedeem = Mage::getModel('eb2cpayment/storedvalue_redeem', array('order' => $order, 'card' => $card));
					$svRedeem->redeemGiftCard();
					if ($svRedeem->getResponseCode() === self::REDEEM_RESPONSE_CODE_FAIL) {
						// SVC failed to be redeemed and cannot be used. Remove it from the
						// cart and add a message to the session to be shown to the user.
						Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByCode($card['pan'])->removeFromCart();
						$this->_checkoutSession->addSuccess(
							$helper->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_REMOVED, $this->_mageCoreHelper->escapeHtml($card['pan']))
						);
						throw Mage::exception('Mage_Core', $helper->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_WRONG_ACCOUNT));
					} elseif ($svRedeem->getPaymentAccountUniqueId()) {
						$card['panToken'] = $svRedeem->getPaymentAccountUniqueId();
						$card['requestId'] = $svRedeem->getRequestId();
						$giftCard[$idx] = $card;
					}
				}
			}
			// Re-store the cards with panToken to the quote and order.
			$order->setGiftCards(serialize($giftCard));
			$observer->getEvent()->getQuote()->setGiftCards(serialize($giftCard));
		}
	}

	/**
	 * Void any redeemed gift cards in the case that an order cannot be placed.
	 * Observes the 'eb2c_order_creation_failure'
	 * @see EbayEnterprise_Eb2cCore_Model_Observer::rollbackExchangePlatformOrder
	 * @param Varien_Event_Observer $observer
	 * @return void
	 */
	public function redeemVoidGiftCard($observer)
	{
		$order = $observer->getEvent()->getOrder();
		$giftCard = unserialize($order->getGiftCards());
		// When gift card data isn't an array of gift card data, don't even try
		// to work with the data.
		if (!is_array($giftCard)) {
			return;
		}
		$voidRequest = Mage::getModel('eb2cpayment/storedvalue_redeem_void');
		foreach ($giftCard as $card) {
			if (isset($card['ba']) && isset($card['pan']) && isset($card['pin'])) {
				// We have a valid record, let's RedeemVoid gift card in eb2c.
				$responseData = $voidRequest->voidCardRedemption(
					$card['pan'], $card['pin'], $order->getIncrementId(), $card['ba']
				);
				// The best we can do if the void request fails is log a warning.
				if (empty($responseData) || strtoupper($responseData['responseCode']) === 'FAIL') {
					$this->_log->logWarn(
						'[%s] Could not void stored value card redemption',
						array(__CLASS__)
					);
				}
			}
		}
	}

	/**
	 * Suppressing all non-eb2c payment modules or payment methods if eb2cpayment is turn on.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function suppressPaymentModule($observer)
	{
		$event = $observer->getEvent();

		$store = $event->getStore();
		$website = $event->getWebsite();

		$store = ($store instanceof Mage_Core_Model_Store) ? $store : $this->_coreHelper->getDefaultStore();
		$website = ($website instanceof Mage_Core_Model_Website) ? $website : $this->_coreHelper->getDefaultWebsite();

		$suppressor = Mage::getModel('eb2cpayment/suppression', array(
			'store' => $store,
			'website' => $website
		));
		if ($this->_paymentHelper->getConfigModel($store)->isPaymentEnabled) {
			// first let's disable any none eBay Enterprise payment method
			$suppressor->disableNonEb2cPaymentMethods();

			// let's enable only payment bridge and not paypal express it can be enabled manually
			// via Exchange platform config section
			$suppressor->saveEb2cPaymentMethods(1);
		} else {
			// let's disabled payment bridge ebay Enterprise Payment method.
			$suppressor->saveEb2cPaymentMethods(0);

			// let's disable any none eBay Enterprise payment method
			$suppressor->disableNonEb2cPaymentMethods();
		}

		return $this;
	}

	/**
	 * configure the PayPal payment action to the order action.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function configurePayPalPaymentAction($observer)
	{
		$event = $observer->getEvent();
		$store = $event->getStore();
		$website = $event->getWebsite();
		Mage::getModel('eb2cpayment/paypal_adminhtml_config')
			->applyExpressPaymentAction($store, $website);
		return $this;
	}

	/**
	 * Void any payments when the order creation has failed.
	 * Observes the 'eb2c_order_creation_failure' event
	 * @see EbayEnterprise_Eb2cCore_Model_Observer::rollbackExchangePlatformOrder
	 * @param  Varien_Event_Observer $observer Contains order that failed and quote used to create it
	 * @return self
	 */
	public function voidPayments($observer)
	{
		$order = $observer->getEvent()->getOrder();
		if ($order->canVoidPayment()) {
			$payment = $order->getPayment();
			// The `$order->canVoid` check wrapping this should prevent this method
			// from ever throwing the exception as we'll already know it can be voided.
			$payment->void($payment);
		}
		return $this;
	}
}
