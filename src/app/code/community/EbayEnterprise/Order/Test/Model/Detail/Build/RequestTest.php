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

use eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\OrderDetailRequest;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Order_Test_Model_Detail_Build_RequestTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const PAYLOAD_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\OrderDetailRequest';
	const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';

	/** @var Mock_IBidirectionalApi */
	protected $_api;
	/** @var Mock_IOrderDetailRequest */
	protected $_payload;

	public function setUp()
	{
		parent::setUp();
		$this->_payload = $this->getMockBuilder(static::PAYLOAD_CLASS)
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
	 * Test that the method ebayenterprise_order/detail_build_request::build()
	 * is invoked, and it will call the method ebayenterprise_order/detail_build_request::_buildPayload().
	 * Finally, the method ebayenterprise_order/detail_build_request::build() will return
	 * an instance of type IOrderDetailRequest.
	 */
	public function testBuildOrderDetailRequestPayload()
	{
		/** @var string */
		$orderId = '1000049499939393881';

		$this->_api->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($this->_payload));

		/** @var Mock_EbayEnterprise_Order_Model_Detail_Build_Request */
		$buildRequest = $this->getModelMock('ebayenterprise_order/detail_build_request', ['_buildPayload'], false, [[
			// This key is required
			'order_id' => $orderId,
			// This key is required
			'api' => $this->_api,
		]]);
		$buildRequest->expects($this->once())
			->method('_buildPayload')
			->will($this->returnSelf());
		$this->assertSame($this->_payload, $buildRequest->build());
	}

	/**
	 * Test that the method ebayenterprise_order/detail_build_request::_buildPayload()
	 * is invoked, and it will call the method IOrderDetailRequest::setOrderType() and passed in
	 * the class constant EbayEnterprise_Order_Model_Detail_Build_IRequest::DEFAULT_ORDER_DETAIL_SEARCH_TYPE.
	 * Then, the method IOrderDetailRequest::setCustomerOrderId() will be invoked and passed in
	 * the order id. Finally, the method ebayenterprise_order/detail_build_request::_buildPayload() will
	 * return itself.
	 */
	public function testBuildPayloadForOrderDetailRequest()
	{
		/** @var string */
		$orderId = '1000049499939393881';

		/** @var Mock_IOrderDetailRequest */
		$payload = $this->getMockBuilder(static::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->setMethods(['setOrderType', 'setCustomerOrderId'])
			->getMock();
		$payload->expects($this->once())
			->method('setOrderType')
			->with($this->identicalTo(EbayEnterprise_Order_Model_Detail_Build_IRequest::DEFAULT_ORDER_DETAIL_SEARCH_TYPE))
			->will($this->returnSelf());
		$payload->expects($this->once())
			->method('setCustomerOrderId')
			->with($this->identicalTo($orderId))
			->will($this->returnSelf());

		$this->_api->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($payload));

		/** @var Mock_EbayEnterprise_Order_Model_Detail_Build_Request */
		$buildRequest = $this->getModelMock('ebayenterprise_order/detail_build_request', ['foo'], false, [[
			// This key is required
			'order_id' => $orderId,
			// This key is required
			'api' => $this->_api,
		]]);

		$this->assertSame($buildRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($buildRequest, '_buildPayload', []));
	}
}
