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

class EbayEnterprise_GiftCard_Block_Checkout_Onepage_Payment_Additional extends EbayEnterprise_GiftCard_Block_Template_Abstract
{
    const ADD_REMOVE_MESSAGE = 'EbayEnterprise_GiftCard_PaymentMethod_Additional_Add_Remove';
    const APPLIED_TOTALS_MESSAGE = 'EbayEnterprise_GiftCard_PaymentMethod_Additional_Applied_Total';
    /** @var EbayEnterprise_GiftCard_Model_Container */
    protected $_giftCardContainer;
    /** @var Mage_Core_Helper_Data */
    protected $_coreHelper;

    /**
     * Called from parent::__construct. Init params passed to __construct will be
     * set as magic data. Init params may include:
     * - 'gift_card_container' => EbayEnterprise_GiftCard_Model_Container
     * - 'core_helper' => Mage_Core_Helper_Data
     */
    protected function _construct()
    {
        parent::_construct();
        list($this->_giftCardContainer, $this->_coreHelper) = $this->_checkTypes(
            $this->_nullCoalesce($this->getData(), 'gift_card_container', Mage::getModel('ebayenterprise_giftcard/container')),
            $this->_nullCoalesce($this->getDAta(), 'core_helper', Mage::helper('core'))
        );
    }
    /**
     * Type check for injected constructor arguments.
     * @param  EbayEnterprise_GiftCard_Model_Container $giftCardContainer
     * @param  Mage_Core_Helper_Data                   $coreHelper
     * @return mixed[]
     */
    protected function _checkTypes(
        EbayEnterprise_GiftCard_Model_Container $giftCardContainer,
        Mage_Core_Helper_Data $coreHelper
    ) {
        return array($giftCardContainer, $coreHelper);
    }
    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array      $arr
     * @param  string|int $field Valid array key
     * @param  mixed      $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }
    /**
     * Get the url for the cart.
     * @return string
     */
    protected function _getCartUrl()
    {
        return Mage::getUrl('checkout/cart');
    }
    /**
     * Check for any gift cards to be applied to the order.
     * @return bool
     */
    protected function _hasGiftCardsApplied()
    {
        return $this->_giftCardContainer->getUnredeemedGiftCards()->count() > 0;
    }
    /**
     * Get the total amount to be redeemed from gift cards, formatted as currency.
     * @return string
     */
    protected function _getAppliedGiftCardTotal()
    {
        $appliedTotal = 0.00;
        foreach ($this->_giftCardContainer->getUnredeemedGiftCards() as $card) {
            $appliedTotal += $card->getAmountToRedeem();
        }
        return $this->_coreHelper->currency($appliedTotal, true, false);
    }
    protected function _getAddRemoveMessage()
    {
        return $this->__(self::ADD_REMOVE_MESSAGE, $this->_getCartUrl());
    }
    protected function _getAppliedTotalsMessage()
    {
        return $this->__(self::APPLIED_TOTALS_MESSAGE, $this->_getAppliedGiftCardTotal());
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * @return float
     */
    protected function getBaseGrandTotal()
    {
        return (float) $this->getQuote()->getBaseGrandTotal();
    }

    /**
     * Determine if any non-free payment method is needed.
     *
     * @return bool
     */
    protected function isFullyPaidAfterApplication()
    {
        $quote = $this->getQuote();
        return (
            ($quote->getBaseGrandTotal() <= 0) &&
            ($quote->getCustomerBalanceAmountUsed() <= 0) &&
            ($quote->getRewardPointsBalance() <= 0)
        );
    }
}
