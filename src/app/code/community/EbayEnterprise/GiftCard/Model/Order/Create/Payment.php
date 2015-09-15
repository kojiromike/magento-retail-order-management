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
    protected $giftcardContainer;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;

    public function __construct(array $args = [])
    {
        list(
            $this->giftcardContainer,
            $this->config
        ) = $this->checkTypes(
            $this->nullCoalesce('giftcard_container', $args, Mage::getModel('ebayenterprise_giftcard/container')),
            $this->nullCoalesce('config', $args, Mage::helper('ebayenterprise_creditcard')->getConfigModel())
        );
    }

    /**
     * Type checks for constructor args array.
     *
     * @param  EbayEnterprise_GiftCard_Model_IContainer
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_GiftCard_Model_IContainer $container,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config
    ) {
        return func_get_args();
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
        foreach ($this->giftcardContainer->getRedeemedGiftcards() as $giftcard) {
            $iterable = $paymentContainer->getPayments();
            $payload = $iterable->getEmptyStoredValueCardPayment();
            $payload
                // payment context
                ->setIsMockPayment($this->config->testModeFlag)
                ->setOrderId($order->getIncrementId())
                ->setTenderType($giftcard->getTenderType())
                ->setAccountUniqueId($giftcard->getCardNumber())
                ->setPanIsToken((bool) $giftcard->getPanIsToken())
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

    protected function nullCoalesce($key, array $args, $default)
    {
        return isset($args[$key]) ? $args[$key] : $default;
    }
}
