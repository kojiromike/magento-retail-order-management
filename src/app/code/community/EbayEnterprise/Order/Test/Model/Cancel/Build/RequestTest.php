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

use eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCancelRequest;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Order_Test_Model_Cancel_Build_RequestTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';

    /** @var Mock_IBidirectionalApi */
    protected $_api;
    /** @var Mock_IOrderCancelRequest */
    protected $_payload;

    public function setUp()
    {
        parent::setUp();
        $this->_payload = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        $this->_api = $this->getMockBuilder(static::API_CLASS)
            // Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
            ->disableOriginalConstructor()
            ->setMethods(['getRequestBody'])
            ->getMock();
    }

    /**
     * Test that the method EbayEnterprise_Order_Model_Cancel_Build_Request::build()
     * is invoked, and it will call the method EbayEnterprise_Order_Model_Cancel_Build_Request::_buildPayload().
     * Finally, the method EbayEnterprise_Order_Model_Cancel_Build_Request::build() will return
     * an instance of type IOrderCancelRequest.
     */
    public function testBuildOrderCancelRequestPayload()
    {
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');

        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($this->_payload));

        /** @var Mock_EbayEnterprise_Order_Model_Cancel_Build_Request */
        $buildRequest = $this->getModelMock('ebayenterprise_order/cancel_build_request', ['_buildPayload'], false, [[
            // This key is required
            'api' => $this->_api,
            // This key is required
            'order' => $order,
        ]]);
        $buildRequest->expects($this->once())
            ->method('_buildPayload')
            ->will($this->returnSelf());
        $this->assertSame($this->_payload, $buildRequest->build());
    }

    /**
     * Test that the method ebayenterprise_order/cancel_build_request::_buildPayload()
     * is invoked, and it will call the method IOrderCancelRequest::setOrderType() and passed
     * it the class constant ebayenterprise_order/cancel_build_irequest::ORDER_TYPE. Then, the
     * method IOrderCancelRequest::setCustomerOrderId() will be called and passed in as parameter
     * the return value from calling the sales/order::getIncrementId() varien magic method. Then,
     * the method IOrderCancelRequest::setReasonCode() will called and passing in as parameter
     * the return value from calling the method ebayenterprise_order/cancel_build_request::_getReasonCode().
     * And then, the method IOrderCancelRequest::setReason() will invoke passing in as parameter
     * the return value from calling the method ebayenterprise_order/cancel_build_request::_getReasonDescription().
     * Finally, the method ebayenterprise_order/cancel_build_request::_buildPayload() will return itself.
     */
    public function testBuildPayloadForOrderCancelRequest()
    {
        /** @var string */
        $incrementId = '1000000783731';
        /** @var string */
        $reasonCode = 'reason_code_001';
        /** @var string */
        $reason = 'Wrong Products';

        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order', [
            'increment_id' => $incrementId,
            'cancel_reason_code' => $reasonCode,
        ]);
        /** @var Mock_IOrderCancelRequest */
        $payload = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->setMethods(['setOrderType', 'setCustomerOrderId', 'setReasonCode', 'setReason'])
            ->getMock();
        $payload->expects($this->once())
            ->method('setOrderType')
            ->with($this->identicalTo(EbayEnterprise_Order_Model_Cancel_Build_IRequest::ORDER_TYPE))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setCustomerOrderId')
            ->with($this->identicalTo($incrementId))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setReasonCode')
            ->with($this->identicalTo($reasonCode))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setReason')
            ->with($this->identicalTo($reason))
            ->will($this->returnSelf());

        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($payload));

        /** @var Mock_EbayEnterprise_Order_Model_Cancel_Build_Request */
        $buildRequest = $this->getModelMock('ebayenterprise_order/cancel_build_request', ['_getReasonCode', '_getReasonDescription'], false, [[
            // This key is required
            'api' => $this->_api,
            // This key is required
            'order' => $order,
        ]]);
        $buildRequest->expects($this->once())
            ->method('_getReasonCode')
            ->will($this->returnValue($reasonCode));
        $buildRequest->expects($this->once())
            ->method('_getReasonDescription')
            ->will($this->returnValue($reason));
        $this->assertSame($buildRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($buildRequest, '_buildPayload', []));
    }

    /**
     * @return array
     */
    public function providerGetReasonCode()
    {
        return [
            [Mage::getModel('sales/order', ['cancel_reason_code' => 'reason_code_0001']), 'reason_code_0001'],
            [Mage::getModel('sales/order', ['cancel_reason_code' => null]), uniqid('OCR-')],
        ];
    }

    /**
     * Test that the method ebayenterprise_order/cancel_build_request::_getReasonCode()
     * is invoked, and it will call the varien magic method sales/order::getCancelReasonCode().
     * If the varien magic method sales/order::getCancelReasonCode() return a non-empty
     * string value then the method ebayenterprise_order/cancel_build_request::_getReasonCode()
     * will simply return that value. Otherwise the method ebayenterprise_order/cancel_build_request::_generateReasonCode()
     * will be invoked and the method ebayenterprise_order/cancel_build_request::_getReasonCode() will return that value.
     * @param Mage_Sales_Model_Order
     * @param string
     * @dataProvider providerGetReasonCode
     */
    public function testGetReasonCode(Mage_Sales_Model_Order $order, $result)
    {
        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($this->_payload));

        /** @var Mock_EbayEnterprise_Order_Model_Cancel_Build_Request */
        $buildRequest = $this->getModelMock('ebayenterprise_order/cancel_build_request', ['_generateReasonCode'], false, [[
            // This key is required
            'api' => $this->_api,
            // This key is required
            'order' => $order,
        ]]);
        $buildRequest->expects($order->getCancelReasonCode()? $this->never() : $this->once())
            // When the varien magic method sales/order::getCancelReasonCode() return
            // a non-empty string value then this method will never be called, otherwise
            // we expect it to be invoked once.
            ->method('_generateReasonCode')
            ->will($this->returnValue($result));

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($buildRequest, '_getReasonCode', []));
    }

    /**
     * @return array
     */
    public function providerGetReasonDescription()
    {
        return [
            [Mage::getModel('sales/order', ['cancel_reason_code' => 'reason_code_0001']), 'Wrong Products'],
            [Mage::getModel('sales/order', ['cancel_reason_code' => null]), null],
        ];
    }

    /**
     * Test that the method ebayenterprise_order/cancel_build_request::_getReasonDescription()
     * is invoked, and it will call the varien magic method sales/order::getCancelReasonCode().
     * If the varien magic method sales/order::getCancelReasonCode() return a non-empty
     * string value then the helper method ebayenterprise_order/data::getCancelReasonDescription()
     * will invoked and passed in the as parameter the return value from calling the
     * varien magic method sales/order::getCancelReasonCode(). The method
     * ebayenterprise_order/cancel_build_request::_getReasonDescription() will simply return that value.
     * However, if the return value from calling the varien magic method sales/order::getCancelReasonCode()
     * an empty string or null, then the method ebayenterprise_order/cancel_build_request::_getReasonDescription()
     * will return null.
     * @param Mage_Sales_Model_Order
     * @param string
     * @dataProvider providerGetReasonDescription
     */
    public function testGetReasonDescription(Mage_Sales_Model_Order $order, $result)
    {
        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($this->_payload));

        $code = $order->getCancelReasonCode();
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['getCancelReasonDescription']);
        $orderHelper->expects($code? $this->once() : $this->never())
            // When the varien magic method sales/order::getCancelReasonCode() return
            // a non-empty string value we expect this method to be called once, otherwise
            // it will never be called.
            ->method('getCancelReasonDescription')
            ->with($this->identicalTo($code))
            ->will($this->returnValue($result));

        /** @var EbayEnterprise_Order_Model_Cancel_Build_Request */
        $buildRequest = Mage::getModel('ebayenterprise_order/cancel_build_request', [
            // This key is required
            'api' => $this->_api,
            // This key is required
            'order' => $order,
            // This key is optional
            'order_helper' => $orderHelper,
        ]);

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($buildRequest, '_getReasonDescription', []));
    }
}
