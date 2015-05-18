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

class EbayEnterprise_Order_Test_Helper_FactoryTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';
	const SUMMARY_RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummaryResponse';
	const CANCEL_RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCancelResponse';
	const PAYLOAD_FACTORY_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory';

	/** @var EbayEnterprise_Order_Helper_Factory */
	protected $_factory;

	public function setUp()
	{
		parent::setUp();
		$this->_factory = Mage::helper('ebayenterprise_order/factory');
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewSearchBuildRequest()
	 * when invoked it will instantiate ebayenterprise_order/search_build_request object
	 * passing in the an array with required key 'customer_id' to the constructor method. Finally,
	 * the helper method ebayenterprise_order/factory::getNewSearchBuildRequest() will return
	 * this instantiated object.
	 */
	public function testGetNewSearchBuildRequest()
	{
		/** @var string */
		$customerId = '006512';
		/** @var null */
		$orderId = null;

		$api = $this->getMockBuilder(static::API_CLASS)
			// Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Search_Build_Request */
		$searchBuildRequest = $this->getModelMock('ebayenterprise_order/search_build_request', [], false, [[
			// This key is required
			'api' => $api,
			// This key is required
			'customer_id' => $customerId,
			// This key is optional
			'order_id' => $orderId,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/search_build_request', $searchBuildRequest);
		$this->assertSame($searchBuildRequest, $this->_factory->getNewSearchBuildRequest($api, $customerId, $orderId));
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewSearchSendRequest()
	 * when invoked it will instantiate ebayenterprise_order/search_send_request object
	 * and passing to its constructor method an array with required key 'request', mapped to an instance of
	 * type  IOrderSummaryRequest. Finally, the helper method ebayenterprise_order/factory::getNewSearchSendRequest()
	 * will return the instance of type ebayenterprise_order/search_send_request.
	 */
	public function testGetNewSearchSendRequest()
	{
		/** @var Mock_IOrderSummaryRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Search_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		$api = $this->getMockBuilder(static::API_CLASS)
			// Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Search_Send_Request */
		$searchSendRequest = $this->getModelMock('ebayenterprise_order/search_send_request', [], false, [[
			// This key is required
			'api' => $api,
			// This key is required
			'request' => $request,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/search_send_request', $searchSendRequest);
		$this->assertSame($searchSendRequest, $this->_factory->getNewSearchSendRequest($api, $request));
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewSearchProcessResponse()
	 * when invoked it will instantiate ebayenterprise_order/search_process_response object
	 * and passing to its constructor method an array with required key 'response', mapped to an instance of
	 * type  IOrderSummaryResponse. Finally, the helper method ebayenterprise_order/factory::getNewSearchProcessResponse()
	 * will return the instance of type ebayenterprise_order/search_process_response.
	 */
	public function testGetNewSearchProcessResponse()
	{
		/** @var Mock_IOrderSummaryResponse */
		$response = $this->getMockBuilder(static::SUMMARY_RESPONSE_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Search_Process_Response */
		$searchProcessResponse = $this->getModelMock('ebayenterprise_order/search_process_response', [], false, [[
			// This key is required
			'response' => $response,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/search_process_response', $searchProcessResponse);
		$this->assertSame($searchProcessResponse, $this->_factory->getNewSearchProcessResponse($response));
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewSearchProcessResponseCollection()
	 * when invoked it will instantiate ebayenterprise_order/search_process_response_collection object
	 * and return this instantiated object.
	 */
	public function testGetNewSearchProcessResponseCollection()
	{
		/** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
		$collection = $this->getModelMock('ebayenterprise_order/search_process_response_collection');
		$this->replaceByMock('model', 'ebayenterprise_order/search_process_response_collection', $collection);
		$this->assertSame($collection, $this->_factory->getNewSearchProcessResponseCollection());
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewRomOrderSearch()
	 * when invoked it will be passed in, as parameter, a string literal customer id. Then,
	 * it will proceed to instantiate ebayenterprise_order/search object
	 * passing in the an array with required key 'customer_id' to  the constructor method.
	 * Finally, the helper method ebayenterprise_order/factory::getNewRomOrderSearch()
	 * will return the ebayenterprise_order/search object.
	 */
	public function testGetNewRomOrderSearch()
	{
		/** @var string */
		$customerId = '006512';

		/** @var EbayEnterprise_Order_Model_Search */
		$search = $this->getModelMock('ebayenterprise_order/search', [], false, [[
			// This key is required
			'customer_id' => $customerId,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/search', $search);
		$this->assertSame($search, $this->_factory->getNewRomOrderSearch($customerId));
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getCurrentCustomer()
	 * when invoked it will instantiate customer/session object and then called
	 * the method customer/session::getCustomer() which will return an instance
	 * of type customer/customer. Finally, the method
	 * ebayenterprise_order/factory::getCurrentCustomer() will return this instance
	 * of type customer/customer.
	 */
	public function testGetCurrentCustomer()
	{
		/** @var Mage_Customer_Model_Customer */
		$customer = Mage::getModel('customer/customer');

		/** @var Mock_Mage_Customer_Model_Customer */
		$session = $this->getModelMockBuilder('customer/session')
			// Disabling the constructor because in order to prevent session start from starting.
			->disableOriginalConstructor()
			->setMethods(['getCustomer'])
			->getMock();
		$session->expects($this->once())
			->method('getCustomer')
			->will($this->returnValue($customer));

		$this->replaceByMock('singleton', 'customer/session', $session);
		$this->assertSame($customer, $this->_factory->getCurrentCustomer());
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewCancelBuildRequest()
	 * when invoked it will instantiate ebayenterprise_order/cancel_build_request object
	 * passing in the an array with required keys 'api' and 'order' to the constructor method. Finally,
	 * the helper method ebayenterprise_order/factory::getNewCancelBuildRequest() will return
	 * this instantiated object.
	 */
	public function testGetNewCancelBuildRequest()
	{
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');

		$api = $this->getMockBuilder(static::API_CLASS)
			// Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Cancel_Build_Request */
		$cancelBuildRequest = $this->getModelMock('ebayenterprise_order/cancel_build_request', [], false, [[
			// This key is required
			'api' => $api,
			// This key is required
			'order' => $order,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/cancel_build_request', $cancelBuildRequest);
		$this->assertSame($cancelBuildRequest, $this->_factory->getNewCancelBuildRequest($api, $order));
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewCancelSendRequest()
	 * when invoked it will instantiate ebayenterprise_order/cancel_send_request object
	 * and passing to its constructor method an array with required keys 'api' and 'request', mapped to an instance of
	 * type IBidirectionalApi and of type IOrderSummaryRequest respectively. Finally, the helper method
	 * ebayenterprise_order/factory::getNewCancelSendRequest() will return the instance of type
	 * ebayenterprise_order/cancel_send_request.
	 */
	public function testGetNewCancelSendRequest()
	{
		/** @var Mock_IOrderSummaryRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Cancel_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		$api = $this->getMockBuilder(static::API_CLASS)
			// Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Cancel_Send_Request */
		$cancelSendRequest = $this->getModelMock('ebayenterprise_order/cancel_send_request', [], false, [[
			// This key is required
			'api' => $api,
			// This key is required
			'request' => $request,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/cancel_send_request', $cancelSendRequest);
		$this->assertSame($cancelSendRequest, $this->_factory->getNewCancelSendRequest($api, $request));
	}

	/**
	 * Test that the helper method ebayenterprise_order/factory::getNewCancelProcessResponse()
	 * when invoked it will instantiate ebayenterprise_order/cancel_process_response object
	 * and passing to its constructor method an array with required key 'response', mapped to an instance of
	 * type  IOrderCancelResponse. Finally, the helper method ebayenterprise_order/factory::getNewCancelProcessResponse()
	 * will return the instance of type ebayenterprise_order/cancel_process_response.
	 */
	public function testGetNewCancelProcessResponse()
	{
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');

		/** @var Mock_IOrderCancelResponse */
		$response = $this->getMockBuilder(static::CANCEL_RESPONSE_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Cancel_Process_Response */
		$cancelProcessResponse = $this->getModelMock('ebayenterprise_order/cancel_process_response', [], false, [[
			// This key is required
			'response' => $response,
			// This key is required
			'order' => $order,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/cancel_process_response', $cancelProcessResponse);
		$this->assertSame($cancelProcessResponse, $this->_factory->getNewCancelProcessResponse($response, $order));
	}
}
