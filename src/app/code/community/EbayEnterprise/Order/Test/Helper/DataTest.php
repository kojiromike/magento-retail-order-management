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

class EbayEnterprise_Order_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';

    /** @var EbayEnterprise_Order_Helper_Data */
    protected $_helper;

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $this->_helper = Mage::helper('ebayenterprise_order');
    }

    public function testGetOrderHistoryUrl()
    {
        $expectedPath = parse_url(Mage::getBaseUrl() . 'sales/guest/view/')['path'];
        $expectedParams = [
            'oar_billing_lastname' => 'Sibelius',
            'oar_email' => 'foo@bar.com',
            'oar_order_id' => '908123987143079814',
            'oar_type' => 'email',
        ];
        $order = $this->getModelMockBuilder('sales/order')
            ->setMethods(['getCustomerEmail', 'getCustomerLastname', 'getIncrementId'])
            ->getMock();
        $order->expects($this->any())
            ->method('getCustomerEmail')
            ->will($this->returnValue($expectedParams['oar_email']));
        $order->expects($this->any())
            ->method('getCustomerLastname')
            ->will($this->returnValue($expectedParams['oar_billing_lastname']));
        $order->expects($this->any())
            ->method('getIncrementId')
            ->will($this->returnValue($expectedParams['oar_order_id']));
        $url = parse_url($this->_helper->getOrderHistoryUrl($order));
        $path = $url['path'];
        $query = [];
        parse_str($url['query'], $query);
        $this->assertSame($expectedPath, $path);
        $this->assertSame($expectedParams, $query);
    }

    /**
     * @return array
     */
    public function providerGetOrderCancelReason()
    {
        return [
            [$this->buildCoreConfigRegistry(['cancelReasonMap' => []]), []],
            [$this->buildCoreConfigRegistry(['cancelReasonMap' => "\n\t\t"]), null],
        ];
    }

    /**
     * Test that helper method ebayenterprise_order/data::_getOrderCancelReason()
     * when invoked will called the helper method ebayenterprise_order/data::getConfigModel()
     * which will return an instance of eb2ccore/config_registry. Then the magic property
     * 'cancelReasonMap' will we tested if is an array the method
     * ebayenterprise_order/data::_getOrderCancelReason() return that array otherwise it
     * will return a null value.
     * @param Mock_EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param mixed
     * @dataProvider providerGetOrderCancelReason
     */
    public function testGetOrderCancelReason($config, $result)
    {
        $helper = $this->getHelperMock('ebayenterprise_order/data', ['getConfigModel']);
        $helper->expects($this->once())
            ->method('getConfigModel')
            ->with($this->identicalTo(null))
            ->will($this->returnValue($config));
        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $helper,
            '_getOrderCancelReason',
            []
        ));
    }

    /**
     * @return array
     */
    public function providerHasOrderCancelReason()
    {
        return [
            [['reason_code_001' => 'Wrong Products'], true],
            [[], false],
            [null, false],
        ];
    }

    /**
     * Test that helper method ebayenterprise_order/data::hasOrderCancelReason()
     * when invoked will called the helper method ebayenterprise_order/data::_getOrderCancelReason()
     * which when return a non empty array the method ebayenterprise_order/data::hasOrderCancelReason()
     * return the boolean value true, otherwise it return false.
     * @param mixed
     * @param bool
     * @dataProvider providerHasOrderCancelReason
     */
    public function testHasOrderCancelReason($reasons, $result)
    {
        $helper = $this->getHelperMock('ebayenterprise_order/data', ['_getOrderCancelReason']);
        $helper->expects($this->once())
            ->method('_getOrderCancelReason')
            ->will($this->returnValue($reasons));
        $this->assertSame($result, $helper->hasOrderCancelReason());
    }

    /**
     * @return array
     */
    public function providerGetCancelReasonOptionArray()
    {
        return [
            [['reason_code_001' => 'Wrong Products'], [['value' => '', 'label' => ''], ['value' => 'reason_code_001', 'label' => 'Wrong Products']]],
            [[], [['value' => '', 'label' => '']]],
            [null, [['value' => '', 'label' => '']]],
        ];
    }

    /**
     * Test that helper method ebayenterprise_order/data::getCancelReasonOptionArray()
     * when invoked will called the helper method ebayenterprise_order/data::_getOrderCancelReason()
     * which when return a non empty array with key/value pair the method
     * ebayenterprise_order/data::getCancelReasonOptionArray() will loop through each key/value
     * and append a new element array with key mapped to the key 'value'  and value mapped to the key
     * value, along with the first index element which has a default array element with key value and
     * label mapped an empty string. Otherwise, if the method ebayenterprise_order/data::_getOrderCancelReason()
     * return an empty array or null only the default array element with empty value for the key value
     * and label is will be return from the ebayenterprise_order/data::getCancelReasonOptionArray() method.
     * @param mixed
     * @param bool
     * @dataProvider providerGetCancelReasonOptionArray
     */
    public function testGetCancelReasonOptionArray($reasons, $result)
    {
        $helper = $this->getHelperMock('ebayenterprise_order/data', ['_getOrderCancelReason']);
        $helper->expects($this->once())
            ->method('_getOrderCancelReason')
            ->will($this->returnValue($reasons));
        $this->assertSame($result, $helper->getCancelReasonOptionArray());
    }

    /**
     * @return array
     */
    public function providerGetCancelReasonDescription()
    {
        return [
            [['reason_code_001' => 'Wrong Products'], 'reason_code_001', 'Wrong Products'],
            [[], 'reason_code_001', null],
            [null, 'reason_code_001', null],
        ];
    }

    /**
     * Test that helper method ebayenterprise_order/data::getCancelReasonDescription()
     * when invoked will be passed in an order reason code if when we call the helper method
     * ebayenterprise_order/data::_getOrderCancelReason() it return a non empty array
     * with a key that match the passed in reason code, then the method
     * ebayenterprise_order/data::getCancelReasonDescription() will return the order cancel
     * reason description for the passed in reason code. Otherwise it will return null.
     * @param mixed
     * @param bool
     * @dataProvider providerGetCancelReasonDescription
     */
    public function testGetCancelReasonDescription($reasons, $code, $result)
    {
        $helper = $this->getHelperMock('ebayenterprise_order/data', ['_getOrderCancelReason']);
        $helper->expects($this->once())
            ->method('_getOrderCancelReason')
            ->will($this->returnValue($reasons));
        $this->assertSame($result, $helper->getCancelReasonDescription($code));
    }

    /**
     * Provide a customer id, configured customer id prefix and the fully prefixed id
     * @return array
     */
    public function provideCustomerId()
    {
        $prefix = '0001';
        $customerId = '3';
        $length = 7;
        return [
            [$customerId, $prefix, $prefix . str_pad($customerId, $length, '0', STR_PAD_LEFT), $length],
            [null, $prefix, null, 0],
        ];
    }

    /**
     * Test getting the current user id, prefixed by the configured client
     * customer id prefix.
     * @param string|null $customer Customer retrieved from the session
     * @param string $prefix Client customer id prefix
     * @param string|null $prefixedId prefixed customer id
     * @param int $length customer id padding length using zeros
     * @dataProvider provideCustomerId
     */
    public function testGetPrefixedCurrentCustomerId($customerId, $prefix, $prefixedId, $length)
    {
        /** @var Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer', ['entity_id' => $customerId]);
        /** @var Mock_EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getCurrentCustomer']);
        $factory->expects($this->once())
            ->method('getCurrentCustomer')
            ->will($this->returnValue($customer));

        /** @var EbayEnterprise_Eb2cCore_Helper_Data */
        $coreHelper = $this->getHelperMock('eb2ccore/data', ['getConfigModel']);
        $coreHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry([
                'clientCustomerIdPrefix' => $prefix,
                'clientCustomerIdLength' => $length,
            ])));

        /** @var Mock_EbayEnterprise_Order_Helper_Data */
        $helper = Mage::helper('ebayenterprise_order');

        EcomDev_Utils_Reflection::setRestrictedPropertyValues($helper, [
            '_coreHelper' => $coreHelper,
            '_factory' => $factory,
        ]);

        $this->assertSame(
            $prefixedId,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, '_getPrefixedCurrentCustomerId')
        );
    }

    /**
     * @return array
     */
    public function providerGetCurCustomerOrders()
    {
        return [
            ['0001832'],
            [null],
        ];
    }

    /**
     * Test that the helper method ebayenterprise_order/data::getCurCustomerOrders()
     * when invoked will call the helper method ebayenterprise_order/data::_getPrefixedCurrentCustomerId()
     * which when return a non-empty string value the method ebayenterprise_order/factory::getNewRomOrderSearch()
     * will be called and passed in the string literal customer id, in turn it will return an instance of type
     * ebayenterprise_order/search. Then, the method ebayenterprise_order/search::process() will be invoked and return
     * an instance of type ebayenterprise_order/search_process_response_collection. However, if the
     * the helper method ebayenterprise_order/data::_getPrefixedCurrentCustomerId() return an empty string
     * or a null value, then the method eb2ccore/data::getNewVarienDataCollection() will be invoked instead,
     * which will return an instance of type Varien_Data_Collection. Finally, the helper method
     * ebayenterprise_order/data::getCurCustomerOrders() will either return an empty Varien_Data_Collection object
     * or a full ebayenterprise_order/search_process_response_collection object.
     *
     * @param string | null
     * @dataProvider providerGetCurCustomerOrders
     */
    public function testGetCurCustomerOrders($customerId)
    {
        $api = $this->getMockBuilder(static::API_CLASS)
            // Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
            ->disableOriginalConstructor()
            ->getMock();

        /** @var string */
        $apiService = 'customers';
        /** @var string */
        $apiOperation = 'orders/get';

        /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
        $orderCfg = $this->buildCoreConfigRegistry([
            'apiSearchService' => $apiService,
            'apiSearchOperation' => $apiOperation,
        ]);

        /** @var Varien_Data_Collection */
        $result = is_null($customerId)
            ? new Varien_Data_Collection()
            : Mage::getModel('ebayenterprise_order/search_process_response_collection');

        /** @var EbayEnterprise_Eb2cCore_Helper_Data */
        $coreHelper = $this->getHelperMock('eb2ccore/data', ['getNewVarienDataCollection', 'getSdkApi']);
        $coreHelper->expects(is_null($customerId) ? $this->once() : $this->never())
            // Proving that this method will only be invoked once when the method
            // ebayenterprise_order/data::_getPrefixedCurrentCustomerId() return a null value
            // or an empty string value, otherwise this method will never be invoked.
            ->method('getNewVarienDataCollection')
            ->will($this->returnValue($result));
        $coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->with($this->identicalTo($apiService), $this->identicalTo($apiOperation))
            ->will($this->returnValue($api));

        /** @var EbayEnterprise_Order_Model_Search */
        $search = $this->getModelMock('ebayenterprise_order/search', ['process'], false, [[
            // This key is required
            'customer_id' => $customerId,
            // This key is optional
            'core_helper' => $coreHelper,
            // This key is optional
            'order_cfg' => $orderCfg,
        ]]);
        $search->expects(is_null($customerId) ? $this->never() : $this->once())
            // Proving that this method will only be invoked once when the method
            // ebayenterprise_order/data::_getPrefixedCurrentCustomerId() return a valid
            // non-empty string value, otherwise this method will never be invoked.
            ->method('process')
            ->will($this->returnValue($result));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewRomOrderSearch']);
        $factory->expects(is_null($customerId) ? $this->never() : $this->once())
            // Proving that this method will only be invoked once when the method
            // ebayenterprise_order/data::_getPrefixedCurrentCustomerId() return a valid
            // non-empty string value, otherwise this method will never be invoked.
            ->method('getNewRomOrderSearch')
            ->with($this->identicalTo($customerId))
            ->will($this->returnValue($search));

        /** @var EbayEnterprise_Order_Helper_Data */
        $helper = $this->getHelperMock('ebayenterprise_order/data', ['_getPrefixedCurrentCustomerId']);
        $helper->expects($this->once())
            ->method('_getPrefixedCurrentCustomerId')
            ->will($this->returnValue($customerId));

        EcomDev_Utils_Reflection::setRestrictedPropertyValues($helper, [
            '_coreHelper' => $coreHelper,
            '_factory' => $factory,
        ]);

        $this->assertSame($result, $helper->getCurCustomerOrders());
    }

        /**
     * Test removing the order increment id prefix.
     */
    public function testRemoveOrderIncrementPrefix()
    {
        $admin = Mage::getModel('core/store', ['store_id' => 0]);
        $default = Mage::getModel('core/store', ['store_id' => 1]);

        $adminConfig = $this->buildCoreConfigRegistry(['clientOrderIdPrefix' => '555']);
        $storeConfig = $this->buildCoreConfigRegistry(['clientOrderIdPrefix' => '7777']);
        $coreHelper = $this->getHelperMock('eb2ccore/data', ['getConfigModel']);
        $coreHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValueMap([
                [0, $adminConfig],
                [1, $storeConfig],
            ]));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelper);
        /** @var EbayEnterprise_Order_Helper_Data */
        $helper = $this->getHelperMock('ebayenterprise_order/data', ['_getAllStores']);
        $helper->expects($this->any())
            ->method('_getAllStores')
            ->will($this->returnValue([$admin, $default]));

        // should be able to replace the order id prefix from any config scope
        $this->assertSame('8888888', $helper->removeOrderIncrementPrefix('77778888888'));
        $this->assertSame('8888888', $helper->removeOrderIncrementPrefix('5558888888'));
        // when no matching prefix on the original increment id, should return unmodified value
        $this->assertSame('1238888888', $helper->removeOrderIncrementPrefix('1238888888'));
        // must work with null as when the first increment id for a store is
        // created, the "last id" will be given as null
        $this->assertSame('', $helper->removeOrderIncrementPrefix(null));
    }
}
