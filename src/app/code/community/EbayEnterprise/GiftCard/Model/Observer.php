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

class EbayEnterprise_GiftCard_Model_Observer
{
	const REDEEM_FAILED_NETWORK_RETRY = 'EbayEnterprise_GiftCard_Redeem_Failed_Network_Retry';
	const REDEEM_FAILED_UNEXPECTED_AMOUNT = 'EbayEnterprise_GiftCard_Redeem_Failed_Unexpected_Amount';
	const REDEEM_FAILED_UNREDEEMABLE_CARD = 'EbayEnterprise_GiftCard_Redeem_Failed_Unredeemable_Card';
	/** @var EbayEnterprise_GiftCard_Model_Container */
	protected $_giftCardContainer;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;

	/**
	 * @param array $initParams May contain:
	 *                          - 'helper' => EbayEnterprise_GiftCard_Helper_Data
	 *                          - 'gift_card_container' => EbayEnterprise_GiftCard_Model_IContainer
	 *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
	 *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
	 */
	public function __construct(array $initParams=array())
	{
		list($this->_helper, $this->_giftCardContainer, $this->_logger, $this->_context) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_giftcard')),
			$this->_nullCoalesce($initParams, 'gift_card_container', Mage::getModel('ebayenterprise_giftcard/container')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
		);
	}
	/**
	 * Type checks for self::__construct $initParams
	 * @param  EbayEnterprise_GiftCard_Helper_Data $helper
	 * @param  EbayEnterprise_GiftCard_Model_IContainer $container
	 * @param  EbayEnterprise_MageLog_Helper_Data $logger
	 * @param  EbayEnterprise_MageLog_Helper_Context $context
	 * @return mixed[]
	 */
	protected function _checkTypes(
		EbayEnterprise_GiftCard_Helper_Data $helper,
		EbayEnterprise_GiftCard_Model_IContainer $giftCardContainer,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $context
	) {
		return array($helper, $giftCardContainer, $logger, $context);
	}
	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 * @param array      $arr
	 * @param string|int $field Valid array key
	 * @param mixed      $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}
	/**
	 * Get the checkout session. Not set in constructor to prevent early construction
	 * preventing session from being constructed without data.
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckoutSession()
	{
		return Mage::getSingleton('checkout/session');
	}
	/**
	 * Force Zero Subtotal Checkout if the grand total is completely covered by SC and/or GC
	 *
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function togglePaymentMethods($observer)
	{
		$event = $observer->getEvent();
		$quote = $event->getQuote();
		if (!$quote || $quote->getBaseGrandTotal() > 0) {
			return $this;
		}
		// disable all payment methods and enable only Zero Subtotal Checkout
		$paymentMethodInstance = $event->getMethodInstance();
		$paymentMethodCode = $paymentMethodInstance->getCode();
		$result = $event->getResult();
		// allow customer to place order if grand total is zero
		$result->isAvailable = $paymentMethodCode === 'free' && empty($result->isDeniedInConfig);
		return $this;
	}
	/**
	 * Redeem any gift cards applied to the order
	 * @param Varien_Event_Observer $observer
	 * @return self
	 * @throws EbayEnterprise_GiftCard_Exception If expected amount to be redeemed from the gift cards cannot be redeemed
	 */
	public function redeemGiftCards(Varien_Event_Observer $observer)
	{
		$orderId = $observer->getEvent()->getOrder()->getIncrementId();
		$cards = $this->_giftCardContainer->getUnredeemedGiftCards();
		$errors = array();
		foreach ($cards as $card) {
			try {
				$this->_redeemCard($card->setOrderId($orderId));
			} catch (EbayEnterprise_GiftCard_Exception $e) {
				// Capture any error messages, all errors get rolled up into a single
				// error after attempting to redeem all of the cards. Allows any issues
				// with redemptions to be caught in a single pass instead of only one
				// on each attempt.
				$errors[] = $e->getMessage();
			}
		}
		if ($errors) {
			// If there were any errors, throw a new, single exception with all
			// errors concatenated into a single message.
			throw Mage::exception('EbayEnterprise_GiftCard', implode("\n", $errors));
		}
		// set the redeemed cards onto the order for inclusion in the order create request
		$observer->getEvent()->getOrder()->setEbayEnterpriseRedeemedGiftCards($cards);
		return $this;
	}
	/**
	 * Redeem the gift card and handle any errors encountered while redeeming
	 * the gift card. Return the amount redeemed from the card.
	 * @param EbayEnterprise_GiftCard_Model_IGiftcard $card
	 * @param string $orderId
	 * @return float
	 * @throws EbayEnterprise_GiftCard_Exception If card could not be redeemed for for the expected amount
	 */
	protected function _redeemCard(EbayEnterprise_GiftCard_Model_IGiftcard $card)
	{
		try {
			$card->redeem();
		} catch (EbayEnterprise_GiftCard_Exception_Network_Exception $e) {
			// Error message indicating card could not be redeemed at this time and to
			// try again or remove card.
			$this->_failGiftCardRedeem($card, self::REDEEM_FAILED_NETWORK_RETRY);
		} catch (EbayEnterprise_GiftCard_Exception $e) {
			// Card cannot be redeemed at all - bad request or response payload,
			// or failed to be redeemed - trying again won't make a difference so
			// remove card from container and give message indicating card could not
			// be redeemed and has been removed.
			$this->_failGiftCardRedeem($card, self::REDEEM_FAILED_UNREDEEMABLE_CARD, true);
		}
		$this->_validateCardRedeemed($card);
		return $card->getAmountRedeemed();
	}
	/**
	 * Validate the amount the card was redeemed for. Card should only be considered
	 * to have been redeemed successfully if the amount expected to be redeemed
	 * matches the amount actually redeemed.
	 * @param EbayEnterprise_GiftCard_Model_IGiftcard $card
	 * @return self
	 */
	protected function _validateCardRedeemed(EbayEnterprise_GiftCard_Model_IGiftcard $card)
	{
		if ($card->getAmountRedeemed() !== $card->getAmountToRedeem()) {
			// Card could not be redeemed for full expected amount but may still
			// be valid.
			if (($card->getBalanceAmount() + $card->getAmountRedeemed()) === 0.00) {
				// no balance left on the card so no point in keeping it on the order.
				// remove card and message card could not be redeemed and has been removed
				$this->_failGiftCardRedeem($card, self::REDEEM_FAILED_UNREDEEMABLE_CARD, true);
			}
			$this->_failGiftCardRedeem($card, self::REDEEM_FAILED_UNEXPECTED_AMOUNT);
		}
		return $this;
	}
	/**
	 * Set the checkout step to return to when a redemption fails. Will set the
	 * goto and update section to the given section.
	 * One of: 'login', 'billing', 'shipping', 'shipping_method', 'payment', 'review'
	 * @param EbayEnterprise_GiftCard_Model_IGiftcard $card
	 * @param string $message
	 * @param bool $shouldRemoveCard Should the gift card also be removed from the order, default false
	 * @return self
	 */
	protected function _failGiftCardRedeem(EbayEnterprise_GiftCard_Model_IGiftcard $card, $message, $shouldRemoveCard=false)
	{
		if ($shouldRemoveCard) {
			$this->_giftCardContainer->removeGiftCard($card);
		}
		// always send back to payment step to review applied gift card amounts or
		// enter additional payment information
		$this->_getCheckoutSession()->setGotoSection('payment')->setUpdateSection('payment-method');
		// as gift cards will have been modified in this case, either removed or
		// amounts updated, recollect totals to ensure gift card amounts and payment
		// methods are properly updated
		$this->_getCheckoutSession()->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
		throw Mage::exception('EbayEnterprise_GiftCard', $this->_helper->__($message, $card->getCardNumber(), $card->getAmountToRedeem(), $card->getAmountRedeemed()));
	}
	/**
	 * Void any gift card redemptions applied to the order.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function redeemVoidGiftCards(Varien_Event_Observer $observer)
	{
		foreach ($this->_giftCardContainer->getRedeemedGiftCards() as $card) {
			$this->_redeemVoidCard($card);
		}
		// remove redeemed cards from the order
		$observer->getEvent()->getOrder()->unsEbayEnterpriseRedeemedGiftCards();
		return $this;
	}
	/**
	 * Redeem the gift card for the requested amount and return the amount that
	 * was actually redeemed for the card.
	 * @param EbayEnterprise_GiftCard_Model_IGiftcard $card
	 * @param float $amount
	 * @return self
	 */
	protected function _redeemVoidCard(EbayEnterprise_GiftCard_Model_IGiftcard $card)
	{
		try {
			$card->void();
		} catch (EbayEnterprise_GiftCard_Exception $e) {
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
		}
		return $card;
	}
	/**
	 * Before collecting totals, empty any expected amounts to redeem from cards.
	 * @param Varien_Event_Observer $observer unused; only here to maintain signature
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function resetGiftCardTotals(Varien_Event_Observer $observer)
	{
		foreach ($this->_giftCardContainer->getUnredeemedGiftCards() as $card) {
			$card->setAmountToRedeem(0.00);
		}
		return $this;
	}
}
