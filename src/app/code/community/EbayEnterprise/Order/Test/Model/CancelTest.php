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
    const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';
    const RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCancelResponse';

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
        $this->_apiOperation = 'cancel';

        /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
        $this->_orderCfg = $this->buildCoreConfigRegistry([
            'apiService' => $this->_apiService,
            'apiCancelOperation' => $this->_apiOperation,
        ]);

        /** EbayEnterprise_Eb2cCore_Helper_Data */
        $this->_coreHelper = $this->getHelperMock('eb2ccore/data', ['getSdkApi']);
        $this->_coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->with($this->identicalTo($this->_apiService), $this->identicalTo($this->_apiOperation))
            ->will($this->returnValue($this->_api));
    }

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
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
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
            'api' => $this->_api,
            // This key is required
            'order' => $order,
        ]]);
        $cancelBuildRequest->expects($this->once())
            ->method('build')
            ->will($this->returnValue($request));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewCancelBuildRequest']);
        $factory->expects($this->once())
            ->method('getNewCancelBuildRequest')
            ->with($this->identicalTo($this->_api), $this->identicalTo($order))
            ->will($this->returnValue($cancelBuildRequest));

        /** @var Mock_EbayEnterprise_Order_Model_Cancel */
        $cancel = $this->getModelMock('ebayenterprise_order/cancel', ['foo'], false, [[
            // This key is required
            'order' => $order,
            // This key is optional
            'factory' => $factory,
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        // Set the class property ebayenterprise_order/cancel::$_api to a known state
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($cancel, '_api', $this->_api);
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
            'api' => $this->_api,
            // This key is required
            'request' => $request,
        ]]);
        $cancelSendRequest->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewCancelSendRequest']);
        $factory->expects($this->once())
            ->method('getNewCancelSendRequest')
            ->with($this->identicalTo($this->_api), $this->identicalTo($request))
            ->will($this->returnValue($cancelSendRequest));

        /** @var Mock_EbayEnterprise_Order_Model_Cancel */
        $cancel = $this->getModelMock('ebayenterprise_order/cancel', ['foo'], false, [[
            // This key is required
            'order' => $order,
            // This key is optional
            'factory' => $factory,
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        // Set the class property ebayenterprise_order/cancel::$_api to a known state
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($cancel, '_api', $this->_api);
        // Set the class property ebayenterprise_order/cancel::$_request to mock of IOrderCancelRequest.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($cancel, '_request', $request);
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

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewCancelProcessResponse']);
        $factory->expects($this->once())
            ->method('getNewCancelProcessResponse')
            ->with($this->identicalTo($response), $this->identicalTo($order))
            ->will($this->returnValue($cancelProcessResponse));

        /** @var Mock_EbayEnterprise_Order_Model_Cancel */
        $cancel = $this->getModelMock('ebayenterprise_order/cancel', ['foo'], false, [[
            // This key is required
            'order' => $order,
            // This key is optional
            'factory' => $factory,
            // This key is optional
            'core_helper' => $this->_coreHelper,
            // This key is optional
            'order_cfg' => $this->_orderCfg,
        ]]);
        // Set the class property ebayenterprise_order/cancel::$_api to a known state
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($cancel, '_api', $this->_api);
        // Set the class property ebayenterprise_order/cancel::$_response to mock of IOrderCancelResponse.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($cancel, '_response', $response);
        $this->assertSame($cancel, EcomDev_Utils_Reflection::invokeRestrictedMethod($cancel, '_processResponse', []));
    }
}
