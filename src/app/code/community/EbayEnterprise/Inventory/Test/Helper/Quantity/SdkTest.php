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

use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;

class EbayEnterprise_Inventory_Test_Helper_Quantity_SdkTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Inventory_Helper_Quantity_Sdk */
    protected $_sdkHelper;
    /** @var eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi */
    protected $_api;
    /** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest */
    protected $_emptyRequestBody;
    /** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest */
    protected $_completeRequestBody;
    /** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityReply */
    protected $_responseBody;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $_inventoryHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_inventoryConfig;
    /** @var string Name of the API service for quantity. Mocked config value. */
    protected $_apiService = 'inventory';
    /** @var string Name of the API operation for quantity. Mocked config value. */
    protected $_apiOperation = 'quantity';
    /** @var EbayEnterprise_Inventory_Helper_Quantity_Factory */
    protected $_inventoryFactory;
    /** @var EbayEnterprise_Inventory_Model_Quantity_Request_Builder */
    protected $_requestBuilder;
    /** @var EbayEnterprise_Inventory_Model_Quantity_Response_Parser */
    protected $_responseParser;
    /** @var Mage_Sales_Model_Quote_Item[] */
    protected $_items;
    /** @var string General exception thrown by the SDK helper when exceptions from the SDK are encountered. */
    protected $_inventoryCollectorException = 'EbayEnterprise_Inventory_Exception_Quantity_Collector_Exception';

    public function setUp()
    {
        $logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));

        $this->_api = $this->getMockForAbstractClass(
            'eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi'
        );

        // Create two request bodies - one to serve as the "empty" request
        // body that needs to be populated with data and one to serve as
        // the "complete" payload that has been populated with data.
        $this->_emptyRequestBody = $this->getMockForAbstractClass(
            'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest'
        );
        $this->_completeRequestBody = $this->getMockForAbstractClass(
            'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest'
        );

        $this->_responseBody = $this->getMockForAbstractClass(
            'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityReply'
        );

        $this->_coreHelper = $this->getHelperMock('ebayenterprise_eb2ccore', ['getSdkApi']);

        $this->_inventoryConfig = $this->buildCoreConfigRegistry([
            'apiService' => $this->_apiService,
            'quantityApiOperation' => $this->_apiOperation,
        ]);

        $this->_inventoryHelper = $this->getHelperMock('ebayenterprise_inventory/data', ['__']);
        $this->_inventoryHelper->expects($this->any())
            ->method('__')
            ->will($this->returnArgument(0));

        $this->_inventoryFactory = $this->getHelperMock(
            'ebayenterprise_inventory/quantity_factory',
            ['createRequestBuilder', 'createResponseParser', 'createQuantityResults']
        );

        $this->_requestBuilder = $this->getModelMockBuilder('ebayenterprise_inventory/quantity_request_builder')
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $this->_responseParser = $this->getModelMockBuilder('ebayenterprise_inventory/quantity_response_parser')
            ->disableOriginalConstructor()
            ->setMethods(['getQuantityResults'])
            ->getMock();

        $this->_items = [$this->getModelMock('sales/quote_item')];

        $this->_sdkHelper = Mage::helper('ebayenterprise_inventory/quantity_sdk');
        // As helpers do not support constructor injection, inject
        // dependencies by directly setting the class properties.
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $this->_sdkHelper,
            [
                '_coreHelper' => $this->_coreHelper,
                '_inventoryHelper' => $this->_inventoryHelper,
                '_inventoryConfig' => $this->_inventoryConfig,
                '_logContext' => $logContext,
                '_inventoryQuantityFactory' => $this->_inventoryFactory,
            ]
        );
    }

    /**
     * Test that a properly configured SDK API is created for making
     * the inventory quantity request.
     */
    public function testGetSdkApi()
    {
        // Allow the core helper to provide the correct API object
        // if given the appropriate configuration values for the
        // API service and operation.
        $this->_coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->with($this->identicalTo($this->_apiService), $this->identicalTo($this->_apiOperation))
            ->will($this->returnValue($this->_api));

        $this->assertSame(
            $this->_api,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_sdkHelper, '_getSdkApi')
        );
    }

    /**
     * Test that the API gets populated with the apporpriate request
     * payload when preparing to make the inventory quantity request.
     */
    public function testPrepareRequestSuccess()
    {
        // Expect that the API will provide the empty request body
        // that will need to be populated to make the request.
        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($this->_emptyRequestBody));
        // The api then needs to be given the complete request body.
        $this->_api->expects($this->once())
            ->method('setRequestBody')
            ->with($this->identicalTo($this->_completeRequestBody))
            ->will($this->returnSelf());
        // Not exactly require that the inventory quantity factory be used to
        // get the request builder, but if it is used, it must be given the
        // apporpriate payload and quote object.
        $this->_inventoryFactory->expects($this->any())
            ->method('createRequestBuilder')
            ->with($this->identicalTo($this->_emptyRequestBody), $this->identicalTo($this->_items))
            ->will($this->returnValue($this->_requestBuilder));
        // Allow the quote request builder to be used to provide the
        // response object needed by the API.
        $this->_requestBuilder->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->_completeRequestBody));

        // Mostly superfluous to ensure the method returns "self" (easy
        // enough to prove just be looking at the method) but easy enough
        // to test the it provides the apporpriate return value to test it anyway.
        $this->assertSame(
            $this->_sdkHelper,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $this->_sdkHelper,
                '_prepareRequest',
                [$this->_api, $this->_items]
            )
        );
    }

    /**
     * If the request payload cannot be created - due to the inventory/quantity operation
     * not being supported by the SDK, the SDK exception should be caught
     * and a more general exception thrown instead.
     */
    public function testPrepareRequestFailure()
    {
        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->throwException(new UnsupportedOperation));
        $this->setExpectedException($this->_inventoryCollectorException);
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_sdkHelper,
            '_prepareRequest',
            [$this->_api, $this->_items]
        );
    }

    /**
     * When sending a request, the SDK API should be used to send the
     * quantity request.
     */
    public function testSendApiRequestSuccess()
    {
        // Really just need to ensure that the request is sent
        // via the SDK API. In the success case, not much else
        // happens here.
        $this->_api->expects($this->once())
            ->method('send')
            ->will($this->returnSelf());

        $this->assertSame(
            $this->_sdkHelper,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $this->_sdkHelper,
                '_sendApiRequest',
                [$this->_api]
            )
        );
    }

    /**
     * Provide exceptions to be thrown by the SDK API when sending
     * quantity requests.
     */
    public function provideSdkSendExceptions()
    {
        return [
            [new NetworkError],
            [new InvalidPayload],
            [new UnsupportedOperation],
            [new UnsupportedHttpAction],
            [new Exception],
        ];
    }

    /**
     * When the API SDK request fails, exceptions from the SDK should
     * be caught and more general, expected exceptions should be thrown
     * for Magento/Inventory module to handle appropriately.
     *
     * @param Exception $sdkException Exception to be thrown by the api.
     * @dataProvider provideSdkSendExceptions
     */
    public function testSendApiRequestErrorHandling(Exception $sdkException)
    {
        $this->_api->expects($this->once())
            ->method('send')
            ->will($this->throwException($sdkException));

        $this->setExpectedException($this->_inventoryCollectorException);
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_sdkHelper,
            '_sendApiRequest',
            [$this->_api]
        );
    }

    /**
     * When extracting results from the SDK response, a quantity
     * result model with the quantity data extracted from the
     * response should be returned.
     */
    public function testExtractResponseResultsSuccess()
    {
        // Create an expected result, should be returned when successfully
        // extracting results from a response.
        $sdkResult = $this->getModelMockBuilder('ebayenterprise_inventory/quantity_results')->disableOriginalConstructor()->getMock();

        // Mock the API to return an expected response body as the
        // reply to the SDK request.
        $this->_api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($this->_responseBody));

        // Mock up some SDK results - doesn't really matter what these
        // are, just that they can be recognized as the "right" results.
        $quantityRecords = [$this->getModelMockBuilder('ebayenteprirse_inventory/quantity')->disableOriginalConstructor()->getMock()];

        // Create a quote response parser capable of returning the
        // proper results from the quantity response.
        $this->_responseParser->expects($this->any())
            ->method('getQuantityResults')
            ->will($this->returnValue($quantityRecords));

        // Setup the quantity factory to be able to provide the proper quote
        // response parser if given the correct response payload and quote.
        $this->_inventoryFactory->expects($this->any())
            ->method('createResponseParser')
            ->with($this->identicalTo($this->_responseBody))
            ->will($this->returnValue($this->_responseParser));
        // Setup the quantity factory to be able to provide the proper SDK result
        // if given the expected quantity data.
        $this->_inventoryFactory->expects($this->any())
            ->method('createQuantityResults')
            ->with($this->identicalTo($quantityRecords))
            ->will($this->returnValue($sdkResult));

        // Ensure that the correct SDK result is returned when extracting
        // results from the SDK. For now, this will return the exact same
        // instance as is expected. In the future this may not be the case and
        // test could be expected to simply ensure the results available in
        // the returned result model match the expected results.
        $this->assertSame(
            $sdkResult,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $this->_sdkHelper,
                '_extractResponseResults',
                [$this->_api, $this->_items]
            )
        );
    }

    /**
     * When the SDK throws an exception while getting the response body,
     * the SDK exception should be caught and a more generic exception
     * that can be expected to be caught (by Magento or Inventory module)
     * should be thrown.
     */
    public function testExtractResponseResultsFailure()
    {
        $this->_api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->throwException(new UnsupportedOperation));

        $this->setExpectedException($this->_inventoryCollectorException);
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_sdkHelper,
            '_extractResponseResults',
            [$this->_api, $this->_items]
        );
    }
}
