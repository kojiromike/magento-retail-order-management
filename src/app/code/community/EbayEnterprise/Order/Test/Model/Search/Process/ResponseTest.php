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

use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;
use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryResponse;

class EbayEnterprise_Order_Test_Model_Search_Process_ResponseTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummaryResponse';
    const ITERABLE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummaryIterable';
    const SUMMARY_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummary';

    /**
     * Get a new PayloadFactory instance.
     *
     * @return PayloadFactory
     */
    protected function _getNewPayloadFactory()
    {
        return new PayloadFactory();
    }

    /**
     * Get a new empty payload instance.
     *
     * @param  string
     * @return IPayload
     */
    protected function _getEmptyPayload($class)
    {
        return $this->_getNewPayloadFactory()
            ->buildPayload($class);
    }

    /**
     * Test that the method ebayenterprise_order/search_process_response::process()
     * is invoked, and it will call the method ebayenterprise_order/search_process_response::_processResponse().
     * Finally, the method ebayenterprise_order/search_process_response::process() will return an instance of type
     * ebayenterprise_order/search_process_response_collection.
     */
    public function testProcessOrderSummaryResponsePayload()
    {
        /** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
        $collection = Mage::getModel('ebayenterprise_order/search_process_response_collection');
        /** @var Mock_IOrderSummaryResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Search_Process_Response */
        $searchProcessResponse = $this->getModelMock('ebayenterprise_order/search_process_response', ['_processResponse'], false, [[
            // This key is required
            'response' => $response,
        ]]);
        $searchProcessResponse->expects($this->once())
            ->method('_processResponse')
            ->will($this->returnValue($collection));
        $this->assertSame($collection, $searchProcessResponse->process());
    }

    /**
     * Test that the method ebayenterprise_order/search_process_response::_processResponse()
     * is invoked, and it will call the method ebayenterprise_order/factory::getNewSearchProcessResponseCollection(),
     * which will return an instance of type ebayenterprise_order/search_process_response_collection.
     * Then, the method IOrderSummaryResponse::getOrderSummaries() will be invoked and returned an instance of type
     * IOrderSummaryIterable. Then, the method ebayenterprise_order/search_process_response::_buildResponseCollection()
     * will be called and passed in as first parameter the instance of type IOrderSummaryIterable and as second parameter
     * the instance of type ebayenterprise_order/search_process_response_collection. Finally, the method
     * ebayenterprise_order/search_process_response::_processResponse() will return the instance of type
     * ebayenterprise_order/search_process_response_collection.
     */
    public function testProcessResponseForOrderSummaryResponsePayload()
    {
        /** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
        $collection = Mage::getModel('ebayenterprise_order/search_process_response_collection');

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewSearchProcessResponseCollection']);
        $factory->expects($this->once())
            ->method('getNewSearchProcessResponseCollection')
            ->will($this->returnValue($collection));

        /** @var Mock_IOrderSummaryIterable */
        $orderSummaryIterable = $this->getMockBuilder(static::ITERABLE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Mock_IOrderSummaryResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->setMethods(['getOrderSummaries'])
            ->getMock();
        $response->expects($this->once())
            ->method('getOrderSummaries')
            ->will($this->returnValue($orderSummaryIterable));

        /** @var EbayEnterprise_Order_Model_Search_Process_Response */
        $searchProcessResponse = $this->getModelMock('ebayenterprise_order/search_process_response', ['_buildResponseCollection'], false, [[
            // This key is required
            'response' => $response,
            // This key is optional
            'factory' => $factory,
        ]]);
        $searchProcessResponse->expects($this->once())
            ->method('_buildResponseCollection')
            ->with($this->identicalTo($orderSummaryIterable), $this->identicalTo($collection))
            ->will($this->returnValue($collection));

        $this->assertSame($collection, EcomDev_Utils_Reflection::invokeRestrictedMethod($searchProcessResponse, '_processResponse', []));
    }

    /**
     * Test that the method ebayenterprise_order/search_process_response::_buildResponseCollection()
     * is invoked, and passed in as first parameter an object of type IOrderSummaryIterable and as second
     * parameter an object of type ebayenterprise_order/search_process_response_collection.
     * It will loop through each item in the IOrderSummaryIterable object, which are objects of type
     * IOrderSummary. Then, it will call the method ebayenterprise_order/search_process_response_map::extract()
     * passing an object of type IOrderSummary and it will return an array of key/value pairs. Then,
     * this array of key/value pairs will be passed to this method ebayenterprise_order/factory::getNewVarienObject(),
     * which will return an instance of type Varien_Object. This Varien_Object object will then be passed to
     * this method ebayenterprise_order/search_process_response_collection::addItem(). After the looping
     * through all the item in the IOrderSummaryIterable object, the method
     * ebayenterprise_order/search_process_response_collection::sort() will be invoked and return itself.
     * Finally, the method ebayenterprise_order/search_process_response::_buildResponseCollection()
     * will return a object of type ebayenterprise_order/search_process_response_collection.
     */
    public function testBuildResponseCollectionForOrderSummaryResponsePayload()
    {
        /** @var array */
        $summaryData = [];
        /** @var Varien_Object */
        $varienObject = new Varien_Object();

        /** @var Mock_EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
        $collection = $this->getModelMock('ebayenterprise_order/search_process_response_collection', ['addItem', 'sort']);
        $collection->expects($this->once())
            ->method('addItem')
            ->with($this->identicalTo($varienObject))
            ->will($this->returnSelf());
        $collection->expects($this->once())
            ->method('sort')
            ->will($this->returnSelf());

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewVarienObject']);
        $factory->expects($this->once())
            ->method('getNewVarienObject')
            ->with($this->identicalTo($summaryData))
            ->will($this->returnValue($varienObject));

        /** @var Mock_IOrderSummary */
        $orderSummary = $this->getMockBuilder(static::SUMMARY_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Search_Process_Response_IMap */
        $map = $this->getModelMock('ebayenterprise_order/search_process_response_map', ['extract']);
        $map->expects($this->once())
            ->method('extract')
            ->with($this->identicalTo($orderSummary))
            ->will($this->returnValue($summaryData));

        /** @var Mock_IOrderSummaryResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Mock_IOrderSummaryIterable */
        $orderSummaryIterable = $this->_getEmptyPayload(static::ITERABLE_CLASS);
        $orderSummaryIterable->offsetSet($orderSummary);

        /** @var EbayEnterprise_Order_Model_Search_Process_Response */
        $searchProcessResponse = Mage::getModel('ebayenterprise_order/search_process_response', [
            // This key is required
            'response' => $response,
            // This key is optional
            'factory' => $factory,
            // This key is optional
            'map' => $map,
        ]);

        $this->assertSame($collection, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $searchProcessResponse,
            '_buildResponseCollection',
            [$orderSummaryIterable, $collection]
        ));
    }
}
