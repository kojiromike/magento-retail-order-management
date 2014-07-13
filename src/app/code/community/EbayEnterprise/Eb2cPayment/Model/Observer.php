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

	/**
	 * Redeem any gift card when 'eb2c_redeem_giftcard' event is dispatched
	 *
	 * @param Varien_Event_Observer $observer
	 * @throws Mage_Core_Exception
	 * @return void
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function redeemGiftCard($observer)
	{
		$order = $observer->getEvent()->getOrder();
		$giftCard = unserialize($order->getGiftCards());
		if ($giftCard) {
			foreach ($giftCard as $idx => $card) {
				if (isset($card['ba']) && isset($card['pan']) && isset($card['pin'])) {
					// We have a valid record, let's redeem gift card in eb2c.
					$svRedeem = Mage::getModel('eb2cpayment/storedvalue_redeem');
					$storeValueRedeemReply = $svRedeem->getRedeem($card['pan'], $card['pin'], $order->getIncrementId(), $card['ba']);
					if ($storeValueRedeemReply) {
						$redeemData = $svRedeem->parseResponse($storeValueRedeemReply);
						if ($redeemData) {
							// making sure we have the right data
							if (isset($redeemData['responseCode']) && strtoupper(trim($redeemData['responseCode'])) === 'FAIL') {
								// removed gift card from the shopping cart
								Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByPanPin($card['pan'], $card['pin'])
									->removeFromCart();
								$helper = Mage::helper('enterprise_giftcardaccount');
								Mage::getSingleton('checkout/session')->addSuccess(
									$helper->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_REMOVED, Mage::helper('core')->escapeHtml($card['pan']))
								);
								throw Mage::exception('Mage_Core', $helper->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_WRONG_ACCOUNT));
							} else {
								$card['panToken'] = $redeemData['paymentAccountUniqueId'];
								$giftCard[$idx] = $card;
							}
						}
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
					Mage::helper('ebayenterprise_magelog')->logWarn(
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
		$helper = Mage::helper('eb2ccore');

		$store = $event->getStore();
		$website = $event->getWebsite();

		$store = ($store instanceof Mage_Core_Model_Store)? $store : $helper->getDefaultStore();
		$website = ($website instanceof Mage_Core_Model_Website)? $website : $helper->getDefaultWebsite();

		$suppressor = Mage::getModel('eb2cpayment/suppression', array(
			'store' => $store,
			'website' => $website
		));
		if (Mage::helper('eb2cpayment')->getConfigModel($store)->isPaymentEnabled) {
			Mage::log(sprintf('[%s::%s] Enabling eBay Enterprise Payment Methods', __CLASS__, __METHOD__), Zend_Log::DEBUG);

			// first let's disable any none eBay Enterprise payment method
			$suppressor->disableNonEb2cPaymentMethods();

			// let's enable only payment bridge and not paypal express it can be enabled manually
			// via Exchange platform config section
			$suppressor->saveEb2cPaymentMethods(1);
		} else {
			Mage::log(sprintf('[%s::%s] disabling eBay Enterprise Payment Methods', __CLASS__, __METHOD__), Zend_Log::DEBUG);
			// let's disabled payment bridge ebay Enterprise Payment method.
			$suppressor->saveEb2cPaymentMethods(0);

			// let's disable any none eBay Enterprise payment method
			$suppressor->disableNonEb2cPaymentMethods();
		}

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
