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

class EbayEnterprise_Order_Test_Model_DetailTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';
    const RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\OrderDetailResponse';
    const REQUEST_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\OrderDetailRequest';

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
        $this->_apiService = 'orders';
        /** @var string */
        $this->_apiOperation = 'get';

        /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
        $this->_orderCfg = $this->buildCoreConfigRegistry([
            'apiService' => $this->_apiService,
            'apiDetailOperation' => $this->_apiOperation,
        ]);

        /** EbayEnterprise_Eb2cCore_Helper_Data */
        $this->_coreHelper = $this->getHelperMock('eb2ccore/data', ['getSdkApi']);
        $this->_coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->with($this->identicalTo($this->_apiService), $this->identicalTo($this->_apiOperation))
            ->will($this->returnValue($this->_api));
    }

    /**
     * Test that the method ebayenterprise_order/detail::process()
     * is invoked, and it will call the method ebayenterprise_order/detail::_buildRequest().
     * Then, it will invoke the method ebayenterprise_order/detail::_sendRequest() and then,
     * it will call the method ebayenterprise_order/detail::_processResponse() which will
     * return an instance of type ebayenterprise_order/detail_process_response.
     * Finally, the method ebayenterprise_order/detail::process() will return
     * this instance of type ebayenterprise_order/detail_process_response.
     */
    public function testProcessOrderDetail()
    {
        /** @var Mock_IOrderDetailRequest */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Detail_Process_IResponse */
        $order = Mage::getModel('ebayenterprise_order/detail_process_response', [
            'response' => $response,
        ]);
        /** @var string */
        $orderId = '00089771100500541';

        /** @var Mock_EbayEnterprise_Order_Model_Detail */
        $detail = $this->getModelMock('ebayenterprise_order/detail', ['_buildRequest', '_sendRequest', '_processResponse'], false, [[
            // This key is required
            'order_id' => $orderId,
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        $detail->expects($this->once())
            ->method('_buildRequest')
            ->will($this->returnSelf());
        $detail->expects($this->once())
            ->method('_sendRequest')
            ->will($this->returnSelf());
        $detail->expects($this->once())
            ->method('_processResponse')
            ->will($this->returnValue($order));
        $this->assertSame($order, $detail->process());
    }

    /**
     * Test that the method ebayenterprise_order/detail::_buildRequest()
     * is invoked, and it will called the method ebayenterprise_order/factory::getNewDetailBuildRequest()
     * passing in the order id, which in turn will return an instance of type
     * ebayenterprise_order/detail_build_request. Then, it will invoke the method
     * ebayenterprise_order/detail_build_request::build(), which will return an instance
     * of type IOrderDetailRequest. This instance of type IOrderDetailRequest will be
     * assigned to the class property ebayenterprise_order/detail::$_request.
     * Finally, the method ebayenterprise_order/detail::_buildRequest() will return itself.
     */
    public function testOrderDetailBuildRequest()
    {
        /** @var string */
        $orderId = '00089771100500541';

        /** @var Mock_IOrderDetailRequest */
        $request = $this->getMockBuilder(static::REQUEST_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Detail_Build_Request */
        $detailBuildRequest = $this->getModelMock('ebayenterprise_order/detail_build_request', ['build'], false, [[
            // This key is required
            'api' => $this->_api,
            // This key is required
            'order_id' => $orderId,
        ]]);
        $detailBuildRequest->expects($this->once())
            ->method('build')
            ->will($this->returnValue($request));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewDetailBuildRequest']);
        $factory->expects($this->once())
            ->method('getNewDetailBuildRequest')
            ->with($this->identicalTo($this->_api), $this->identicalTo($orderId))
            ->will($this->returnValue($detailBuildRequest));

        /** @var EbayEnterprise_Order_Model_Detail */
        $detail = $this->getModelMock('ebayenterprise_order/detail', ['foo'], false, [[
            // This key is required
            'order_id' => $orderId,
            // This key is optional
            'factory' => $factory,
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        // Set the class property ebayenterprise_order/detail::$_api to a known state
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($detail, '_api', $this->_api);
        // Proving that the initial state of the class property ebayenterprise_order/detail::$_request is null.
        $this->assertNull(EcomDev_Utils_Reflection::getRestrictedPropertyValue($detail, '_request'));
        $this->assertSame($detail, EcomDev_Utils_Reflection::invokeRestrictedMethod($detail, '_buildRequest', []));
        // Proving that after invoking the method ebayenterprise_order/detail::_buildRequest()
        // the class property ebayenterprise_order/detail::$_request is now
        // an instance of IOrderDetailRequest.
        $this->assertSame($request, EcomDev_Utils_Reflection::getRestrictedPropertyValue($detail, '_request'));
    }

    /**
     * Test that the method ebayenterprise_order/detail::_sendRequest()
     * is invoked, and it will called the method ebayenterprise_order/factory::getNewDetailSendRequest()
     * passing in the IOrderDetailRequest payload object, in turn return an instance of type
     * ebayenterprise_order/detail_send_request. Then, it will invoke the method
     * ebayenterprise_order/detail_send_request::send(), which will return an instance
     * of type IOrderDetailResponse. This instance of type IOrderDetailResponse will be
     * assigned to the class property ebayenterprise_order/detail::$_response.
     * Finally, the method ebayenterprise_order/detail::_sendRequest() will return itself.
     */
    public function testOrderDetailSendRequest()
    {
        /** @var string */
        $orderId = '00089771100500541';

        /** @var Mock_IOrderDetailRequest */
        $request = $this->getMockBuilder(static::REQUEST_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Mock_IOrderDetailResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Detail_Send_Request */
        $detailSendRequest = $this->getModelMock('ebayenterprise_order/detail_send_request', ['send'], false, [[
            // This key is required
            'api' => $this->_api,
            // This key is required
            'request' => $request,
        ]]);
        $detailSendRequest->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewDetailSendRequest']);
        $factory->expects($this->once())
            ->method('getNewDetailSendRequest')
            ->with($this->identicalTo($this->_api), $this->identicalTo($request))
            ->will($this->returnValue($detailSendRequest));

        /** @var EbayEnterprise_Order_Model_Detail */
        $detail = $this->getModelMock('ebayenterprise_order/detail', ['foo'], false, [[
            // This key is required
            'order_id' => $orderId,
            // This key is optional
            'factory' => $factory,
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        // Set the class property ebayenterprise_order/detail::$_api to a known state
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($detail, '_api', $this->_api);
        // Set the class property ebayenterprise_order/detail::$_request to mock of IOrderDetailRequest.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($detail, '_request', $request);
        // Proving that the initial state of the class property ebayenterprise_order/detail::$_response is null.
        $this->assertNull(EcomDev_Utils_Reflection::getRestrictedPropertyValue($detail, '_response'));
        $this->assertSame($detail, EcomDev_Utils_Reflection::invokeRestrictedMethod($detail, '_sendRequest', []));
        // Proving that after invoking the method ebayenterprise_order/detail::_sendRequest()
        // the class property ebayenterprise_order/detail::$_response is now
        // an instance of IOrderDetailResponse.
        $this->assertSame($response, EcomDev_Utils_Reflection::getRestrictedPropertyValue($detail, '_response'));
    }

    /**
     * Test that the method ebayenterprise_order/detail::_processResponse()
     * is invoked, and it will called the method ebayenterprise_order/factory::getNewDetailSendRequest()
     * passing in the IOrderDetailResponse payload object, in turn will return an instance of type
     * ebayenterprise_order/detail_process_response. Then, it will invoke the method
     * ebayenterprise_order/detail_process_response::process(), which will return an instance of type
     * EbayEnterprise_Order_Model_Detail_Process_IResponse. Finally, the method
     * ebayenterprise_order/detail::_processResponse() will return this instance of type
     * EbayEnterprise_Order_Model_Detail_Process_IResponse.
     */
    public function testOrderDetailProcessResponse()
    {
        /** @var Mock_IOrderDetailResponse */
        $response = $this->getMockBuilder(static::RESPONSE_CLASS)
            // Disabling the constructor because it requires the following parameters: IValidatorIterator
            // ISchemaValidator, IPayloadMap, LoggerInterface
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Model_Detail_Process_IResponse */
        $order = Mage::getModel('ebayenterprise_order/detail_process_response', [
            'response' => $response,
        ]);
        /** @var string */
        $orderId = '00089771100500541';

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response */
        $detailProcessResponse = $this->getModelMock('ebayenterprise_order/detail_process_response', ['process'], false, [[
            // This key is required
            'response' => $response,
        ]]);
        $detailProcessResponse->expects($this->once())
            ->method('process')
            ->will($this->returnValue($order));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewDetailProcessResponse']);
        $factory->expects($this->once())
            ->method('getNewDetailProcessResponse')
            ->with($this->identicalTo($response))
            ->will($this->returnValue($detailProcessResponse));

        /** @var EbayEnterprise_Order_Model_Detail */
        $detail = $this->getModelMock('ebayenterprise_order/detail', ['foo'], false, [[
            // This key is required
            'order_id' => $orderId,
            // This key is optional
            'factory' => $factory,
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        // Set the class property ebayenterprise_order/detail::$_api to a known state
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($detail, '_api', $this->_api);
        // Set the class property ebayenterprise_order/detail::$_response to the mock IOrderDetailResponse object.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($detail, '_response', $response);

        $this->assertSame($order, EcomDev_Utils_Reflection::invokeRestrictedMethod($detail, '_processResponse', []));
    }

    /**
     * @see self::testOrderDetailProcessResponse
     * Test that the method ebayenterprise_order/detail::_processResponse()
     * is invoked, will throw a EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception
     * when the class property ebayenterprise_order/detail::$_response is not an instance
     * of type IOrderDetailResponse.
     * @expectedException EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception
     */
    public function testOrderDetailProcessResponseThrowException()
    {
        /** @var null */
        $response = null;
        /** @var null */
        $order = null;
        /** @var string */
        $orderId = '00089771100500541';

        /** @var EbayEnterprise_Order_Model_Detail */
        $detail = $this->getModelMock('ebayenterprise_order/detail', ['foo'], false, [[
            // This key is required
            'order_id' => $orderId,
            // This key is optional
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        // Set the class property ebayenterprise_order/detail::$_api to a known state
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($detail, '_api', $this->_api);
        // Set the class property ebayenterprise_order/detail::$_response to a null value.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($detail, '_response', $response);

        $this->assertSame($order, EcomDev_Utils_Reflection::invokeRestrictedMethod($detail, '_processResponse', []));
    }
}
