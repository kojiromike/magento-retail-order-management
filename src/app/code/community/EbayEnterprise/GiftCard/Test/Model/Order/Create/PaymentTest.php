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

/**
 * Test Order Create Payment Payload Injection
 */
class EbayEnterprise_GiftCard_Test_Model_Order_Create_PaymentTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const PAYMENT_CONTAINER_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer';
    const PAYMENT_ITERABLE_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentIterable';
    const PAYMENT_PAYLOAD_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IStoredValueCardPayment';

    /** @var Mage_Sales_Model_Order (stub) */
    protected $_orderStub;
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer */
    protected $_paymentContainer;
    /** @var EbayEnterprise_Giftcard_Model_Giftcard */
    protected $_giftcardStub;
    /** @var EbayEnterprise_Giftcard_Model_Container */
    protected $_giftcardContainerStub;
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPayPalPayment */
    protected $_payloadStub;

    public function setUp()
    {
        $this->_payloadStub = $this->getMock(self::PAYMENT_PAYLOAD_CLASS);
        $paymentIterable = $this->getMock(self::PAYMENT_ITERABLE_CLASS);
        $paymentIterable->expects($this->any())
            ->method('getEmptyStoredValueCardPayment')
            ->will($this->returnValue($this->_payloadStub));
        $this->_paymentContainer = $this->getMock(self::PAYMENT_CONTAINER_CLASS);
        $this->_paymentContainer->expects($this->any())
            ->method('getPayments')
            ->will($this->returnValue($paymentIterable));

        $this->_giftcardStub = $this->getModelMock('ebayenterprise_giftcard/giftcard');
        $this->_giftcardContainerStub = $this->getModelMock(
            'ebayenterprise_giftcard/container',
            ['getRedeemedGiftCards']
        );
        $this->_giftcardContainerStub->expects($this->any())
            ->method('getRedeemedGiftCards')
            ->will($this->returnValue(array($this->_giftcardStub)));
        $this->_orderStub = $this->getModelMock('sales/order');
    }
    /**
     * verify
     * - payment will be made into a payload using the pan
     */
    public function testAddPaymentsToPayload()
    {
        $processedPayments = new SplObjectStorage();
        $this->_giftcardStub->expects($this->any())
            ->method('getRedeemedAt')
            ->will($this->returnValue(new DateTime()));
        $this->_stubPayload();
        $handler = Mage::getModel(
            'ebayenterprise_giftcard/order_create_payment',
            ['giftcard_container' => $this->_giftcardContainerStub]
        );
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $processedPayments);
    }
    /**
     * stub out the setter methods for the payload excluding those
     * specified in the array
     * @param  array  $exclude
     */
    protected function _stubPayload($exclude = array())
    {
        $methods = array(
            'setOrderId',
            'setTenderType',
            'setAccountUniqueId',
            'setPanIsToken',
            'setCreateTimestamp',
            'setAmount',
            'setPin',
        );
        foreach ($methods as $method) {
            if (!in_array($method, $exclude)) {
                $this->_payloadStub->expects($this->once())
                    ->method($method)
                    ->will($this->returnSelf());
            }
        }
    }
}
