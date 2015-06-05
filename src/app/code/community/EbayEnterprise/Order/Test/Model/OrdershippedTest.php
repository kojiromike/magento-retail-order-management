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

use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Order_Test_Model_OrdershippedTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const PAYLOAD_CUSTOMER_ORDER_ID = '10004062';
    const PAYLOAD_STORE_ID = 'LAH383';

    /** @var PayloadFactory $_payloadFactory */
    protected $_payloadFactory;
    /** @var OrderEvents\OrderShipped $_payload */
    protected $_payload;
    /** @var EbayEnterprise_Order_Model_Ordershipped $_ordershipped */
    protected $_ordershipped;
    /** @var EbayEnterprise_Order_Helper_Event_Shipment $_shipmentHelper */
    protected $_shipmentHelper;

    public function setUp()
    {
        parent::setUp();
        $this->_payloadFactory = new PayloadFactory();
        $this->_payload = $this->_payloadFactory->buildPayload('\eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\OrderShipped');

        $this->_payload->setCustomerOrderId(static::PAYLOAD_CUSTOMER_ORDER_ID)
            ->setStoreId(static::PAYLOAD_STORE_ID);

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);

        $this->_shipmentHelper = $this->getHelperMock('ebayenterprise_order/event_shipment', ['process']);

        $this->_ordershipped = Mage::getModel('ebayenterprise_order/ordershipped', [
            'payload' => $this->_payload,
            'shipment_event_helper' => $this->_shipmentHelper
        ]);
    }
    /**
     * This is a procedural test, testing that the method `ebayenterprise_order/ordershipped::process`
     * when invoke will call the expected methods and pass in the expected parameter values.
     */
    public function testOrderShippedEvent()
    {
        $id = 7;
        $canShip = true;
        $order = $this->getModelMock('sales/order', ['loadByIncrementId', 'canShip']);
        $order->setId($id);
        $order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($this->identicalTo(static::PAYLOAD_CUSTOMER_ORDER_ID))
            ->will($this->returnSelf());
        $order->expects($this->any())
            ->method('canShip')
            ->will($this->returnValue($canShip));
        $this->replaceByMock('model', 'sales/order', $order);

        $this->_shipmentHelper->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($order), $this->identicalTo($this->_payload))
            ->will($this->returnSelf());

        $this->_ordershipped->process();
    }
    /**
     * Testing the 'ebayenterprise_order/ordershipped::_getOrder()' protected method when the order in the payload
     * is not found in the Magento Store.
     */
    public function testGetOrderMethodOrderInPayloadNotFound()
    {
        $expectedReturnValue = null;
        $id = 0;

        $order = $this->getModelMock('sales/order', ['loadByIncrementId']);
        $order->setId($id);
        $order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($this->identicalTo(static::PAYLOAD_CUSTOMER_ORDER_ID))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'sales/order', $order);

        $this->assertSame($expectedReturnValue, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_ordershipped,
            '_getOrder',
            []
        ));
    }
    /**
     * Testing the 'ebayenterprise_order/ordershipped::_getOrder()' protected method when the order
     * can not be shipped.
     */
    public function testGetOrderMethodOrderNotShippable()
    {
        $expectedReturnValue = null;
        $id = 9;
        $canShip = false;

        $order = $this->getModelMock('sales/order', ['loadByIncrementId', 'canShip']);
        $order->setId($id);
        $order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($this->identicalTo(static::PAYLOAD_CUSTOMER_ORDER_ID))
            ->will($this->returnSelf());
        $order->expects($this->any())
            ->method('canShip')
            ->will($this->returnValue($canShip));
        $this->replaceByMock('model', 'sales/order', $order);

        $this->assertSame($expectedReturnValue, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_ordershipped,
            '_getOrder',
            []
        ));
    }
}
