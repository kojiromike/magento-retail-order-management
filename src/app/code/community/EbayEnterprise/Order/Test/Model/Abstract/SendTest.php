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

class EbayEnterprise_Order_Test_Model_Abstract_SendTest
	extends EbayEnterprise_Eb2cCore_Test_Base
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
	 * @see self::testSendRequestForOrderCancelRequestPayload()
	 * Test that when the method IBidirectionalApi::send() throw an exception.
	 * The method IBidirectionalApi::getResponseBody() will never be called and
	 * the method ebayenterprise_order/abstract_send::_processException()
	 * will be called, passing in as parameter the Exception object.
	 * Finally, the method ebayenterprise_order/abstract_send::_sendRequest() will return null.
	 */
	public function testSendRequestForOrderCancelRequestPayloadThrowException()
	{
		$exception = Mage::exception('Mage_Core', 'Simulating order cancel api exception');
		/** @var Mock_IOrderCancelRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();
		/** @var null */
		$response = null;

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
		$cancelSendRequest = $this->getModelMock('ebayenterprise_order/abstract_send', ['_processException'], true, [[
			// This key is required
			'api' => $api,
			// This key is required
			'request' => $request,
		]]);
		$cancelSendRequest->expects($this->once())
			// This method will finally be called because of the exception
			// thrown in the IBidirectionalApi::send() method.
			->method('_processException')
			->with($this->identicalTo($exception))
			->will($this->returnSelf());
		$this->assertSame($response, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelSendRequest, '_sendRequest', []));
	}

	/**
	 * @return array
	 */
	public function providerProcessExceptionForOrderCancelRequestPayload()
	{
		return [
			[new NetworkError()],
			[new UnsupportedOperation()],
			[new UnsupportedHttpAction()],
			[Mage::exception('Mage_Core', '')],
		];
	}

	/**
	 * Test that the method ebayenterprise_order/abstract_send::_processException()
	 * is invoked, and it will be passed an instance that inherit from the Exception class.
	 * When the parameter passed to the method ebayenterprise_order/abstract_send::_processException()
	 * is of type UnsupportedOperation or UnsupportedHttpAction we expect the helper class method
	 * ebayenterprise_magelog/data::critical() to be called a passed in as first parameter a string
	 * and passed in as second parameter an array. However, the passed in parameter to the method
	 * ebayenterprise_order/abstract_send::_processException() is not of type UnsupportedOperation
	 * or UnsupportedHttpAction, then the method ebayenterprise_magelog/data::warning() will be called
	 * passing as first parameter a string value and as second parameter an array. Finally, the method
	 * ebayenterprise_order/abstract_send::_processException() will return itself.
	 *
	 * @param Exception
	 * @dataProvider providerProcessExceptionForOrderCancelRequestPayload
	 */
	public function testProcessExceptionForOrderCancelRequestPayload(Exception $e)
	{
		/** @var array */
		$context = [];
		/** @var bool */
		$isCritcal = ($e instanceof UnsupportedOperation || $e instanceof UnsupportedHttpAction);
		/** @var EbayEnterprise_MageLog_Helper_Data */
		$logger = $this->getHelperMock('ebayenterprise_magelog', ['warning', 'critical']);
		$logger->expects($isCritcal ? $this->never() : $this->once())
			->method('warning')
			->with($this->isType('string'), $this->identicalTo($context))
			->will($this->returnValue(null));
		$logger->expects($isCritcal ? $this->once() : $this->never())
			->method('critical')
			->with($this->isType('string'), $this->identicalTo($context))
			->will($this->returnValue(null));

		/** @var Mock_IOrderCancelRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var Mock_EbayEnterprise_Order_Model_Abstract_Send */
		$cancelSendRequest = $this->getModelMock('ebayenterprise_order/abstract_send', ['_getLogContext'], true, [[
			// This key is required
			'api' => $this->_api,
			// This key is required
			'request' => $request,
			// This key is optional
			'logger' => $logger,
		]]);
		$cancelSendRequest->expects($this->once())
			->method('_getLogContext')
			->with($this->identicalTo($e))
			->will($this->returnValue($context));
		$this->assertSame($cancelSendRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelSendRequest, '_processException', [$e]));
	}

	/**
	 * Test that the method ebayenterprise_order/abstract_send::_getLogContext()
	 * is invoked, and it will be passed an instance of Mage_Core_Exception. It will
	 * then invoke the method ebayenterprise_magelog/context::getMetaData(), passing in
	 * as first parameter a string of its class name, as second parameter it will passed,
	 * an empty array, and as its third parameter it will passed in an instance of
	 * Mage_Core_Exception. The method ebayenterprise_magelog/context::getMetaData() will
	 * return an array. Finally, the method ebayenterprise_order/abstract_send::_getLogContext()
	 * will return that array.
	 */
	public function testGetLogContextForOrderCancelRequestPayload()
	{
		/** @var Mage_Core_Exception */
		$exception = Mage::exception('Mage_Core', '');
		/* @var array */
		$context = [];
		/* @var string */
		$class = 'EbayEnterprise_Order_Model_Abstract_Send';
		/** @var EbayEnterprise_MageLog_Helper_Context */
		$logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
		$logContext->expects($this->once())
			->method('getMetaData')
			->with($this->identicalTo($class), $this->identicalTo([]), $this->identicalTo($exception))
			->will($this->returnValue($context));

		/** @var Mock_IOrderCancelRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var Mock_EbayEnterprise_Order_Model_Abstract_Send */
		$cancelSendRequest = $this->getModelMock('ebayenterprise_order/abstract_send', ['foo'], true, [[
			// This key is required
			'api' => $this->_api,
			// This key is required
			'request' => $request,
			// This key is optional
			'log_context' => $logContext,
		]]);
		$this->assertSame($context, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancelSendRequest, '_getLogContext', [$exception]));
	}
}
