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

class EbayEnterprise_Order_Test_Model_Detail_Process_ResponseTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\OrderDetailResponse';

	/** @var Mock_IOrderDetailResponse */
	protected $_response;

	public function setUp()
	{
		parent::setUp();
		$this->_response = $this->getMockBuilder(static::RESPONSE_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();
	}

	public function providerProcessOrderDetailResponsePayload()
	{
		return [
			[$this->_response],
			[null],
		];
	}

	/**
	 * Test that the method ebayenterprise_order/detail_process_response::process()
	 * is invoked, and it will call the method ebayenterprise_order/detail_process_response::_processResponse().
	 * Finally, the method ebayenterprise_order/detail_process_response::process() will return an instance of type
	 * EbayEnterprise_Order_Model_Detail_Process_IResponse.
	 *
	 * @param IOrderDetailResponse | null
	 * @dataProvider providerProcessOrderDetailResponsePayload
	 */
	public function testProcessOrderDetailResponsePayload($response)
	{
		/** @var bool */
		$invalidResponse = is_null($response);
		/** @var Mock_EbayEnterprise_Order_Model_Detail_Process_Response */
		$detailProcessResponse = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response')
			->disableOriginalConstructor()
			->setMethods([
				'_populateOrder', '_populateOrderAddress', '_determineBillingAddress', '_determineShippingAddress',
				'_populateOrderItem', '_populateOrderPayment', '_populateOrderShipment', '_populateOrderShipGroup'
			])
			->getMock();
		// Set the class property ebayenterprise_order/detail_process_response::$_response to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($detailProcessResponse, '_response', $response);

		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			// Proving that this method will only be invoked when there's a valid IOrderDetailResponse response.
			->method('_populateOrder')
			->will($this->returnSelf());
		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			->method('_populateOrderAddress')
			->will($this->returnSelf());
		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			->method('_determineBillingAddress')
			->will($this->returnSelf());
		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			->method('_determineShippingAddress')
			->will($this->returnSelf());
		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			->method('_populateOrderItem')
			->will($this->returnSelf());
		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			->method('_populateOrderPayment')
			->will($this->returnSelf());
		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			->method('_populateOrderShipment')
			->will($this->returnSelf());
		$detailProcessResponse->expects($invalidResponse? $this->never() : $this->once())
			->method('_populateOrderShipGroup')
			->will($this->returnSelf());
		$this->assertSame($detailProcessResponse, $detailProcessResponse->process());
	}
}
