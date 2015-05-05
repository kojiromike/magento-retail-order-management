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

class EbayEnterprise_Order_Test_Model_Cancel_Build_RequestTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
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
		/** @var Mock_IOrderCancelRequest */
		$payload = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var Mock_EbayEnterprise_Order_Model_Cancel_Build_Request */
		$buildRequest = $this->getModelMock('ebayenterprise_order/cancel_build_request', array('_buildPayload'), false, array(array(
			// This key is optional
			'payload' => $payload,
			// This key is required
			'order' => $order,
		)));
		$buildRequest->expects($this->once())
			->method('_buildPayload')
			->will($this->returnSelf());
		$this->assertSame($payload, $buildRequest->build());
	}

	/**
	 * Test that the method EbayEnterprise_Order_Model_Cancel_Build_Request::_buildPayload()
	 * is invoked, and it will call the method EbayEnterprise_Order_Model_Cancel_Build_Request::_buildPayload().
	 * Finally, the method EbayEnterprise_Order_Model_Cancel_Build_Request::build() will return
	 * an instance of type IOrderCancelRequest.
	 */
	public function testBuildPayloadForOrderCancelRequest()
	{
		/** @var string */
		$incrementId = '1000000783731';
		/** @var string */
		$reasonCode = uniqid('OCR-');

		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order', array('increment_id' => $incrementId));
		/** @var Mock_IOrderCancelRequest */
		$payload = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->setMethods(array('setOrderType', 'setCustomerOrderId', 'setReasonCode'))
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

		/** @var Mock_EbayEnterprise_Order_Model_Cancel_Build_Request */
		$buildRequest = $this->getModelMock('ebayenterprise_order/cancel_build_request', array('_generateReasonCode'), false, array(array(
			// This key is optional
			'payload' => $payload,
			// This key is required
			'order' => $order,
		)));
		$buildRequest->expects($this->once())
			->method('_generateReasonCode')
			->will($this->returnValue($reasonCode));
		$this->assertSame($buildRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($buildRequest, '_buildPayload', array()));
	}
}
