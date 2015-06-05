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

use eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummaryRequest;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Order_Test_Model_Search_Build_RequestTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const SUB_PAYLOAD_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSearch';
    const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';

    /** @var Mock_IBidirectionalApi */
    protected $_api;
    /** @var Mock_IOrderCancelRequest */
    protected $_payload;

    public function setUp()
    {
        parent::setUp();
        $this->_payload = $this->getMockBuilder(EbayEnterprise_Order_Model_Search_Build_IRequest::PAYLOAD_CLASS)
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
     * Test that the method ebayenterprise_order/search_build_request::build()
     * is invoked, and it will call the method ebayenterprise_order/search_build_request::_buildPayload().
     * Finally, the method ebayenterprise_order/search_build_request::build() will return
     * an instance of type IOrderSummaryRequest.
     */
    public function testBuildOrderSearchRequestPayload()
    {
        /** @var string */
        $customerId = '006512';

        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($this->_payload));

        /** @var Mock_EbayEnterprise_Order_Model_Search_Build_Request */
        $buildRequest = $this->getModelMock('ebayenterprise_order/search_build_request', ['_buildPayload'], false, [[
            // This key is required
            'customer_id' => $customerId,
            // This key is required
            'api' => $this->_api,
        ]]);
        $buildRequest->expects($this->once())
            ->method('_buildPayload')
            ->will($this->returnSelf());
        $this->assertSame($this->_payload, $buildRequest->build());
    }

    /**
     * Test that the method ebayenterprise_order/search_build_request::_buildPayload()
     * is invoked, and it will call the method IOrderSummaryRequest::getOrderSearch(), which
     * will return an instance of type IOrderSearch. The instance of type IOrderSearch will
     * be passed to the method ebayenterprise_order/search_build_request::_buildOrderSearch()
     * which will return the same instance of type IOrderSearch. This same instance of type
     * IOrderSearch will be passed to the method IOrderSummaryRequest::setOrderSearch()
     * Finally, the method ebayenterprise_order/search_build_request::_buildPayload() will
     * return itself.
     */
    public function testBuildPayloadForOrderSearchRequest()
    {
        /** @var string */
        $customerId = '006512';

        /** @var Mock_IOrderSearch */
        $orderSearch = $this->getMockBuilder(static::SUB_PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Mock_IOrderSummaryRequest */
        $payload = $this->getMockBuilder(EbayEnterprise_Order_Model_Search_Build_IRequest::PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->setMethods(['getOrderSearch', 'setOrderSearch'])
            ->getMock();
        $payload->expects($this->once())
            ->method('getOrderSearch')
            ->will($this->returnValue($orderSearch));
        $payload->expects($this->once())
            ->method('setOrderSearch')
            ->with($this->identicalTo($orderSearch))
            ->will($this->returnSelf());

        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($payload));

        /** @var Mock_EbayEnterprise_Order_Model_Search_Build_Request */
        $buildRequest = $this->getModelMock('ebayenterprise_order/search_build_request', ['_buildOrderSearch'], false, [[
            // This key is required
            'customer_id' => $customerId,
            // This key is required
            'api' => $this->_api,
        ]]);
        $buildRequest->expects($this->once())
            ->method('_buildOrderSearch')
            ->with($this->identicalTo($orderSearch))
            ->will($this->returnValue($orderSearch));

        $this->assertSame($buildRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($buildRequest, '_buildPayload', []));
    }

    /**
     * Test that the method ebayenterprise_order/search_build_request::_buildOrderSearch()
     * is invoked, and it will be passed in an instance of type IOrderSearch. Then, the method
     * IOrderSearch::setCustomerId() will be called and passed in the customer id. Then, the method
     * IOrderSearch::setCustomerOrderId() will be invoked and passed in the order id. Finally,
     * the method ebayenterprise_order/search_build_request::_buildOrderSearch() will return
     * the instance of type IOrderSearch.
     */
    public function testBuildOrderSearch()
    {
        /** @var string */
        $customerId = '006512';
        /** @var null */
        $orderId = null;

        $this->_api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($this->_payload));

        /** @var Mock_IOrderSearch */
        $orderSearch = $this->getMockBuilder(static::SUB_PAYLOAD_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->setMethods(['setCustomerId', 'setCustomerOrderId'])
            ->getMock();
        $orderSearch->expects($this->once())
            ->method('setCustomerId')
            ->with($this->identicalTo($customerId))
            ->will($this->returnSelf());
        $orderSearch->expects($this->once())
            ->method('setCustomerOrderId')
            ->with($this->identicalTo($orderId))
            ->will($this->returnSelf());

        /** @var Mock_EbayEnterprise_Order_Model_Search_Build_Request */
        $buildRequest = Mage::getModel('ebayenterprise_order/search_build_request', [
            // This key is required
            'customer_id' => $customerId,
            // This key is required
            'api' => $this->_api,
        ]);

        $this->assertSame($orderSearch, EcomDev_Utils_Reflection::invokeRestrictedMethod($buildRequest, '_buildOrderSearch', [$orderSearch]));
    }
}
