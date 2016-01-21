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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer;

class EbayEnterprise_Giftcard_Model_Order_Create_Payment
{
    /** @var EbayEnterprise_GiftCard_Model_IContainer */
    protected $_giftcardContainer;
    public function __construct(array $args = [])
    {
        list(
            $this->_giftcardContainer
        ) = $this->_enforceTypes(
            $this->_nullCoalesce('giftcard_container', $args, Mage::getModel('ebayenterprise_giftcard/container'))
        );
    }

    /**
     * ensure correct types
     * @param  EbayEnterprise_GiftCard_Model_IContainer $container
     * @return array
     */
    protected function _enforceTypes(EbayEnterprise_GiftCard_Model_IContainer $container)
    {
        return [$container];
    }

    /**
     * Make stored value card payloads for any redeemed
     * gift cards
     *
     * @param Mage_Sales_Model_Order $order
     * @param IPaymentContainer      $paymentContainer
     * @param SplObjectStorage       $processedPayments
     */
    public function addPaymentsToPayload(
        Mage_Sales_Model_Order $order,
        IPaymentContainer $paymentContainer,
        SplObjectStorage $processedPayments
    ) {
        foreach ($this->_giftcardContainer->getRedeemedGiftcards() as $giftcard) {
            $iterable = $paymentContainer->getPayments();
            $payload = $iterable->getEmptyStoredValueCardPayment();
            $payload
                // payment context
                ->setOrderId($order->getIncrementId())
                ->setTenderType($giftcard->getTenderType())
                ->setAccountUniqueId($this->getGcPan($giftcard))
                ->setPanIsToken($this->isPanTokenize($giftcard))
                // payment data
                ->setCreateTimestamp($giftcard->getRedeemedAt())
                ->setAmount($giftcard->getAmountRedeemed())
                ->setPin($giftcard->getPin())
                ->setPaymentRequestId($giftcard->getRedeemRequestId());
            // add the new payload
            $iterable->OffsetSet($payload, $payload);
            // put the payment in the processed payments set
            $processedPayments->attach($giftcard);
        }
    }

    protected function _nullCoalesce($key, array $args, $default)
    {
        return isset($args[$key]) ? $args[$key] : $default;
    }

    /**
     * Determine if the PAN in the giftcard is tokenized.
     *
     * @param EbayEnterprise_GiftCard_Model_IGiftcard
     * @return bool
     */
    protected function isPanTokenize(EbayEnterprise_GiftCard_Model_IGiftcard $giftcard)
    {
        return !is_null($giftcard->getTokenizedCardNumber());
    }

    /**
     * Get the Giftcard PAN. Return the tokenized pan if it is tokenized otherwise
     * return the raw PAN.
     *
     * @param EbayEnterprise_GiftCard_Model_IGiftcard
     * @return bool
     */
    protected function getGcPan(EbayEnterprise_GiftCard_Model_IGiftcard $giftcard)
    {
        return $this->isPanTokenize($giftcard) ? $giftcard->getTokenizedCardNumber() : $giftcard->getCardNumber();
    }
}
