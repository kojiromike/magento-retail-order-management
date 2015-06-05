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
 * Test Order Create
 */
class EbayEnterprise_Order_Test_Model_Create_PaymentTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const PAYMENT_CONTAINER_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer';
    const PAYMENT_ITERABLE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentIterable';
    const PAYMENT_PAYLOAD_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPrepaidCreditCardPayment';

    /** @var Mage_Sales_Model_Order (stub) */
    protected $_orderStub;
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer */
    protected $_paymentContainer;
    /** @var array stubbed payment models */
    protected $_paymentStubs = [];
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPrepaidCreditCardPayment */
    protected $_payloadStub;
    protected $_processedPayments;

    public function setUp()
    {
        $this->_processedPayments = new SplObjectStorage();
        $this->_payloadStub = $this->getMock(self::PAYMENT_PAYLOAD_CLASS);
        $paymentIterable = $this->getMock(self::PAYMENT_ITERABLE_CLASS);
        $paymentIterable->expects($this->any())
            ->method('getEmptyPrepaidCreditCardPayment')
            ->will($this->returnValue($this->_payloadStub));
        $this->_paymentContainer = $this->getMock(self::PAYMENT_CONTAINER_CLASS);
        $this->_paymentContainer->expects($this->any())
            ->method('getPayments')
            ->will($this->returnValue($paymentIterable));
        $paymentMethod = $this->getModelMock('payment/method_abstract', ['getCode']);
        $paymentMethod->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue('abstractPaymentMethodStub'));
        $plainPayment = $this->getModelMock('sales/order_payment', ['getId', 'getMethod']);
        $plainPayment->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $plainPayment->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($paymentMethod));
        $discoverCardPayment = $this->getModelMock('sales/order_payment', ['getId', 'getCcType', 'getMethod']);
        $discoverCardPayment->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $discoverCardPayment->expects($this->any())
            ->method('getCcType')
            ->will($this->returnValue('DC'));
        $discoverCardPayment->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($paymentMethod));
        $freePayment = $this->getModelMock('sales/order_payment', ['getId', 'getMethod']);
        $freePayment->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));
        $freePayment->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue(Mage::getModel('payment/method_free')->getCode()));
        $this->_paymentStubs = [$plainPayment, $discoverCardPayment, $freePayment];
        $this->_orderStub = $this->getModelMock('sales/order', ['getAllPayments']);
    }

    /**
     * verify
     * - payment will be made into payload
     * - the brand will not be set for a payment with no cc type configured
     */
    public function testAddPaymentsToPayload()
    {
        $this->_orderStub->expects($this->once())
            ->method('getAllPayments')
            ->will($this->returnValue([$this->_paymentStubs[0]]));
        $this->_payloadStub->expects($this->never())
            ->method('setBrand');
        $this->_payloadStub->expects($this->once())
            ->method('setAmount')
            ->will($this->returnSelf());
        $handler = Mage::getModel('ebayenterprise_order/create_payment');
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $this->_processedPayments);
    }

    /**
     * verify
     * - payment will be made into payload
     * - the brand will be set for a payment with cc type configured
     */
    public function testAddPaymentsToPayloadPaymentHasCCType()
    {
        $this->_orderStub->expects($this->once())
            ->method('getAllPayments')
            ->will($this->returnValue([$this->_paymentStubs[1]]));
        $this->_payloadStub->expects($this->once())
            ->method('setBrand')
            ->will($this->returnSelf());
        $this->_payloadStub->expects($this->once())
            ->method('setAmount')
            ->will($this->returnSelf());
        $handler = Mage::getModel('ebayenterprise_order/create_payment');
        $handler->addPaymentsToPayload(
            $this->_orderStub,
            $this->_paymentContainer,
            $this->_processedPayments
        );
    }

    /**
     * verify
     * - no payments will be processed if all are in the
     *   processed list
     */
    public function testAddPaymentsToPayloadAlreadyProcessed()
    {
        $this->_orderStub->expects($this->once())
            ->method('getAllPayments')
            ->will($this->returnValue($this->_paymentStubs));
        foreach ($this->_paymentStubs as $stub) {
            $this->_processedPayments->attach($stub);
        }
        $this->_payloadStub->expects($this->never())
            ->method('setBrand');
        $this->_payloadStub->expects($this->never())
            ->method('setAmount');
        $handler = Mage::getModel('ebayenterprise_order/create_payment');
        $handler->addPaymentsToPayload(
            $this->_orderStub,
            $this->_paymentContainer,
            $this->_processedPayments
        );
    }

    /**
     * verify
     * - the free payment method will not be processed
     */
    public function testAddPaymentsToPayloadFreePaymentMethod()
    {
        $this->_orderStub->expects($this->once())
            ->method('getAllPayments')
            ->will($this->returnValue([$this->_paymentStubs[2]]));
        $this->_payloadStub->expects($this->never())
            ->method('setAmount');
        $handler = Mage::getModel('ebayenterprise_order/create_payment');
        $handler->addPaymentsToPayload(
            $this->_orderStub,
            $this->_paymentContainer,
            $this->_processedPayments
        );
    }
}
