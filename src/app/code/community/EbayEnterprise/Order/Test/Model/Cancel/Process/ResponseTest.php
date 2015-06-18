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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCancelResponse;

class EbayEnterprise_Order_Test_Model_Cancel_Process_ResponseTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const RESPONSE_CLASS = 'eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCancelResponse';

    /**
     * Test that the method ebayenterprise_order/cancel_process_response::process()
     * is invoked, and it will call the method ebayenterprise_order/cancel_process_response::_processResponse().
     * Finally, the method ebayenterprise_order/cancel_process_response::process() will return itself.
     */
    public function testProcessOrderCancelResponsePayload()
    {
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var Mock_IOrderCancelResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Cancel_Process_Response */
        $cancelProcessResponse = $this->getModelMock('ebayenterprise_order/cancel_process_response', ['_processResponse'], false, [[
            // This key is required
            'response' => $response,
            // This key is required
            'order' => $order,
        ]]);
        $cancelProcessResponse->expects($this->once())
            ->method('_processResponse')
            ->will($this->returnSelf());
        $this->assertSame($cancelProcessResponse, $cancelProcessResponse->process());
    }

    /**
     * @return array
     */
    public function providerProcessOrderCancelResponsePayload()
    {
        return [
            ['CANCELLED', true],
            ['PENDING', false],
        ];
    }

    /**
     * Test that the method ebayenterprise_order/cancel_process_response::_processResponse()
     * is invoked, and it will call the method IOrderCancelResponse::getResponseStatus().
     * If the method IOrderCancelResponse::getResponseStatus() return a value that match
     * the class constant EbayEnterprise_Order_Model_Cancel_Process_Response::CANCELLED_RESPONSE,
     * then it will call the following methods sales/order::cancel(), and sales/order::save().
     * However, If the method IOrderCancelResponse::getResponseStatus() return a value that doesn't
     * match the class constant EbayEnterprise_Order_Model_Cancel_Process_Response::CANCELLED_RESPONSE,
     * then it will call this method bayenterprise_order/cancel_process_response::_logResponse().
     * Finally, the method ebayenterprise_order/cancel_process_response::_processResponse() will
     * return itself.
     *
     * @param string
     * @param bool
     * @dataProvider providerProcessOrderCancelResponsePayload
     */
    public function testProcessResponseForOrderCancelResponsePayload($responseStatus, $isCancelable)
    {
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var Mock_IOrderCancelResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->setMethods(['getResponseStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('getResponseStatus')
            ->will($this->returnValue($responseStatus));

        /** @var EbayEnterprise_Order_Model_Cancel_Process_Response */
        $cancelProcessResponse = $this->getModelMock('ebayenterprise_order/cancel_process_response', ['_logResponse', '_cancelOrder'], false, [[
            // This key is required
            'response' => $response,
            // This key is required
            'order' => $order,
        ]]);
        $cancelProcessResponse->expects($isCancelable ? $this->never() : $this->once())
            ->method('_logResponse')
            ->will($this->returnSelf());
        $cancelProcessResponse->expects($isCancelable ? $this->once() : $this->never())
            ->method('_cancelOrder')
            ->will($this->returnSelf());
        $this->assertSame($cancelProcessResponse, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelProcessResponse, '_processResponse', []));
    }

    /**
     * Test that the method ebayenterprise_order/cancel_process_response::_logResponse()
     * is invoked, and it will call the method IOrderCancelResponse::getResponseStatus() and
     * its return message will be appended to the log message that will be passed as first parameter
     * to calling the method ebayenterprise_magelog/data::warning() and also the return value
     * from the calling the method ebayenterprise_magelog/context::getMetaData() passing its class name
     * will the second parameter to the method ebayenterprise_magelog/data::warning().
     * Finally, the method ebayenterprise_order/cancel_process_response::_logResponse() will return itself.
     */
    public function testLogResponseForOrderCancelResponsePayload()
    {
        $responseStatus = 'PENDING';
        $class = 'EbayEnterprise_Order_Model_Cancel_Process_Response';
        /** @var array */
        $context = [];
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');

        /** @var EbayEnterprise_MageLog_Helper_Data */
        $logger = $this->getHelperMock('ebayenterprise_magelog', ['warning']);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->isType('string'), $this->identicalTo($context))
            ->will($this->returnValue(null));

        /** @var EbayEnterprise_MageLog_Helper_Context */
        $logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $logContext->expects($this->once())
            ->method('getMetaData')
            ->with($this->identicalTo($class), $this->identicalTo([]), $this->identicalTo(null))
            ->will($this->returnValue($context));

        /** @var Mock_IOrderCancelResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->setMethods(['getResponseStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('getResponseStatus')
            ->will($this->returnValue($responseStatus));

        /** @var EbayEnterprise_Order_Model_Cancel_Process_Response */
        $cancelProcessResponse = Mage::getModel('ebayenterprise_order/cancel_process_response', [
            // This key is required
            'response' => $response,
            // This key is required
            'order' => $order,
            // This key is optional
            'logger' => $logger,
            // This key is optional
            'log_context' => $logContext,
        ]);
        $this->assertSame($cancelProcessResponse, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelProcessResponse, '_logResponse', []));
    }

    /**
     * @return array
     */
    public function providerCancelOrder()
    {
        return [
            [7],
            [null]
        ];
    }

    /**
     * Test that the method ebayenterprise_order/cancel_process_response::_cancelOrder()
     * is invoked, and it will call the method sales/order::getId(), if it returns non-zero
     * integer value, then the methods sales/order::cancel() and sales/order::save() will be
     * called in the sales/order object. Otherwise, only the sales/order::setState() will
     * be invoked and passed in class constant Mage_Sales_Model_Order::STATE_CANCELED.
     * Finally, the method ebayenterprise_order/cancel_process_response::_cancelOrder() will
     * return itself.
     *
     * @param int | null
     * @dataProvider providerCancelOrder
     */
    public function testCancelOrder($id)
    {
        /** @var Mock_Mage_Sales_Model_Order */
        $order = $this->getModelMock('sales/order', ['cancel', 'save', 'getId', 'setState']);
        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));
        $order->expects($id ? $this->once() : $this->never())
            ->method('cancel')
            ->will($this->returnSelf());
        $order->expects($id ? $this->once() : $this->never())
            ->method('save')
            ->will($this->returnSelf());
        $order->expects($id ? $this->never() : $this->once())
            ->method('setState')
            ->with($this->identicalTo(Mage_Sales_Model_Order::STATE_CANCELED))
            ->will($this->returnSelf());

        /** @var Mock_IOrderCancelResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Cancel_Process_Response */
        $cancelProcessResponse = $this->getModelMock('ebayenterprise_order/cancel_process_response', ['foo'], false, [[
            // This key is required
            'response' => $response,
            // This key is required
            'order' => $order,
        ]]);
        $this->assertSame($cancelProcessResponse, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelProcessResponse, '_cancelOrder', []));
    }
}
