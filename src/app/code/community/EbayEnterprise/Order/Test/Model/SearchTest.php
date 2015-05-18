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

class EbayEnterprise_Order_Test_Model_SearchTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';
	const RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummaryResponse';

	/** @var Mock_IBidirectionalApi */
	protected $_api;
	/** @var string */
	protected $_apiService;
	/** @var string */
	protected $_apiOperation;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_orderCfg;
	/** EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;

	public function setUp()
	{
		parent::setUp();
		$this->_api = $this->getMockBuilder(static::API_CLASS)
			// Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
			->disableOriginalConstructor()
			->getMock();

		/** @var string */
		$this->_apiService = 'customers';
		/** @var string */
		$this->_apiOperation = 'orders/get';

		/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
		$this->_orderCfg = $this->buildCoreConfigRegistry([
			'apiSearchService' => $this->_apiService,
			'apiSearchOperation' => $this->_apiOperation,
		]);

		/** EbayEnterprise_Eb2cCore_Helper_Data */
		$this->_coreHelper = $this->getHelperMock('eb2ccore/data', ['getSdkApi']);
		$this->_coreHelper->expects($this->any())
			->method('getSdkApi')
			->with($this->identicalTo($this->_apiService), $this->identicalTo($this->_apiOperation))
			->will($this->returnValue($this->_api));
	}

	/**
	 * Test that the method ebayenterprise_order/search::process()
	 * is invoked, and it will call the method ebayenterprise_order/search::_buildRequest().
	 * Then, it will invoke the method ebayenterprise_order/search::_sendRequest() and then,
	 * it will call the method ebayenterprise_order/search::_processResponse() which will
	 * return an instance of type ebayenterprise_order/search_process_response_collection.
	 * Finally, the method ebayenterprise_order/search::process() will return
	 * this instance of type ebayenterprise_order/search_process_response_collection.
	 */
	public function testProcessOrderSearch()
	{
		/** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
		$collection = Mage::getModel('ebayenterprise_order/search_process_response_collection');
		/** @var string */
		$customerId = '006512';

		/** @var Mock_EbayEnterprise_Order_Model_Search */
		$search = $this->getModelMock('ebayenterprise_order/search', ['_buildRequest', '_sendRequest', '_processResponse'], false, [[
			// This key is required
			'customer_id' => $customerId,
			// This key is optional
			'core_helper' => $this->_coreHelper,
			// This key is optional
			'order_cfg' => $this->_orderCfg,
		]]);
		$search->expects($this->once())
			->method('_buildRequest')
			->will($this->returnSelf());
		$search->expects($this->once())
			->method('_sendRequest')
			->will($this->returnSelf());
		$search->expects($this->once())
			->method('_processResponse')
			->will($this->returnValue($collection));
		$this->assertSame($collection, $search->process());
	}

	/**
	 * Test that the method ebayenterprise_order/search::_buildRequest()
	 * is invoked, and it will called the method ebayenterprise_order/factory::getNewSearchBuildRequest()
	 * passing in the customer id and order id, which in turn will return an instance of type
	 * ebayenterprise_order/search_build_request. Then, it will invoke the method
	 * ebayenterprise_order/search_build_request::build(), which will return an instance
	 * of type IOrderSummaryRequest. This instance of type IOrderSummaryRequest will be
	 * assigned to the class property ebayenterprise_order/search::$_request.
	 * Finally, the method ebayenterprise_order/search::_buildRequest() will return itself.
	 */
	public function testOrderSearchBuildRequest()
	{
		/** @var string */
		$customerId = '006512';
		/** @var null */
		$orderId = null;

		/** @var Mock_IOrderSummaryRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Search_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Search_Build_Request */
		$searchBuildRequest = $this->getModelMock('ebayenterprise_order/search_build_request', ['build'], false, [[
			// This key is required
			'api' => $this->_api,
			// This key is required
			'customer_id' => $customerId,
			// This key is optional
			'order_id' => $orderId,
		]]);
		$searchBuildRequest->expects($this->once())
			->method('build')
			->will($this->returnValue($request));

		/** @var EbayEnterprise_Order_Helper_Factory */
		$factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewSearchBuildRequest']);
		$factory->expects($this->once())
			->method('getNewSearchBuildRequest')
			->with($this->identicalTo($this->_api), $this->identicalTo($customerId), $this->identicalTo($orderId))
			->will($this->returnValue($searchBuildRequest));

		/** @var EbayEnterprise_Order_Model_Search */
		$search = $this->getModelMock('ebayenterprise_order/search', ['foo'], false, [[
			// This key is required
			'customer_id' => $customerId,
			// This key is optional
			'factory' => $factory,
			// This key is optional
			'core_helper' => $this->_coreHelper,
			// This key is optional
			'order_cfg' => $this->_orderCfg,
		]]);
		// Set the class property ebayenterprise_order/search::$_api to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($search, '_api', $this->_api);
		// Proving that the initial state of the class property ebayenterprise_order/search::$_request is null.
		$this->assertNull(EcomDev_Utils_Reflection::getRestrictedPropertyValue($search, '_request'));
		$this->assertSame($search, EcomDev_Utils_Reflection::invokeRestrictedMethod($search, '_buildRequest', []));
		// Proving that after invoking the method ebayenterprise_order/search::_buildRequest()
		// the class property ebayenterprise_order/search::$_request is now
		// an instance of IOrderSummaryRequest.
		$this->assertSame($request, EcomDev_Utils_Reflection::getRestrictedPropertyValue($search, '_request'));
	}

	/**
	 * Test that the method ebayenterprise_order/search::_sendRequest()
	 * is invoked, and it will called the method ebayenterprise_order/factory::getNewSearchSendRequest()
	 * passing in the IOrderSummaryRequest payload object, in turn return an instance of type
	 * ebayenterprise_order/search_send_request. Then, it will invoke the method
	 * ebayenterprise_order/search_send_request::send(), which will return an instance
	 * of type IOrderSummaryResponse. This instance of type IOrderSummaryResponse will be
	 * assigned to the class property ebayenterprise_order/search::$_response.
	 * Finally, the method ebayenterprise_order/search::_sendRequest() will return itself.
	 */
	public function testOrderSearchSendRequest()
	{
		/** @var string */
		$customerId = '006512';

		/** @var Mock_IOrderSummaryRequest */
		$request = $this->getMockBuilder(EbayEnterprise_Order_Model_Search_Build_IRequest::PAYLOAD_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var Mock_IOrderSummaryResponse */
		$response = $this->getMockBuilder(static::RESPONSE_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Search_Send_Request */
		$searchSendRequest = $this->getModelMock('ebayenterprise_order/search_send_request', ['send'], false, [[
			// This key is required
			'api' => $this->_api,
			// This key is required
			'request' => $request,
		]]);
		$searchSendRequest->expects($this->once())
			->method('send')
			->will($this->returnValue($response));

		/** @var EbayEnterprise_Order_Helper_Factory */
		$factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewSearchSendRequest']);
		$factory->expects($this->once())
			->method('getNewSearchSendRequest')
			->with($this->identicalTo($this->_api), $this->identicalTo($request))
			->will($this->returnValue($searchSendRequest));

		/** @var EbayEnterprise_Order_Model_Search */
		$search = $this->getModelMock('ebayenterprise_order/search', ['foo'], false, [[
			// This key is required
			'customer_id' => $customerId,
			// This key is optional
			'factory' => $factory,
			// This key is optional
			'core_helper' => $this->_coreHelper,
			// This key is optional
			'order_cfg' => $this->_orderCfg,
		]]);
		// Set the class property ebayenterprise_order/search::$_api to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($search, '_api', $this->_api);
		// Set the class property ebayenterprise_order/search::$_request to mock of IOrderSummaryRequest.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($search, '_request', $request);
		// Proving that the initial state of the class property ebayenterprise_order/search::$_response is null.
		$this->assertNull(EcomDev_Utils_Reflection::getRestrictedPropertyValue($search, '_response'));
		$this->assertSame($search, EcomDev_Utils_Reflection::invokeRestrictedMethod($search, '_sendRequest', []));
		// Proving that after invoking the method ebayenterprise_order/search::_sendRequest()
		// the class property ebayenterprise_order/search::$_response is now
		// an instance of IOrderSummaryResponse.
		$this->assertSame($response, EcomDev_Utils_Reflection::getRestrictedPropertyValue($search, '_response'));
	}

	/**
	 * Test that the method ebayenterprise_order/search::_processResponse()
	 * is invoked, and it will called the method ebayenterprise_order/factory::getNewSearchSendRequest()
	 * passing in the IOrderSummaryResponse payload object, in turn will return an instance of type
	 * ebayenterprise_order/search_process_response. Then, it will invoke the method
	 * ebayenterprise_order/search_process_response::process(), which will return an instance of type
	 * EbayEnterprise_Order_Model_Search_Process_Response_ICollection. Finally, the method
	 * ebayenterprise_order/search::_processResponse() will return this instance of type
	 * EbayEnterprise_Order_Model_Search_Process_Response_ICollection.
	 */
	public function testOrderSearchProcessResponse()
	{
		/** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
		$collection = Mage::getModel('ebayenterprise_order/search_process_response_collection');
		/** @var string */
		$customerId = '006512';

		/** @var Mock_IOrderSummaryResponse */
		$response = $this->getMockBuilder(static::RESPONSE_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var EbayEnterprise_Order_Model_Search_Process_Response */
		$searchProcessResponse = $this->getModelMock('ebayenterprise_order/search_process_response', ['process'], false, [[
			// This key is required
			'response' => $response,
		]]);
		$searchProcessResponse->expects($this->once())
			->method('process')
			->will($this->returnValue($collection));

		/** @var EbayEnterprise_Order_Helper_Factory */
		$factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewSearchProcessResponse']);
		$factory->expects($this->once())
			->method('getNewSearchProcessResponse')
			->with($this->identicalTo($response))
			->will($this->returnValue($searchProcessResponse));

		/** @var EbayEnterprise_Order_Model_Search */
		$search = $this->getModelMock('ebayenterprise_order/search', ['foo'], false, [[
			// This key is required
			'customer_id' => $customerId,
			// This key is optional
			'factory' => $factory,
			// This key is optional
			'core_helper' => $this->_coreHelper,
			// This key is optional
			'order_cfg' => $this->_orderCfg,
		]]);
		// Set the class property ebayenterprise_order/search::$_api to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($search, '_api', $this->_api);
		// Set the class property ebayenterprise_order/search::$_response to the mock IOrderSummaryResponse object.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($search, '_response', $response);

		$this->assertSame($collection, EcomDev_Utils_Reflection::invokeRestrictedMethod($search, '_processResponse', []));
	}
}
