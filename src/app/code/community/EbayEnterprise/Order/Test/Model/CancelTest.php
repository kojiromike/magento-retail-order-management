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

class EbayEnterprise_Order_Test_Model_CancelTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	const RESPONSE_CLASS = 'eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCancelResponse';

	/**
	 * Test that the method ebayenterprise_order/cancel::process()
	 * is invoked, and it will call the method ebayenterprise_order/cancel::_buildRequest().
	 * Then, it will invoke the method ebayenterprise_order/cancel::_sendRequest() and then,
	 * it will call the method ebayenterprise_order/cancel::_processResponse().
	 * Finally, the method ebayenterprise_order/cancel::process() will return
	 * itself.
	 */
	public function testProcessOrderCancel()
	{
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');

		/** @var Mock_EbayEnterprise_Order_Model_Cancel */
		$cancel = $this->getModelMock('ebayenterprise_order/cancel', ['_buildRequest', '_sendRequest', '_processResponse'], false, [[
			// This key is required
			'order' => $order,
		]]);
		$cancel->expects($this->once())
			->method('_buildRequest')
			->will($this->returnSelf());
		$cancel->expects($this->once())
			->method('_sendRequest')
			->will($this->returnSelf());
		$cancel->expects($this->once())
			->method('_processResponse')
			->will($this->returnSelf());
		$this->assertSame($cancel, $cancel->process());
	}

	/**
	 * Test that the method ebayenterprise_order/cancel::_buildRequest()
	 * is invoked, and it will instantiate the class ebayenterprise_order/cancel_build_request
	 * passing to its constructor method an array with key 'order' mapped to a
	 * sales/order object. Then, it will invoke the method
	 * ebayenterprise_order/cancel_build_request::build(), which will return an instance
	 * of type IOrderCancelRequest. This instance of type IOrderCancelRequest will be
	 * assigned to the class property ebayenterprise_order/cancel::$_request.
	 * Finally, the method ebayenterprise_order/cancel::_buildRequest() will return itself.
	 */
	public function testOrderCancelBuildRequest()
	{
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');

		/** @var Mock_IOrderCancelRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Cancel_Build_Request */
		$cancelBuildRequest = $this->getModelMock('ebayenterprise_order/cancel_build_request', ['build'], false, [[
			// This key is required
			'order' => $order,
		]]);
		$cancelBuildRequest->expects($this->once())
			->method('build')
			->will($this->returnValue($request));
		$this->replaceByMock('model', 'ebayenterprise_order/cancel_build_request', $cancelBuildRequest);

		/** @var EbayEnterprise_Order_Model_Cancel */
		$cancel = Mage::getModel('ebayenterprise_order/cancel', [
			// This key is required
			'order' => $order,
		]);
		// Proving that initial state of the class property ebayenterprise_order/cancel::$_request is null.
		$this->assertNull(EcomDev_Utils_Reflection::getRestrictedPropertyValue($cancel, '_request'));
		$this->assertSame($cancel, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancel, '_buildRequest', []));
		// Proving that after invoking the method ebayenterprise_order/cancel::_buildRequest()
		// the class property ebayenterprise_order/cancel::$_request is now
		// an instance of IOrderCancelRequest.
		$this->assertSame($request, EcomDev_Utils_Reflection::getRestrictedPropertyValue($cancel, '_request'));
	}

	/**
	 * Test that the method ebayenterprise_order/cancel::_sendRequest()
	 * is invoked, and it will instantiate the class ebayenterprise_order/cancel_send_request
	 * passing to its constructor method an array with key 'request' mapped to a
	 * IOrderCancelRequest payload object. Then, it will invoke the method
	 * ebayenterprise_order/cancel_send_request::send(), which will return an instance
	 * of type IOrderCancelResponse. This instance of type IOrderCancelResponse will be
	 * assigned to the class property ebayenterprise_order/cancel::$_response.
	 * Finally, the method ebayenterprise_order/cancel::_sendRequest() will return itself.
	 */
	public function testOrderCancelSendRequest()
	{
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');

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

		/** @var EbayEnterprise_Order_Model_Cancel_Send_Request */
		$cancelSendRequest = $this->getModelMock('ebayenterprise_order/cancel_send_request', ['send'], false, [[
			// This key is required
			'request' => $request,
		]]);
		$cancelSendRequest->expects($this->once())
			->method('send')
			->will($this->returnValue($response));
		$this->replaceByMock('model', 'ebayenterprise_order/cancel_send_request', $cancelSendRequest);

		/** @var EbayEnterprise_Order_Model_Cancel */
		$cancel = Mage::getModel('ebayenterprise_order/cancel', [
			// This key is required
			'order' => $order,
		]);
		// Proving that initial state of the class property ebayenterprise_order/cancel::$_response is null.
		$this->assertNull(EcomDev_Utils_Reflection::getRestrictedPropertyValue($cancel, '_response'));
		$this->assertSame($cancel, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancel, '_sendRequest', []));
		// Proving that after invoking the method ebayenterprise_order/cancel::_sendRequest()
		// the class property ebayenterprise_order/cancel::$_response is now
		// an instance of IOrderCancelResponse.
		$this->assertSame($response, EcomDev_Utils_Reflection::getRestrictedPropertyValue($cancel, '_response'));
	}

	/**
	 * Test that the method ebayenterprise_order/cancel::_processResponse()
	 * is invoked, and it will instantiate the class ebayenterprise_order/cancel_process_response
	 * passing to its constructor method an array with key 'response' mapped to a
	 * OrderCancelResponse payload object and another key 'order' mapped to a sales/order object.
	 * Then, it will invoke the method ebayenterprise_order/cancel_process_response::process().
	 * Finally, the method ebayenterprise_order/cancel::_processResponse() will return itself.
	 */
	public function testOrderCancelProcessResponse()
	{
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');

		/** @var Mock_OrderCancelResponse */
		$response = $this->getMockBuilder(static::RESPONSE_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Cancel_Send_Request */
		$cancelProcessResponse = $this->getModelMock('ebayenterprise_order/cancel_process_response', ['process'], false, [[
			// This key is required
			'response' => $response,
			// This key is required
			'order' => $order,
		]]);
		$cancelProcessResponse->expects($this->once())
			->method('process')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'ebayenterprise_order/cancel_process_response', $cancelProcessResponse);

		/** @var EbayEnterprise_Order_Model_Cancel */
		$cancel = Mage::getModel('ebayenterprise_order/cancel', [
			// This key is required
			'order' => $order,
		]);
		$this->assertSame($cancel, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancel, '_processResponse', []));
	}
}
