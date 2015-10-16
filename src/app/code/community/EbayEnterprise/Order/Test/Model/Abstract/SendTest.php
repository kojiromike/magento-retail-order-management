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

use \eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;

class EbayEnterprise_Order_Test_Model_Abstract_SendTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCancelResponse';
    const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';

    /** @var Mock_IBidirectionalApi */
    protected $_api;
    /** @var Mock_IOrderCancelRequest */
    protected $_payload;

    public function setUp()
    {
        parent::setUp();
        $this->_payload = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        $this->_api = $this->getMockBuilder(static::API_CLASS)
            // Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
            ->disableOriginalConstructor()
            ->setMethods(['getResponseBody'])
            ->getMock();
    }

    /**
     * Test that the method ebayenterprise_order/abstract_send::send()
     * is invoked, and it will call the method ebayenterprise_order/abstract_send::_sendRequest().
     * Finally, the method ebayenterprise_order/abstract_send::send() will return
     * an instance of type IOrderCancelResponse.
     */
    public function testSendOrderCancelRequestPayload()
    {
        /** @var Mock_IOrderCancelRequest */
        $request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Mock_IOrderCancelResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($response));

        /** @var Mock_EbayEnterprise_Order_Model_Abstract_Send */
        $cancelSendRequest = $this->getModelMock('ebayenterprise_order/abstract_send', ['_sendRequest'], true, [[
            // This key is required
            'api' => $this->_api,
            // This key is required
            'request' => $request,
        ]]);
        $cancelSendRequest->expects($this->once())
            ->method('_sendRequest')
            ->will($this->returnValue($response));
        $this->assertSame($response, $cancelSendRequest->send());
    }

    /**
     * Test that the method ebayenterprise_order/abstract_send::_sendRequest()
     * is invoked, and it will call the method IBidirectionalApi::setRequestBody()
     * will be called and passed as parameter and instance of type IOrderCancelRequest. Then, the method
     * IBidirectionalApi::send() will be called, if it doesn't throw an exception, then the method
     * IBidirectionalApi::getResponseBody() and return an instance of type IOrderCancelResponse object.
     * Finally, the method ebayenterprise_order/abstract_send::_sendRequest() will return
     * the instance of type IOrderCancelResponse.
     */
    public function testSendRequestForOrderCancelRequestPayload()
    {
        /** @var Mock_IOrderCancelRequest */
        $request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Mock_IOrderCancelResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        $api = $this->getMockBuilder(static::API_CLASS)
            // Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
            ->disableOriginalConstructor()
            ->setMethods(['setRequestBody', 'send', 'getResponseBody'])
            ->getMock();
        $api->expects($this->once())
            ->method('setRequestBody')
            ->with($this->identicalTo($request))
            ->will($this->returnSelf());
        $api->expects($this->once())
            ->method('send')
            ->will($this->returnSelf());
        $api->expects($this->once())
            ->method('getResponseBody')
            ->will($this->returnValue($response));

        /** @var Mock_EbayEnterprise_Order_Model_Abstract_Send */
        $cancelSendRequest = $this->getModelMock('ebayenterprise_order/abstract_send', ['foo', '_processException'], true, [[
            // This key is required
            'api' => $api,
            // This key is required
            'request' => $request,
        ]]);
        $cancelSendRequest->expects($this->never())
            // This method will only be called when there's an exception thrown from
            // calling the IBidirectionalApi::send() method.
            ->method('_processException');
        $this->assertSame($response, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelSendRequest, '_sendRequest', []));
    }

    /**
     * Provide exceptions that can be thrown from the SDK and the exception
     * expected to be thrown after handling the SDK exception.
     *
     * @return array
     */
    public function provideSdkExceptions()
    {
        $invalidPayload = '\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload';
        $networkError = '\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError';
        $unsupportedOperation = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation';
        $unsupportedHttpAction = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction';
        $baseException = 'Exception';
        return [
            [$invalidPayload],
            [$networkError],
            [$unsupportedOperation],
            [$unsupportedHttpAction],
            [$baseException],
        ];
    }

    /**
     * @see self::testSendRequestForOrderCancelRequestPayload()
     * Test that when the method IBidirectionalApi::send() throw an exception.
     * The method IBidirectionalApi::getResponseBody() will never be called and
     * The thrown exception is caught and null is returned.
     *
     * @param string
     * @dataProvider provideSdkExceptions
     */
    public function testSendRequestForOrderCancelRequestPayloadThrowException($exceptionType)
    {
        $exception = new $exceptionType(__METHOD__ . ': Test Exception');
        /** @var Mock_IOrderCancelRequest */
        $request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        // Mock out the logger context to prevent session hits while collection log data.
        $logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $logContext->method('getMetaData')->will($this->returnValue([]));

        $api = $this->getMockBuilder(static::API_CLASS)
            // Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
            ->disableOriginalConstructor()
            ->setMethods(['setRequestBody', 'send', 'getResponseBody'])
            ->getMock();
        $api->expects($this->once())
            ->method('setRequestBody')
            ->with($this->identicalTo($request))
            ->will($this->returnSelf());
        $api->expects($this->once())
            ->method('send')
            ->will($this->throwException($exception));
        $api->expects($this->never())
            // This method will never be called because of the
            // Exception thrown in the IBidirectionalApi::send() method.
            ->method('getResponseBody');

        /** @var Mock_EbayEnterprise_Order_Model_Abstract_Send */
        $cancelSendRequest = $this->getModelMock('ebayenterprise_order/abstract_send', [], true, [[
            // This key is required
            'api' => $api,
            // This key is required
            'request' => $request,
            'log_context' => $logContext,
        ]]);

        $this->assertNull(EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelSendRequest, '_sendRequest', []));
    }
}
