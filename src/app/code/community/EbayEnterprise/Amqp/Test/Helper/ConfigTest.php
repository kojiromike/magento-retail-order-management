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

class EbayEnterprise_Amqp_Test_Helper_ConfigTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var Mage_Core_Model_App original Mage::app instance */
    protected $_origApp;
    /** @var ITestMessage */
    protected $_payload;
    /** @var EbayEnterprise_Amqp_Model_Config */
    protected $_configMap;
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $this->_origApp = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_app');
        $this->_payload = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\ITestMessage');
        $this->_configMap = $this->getModelMock('ebayenterprise_amqp/config');
    }
    /**
     * Restore original Mage::app instance
     */
    public function tearDown()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->_origApp);
        parent::tearDown();
    }
    /**
     * Create a store model and mock config registries.
     * @param  array $params store id, username, password
     * @return mixed[] Array of:
     *                 - 'store' => Mage_Core_Model_Store
     *                 - 'amqp_config' => EbayEnterprise_Eb2cCore_Model_Config_Registry for EbayEnterprise_Amqp config
     *                 - 'core_config' => EbayEnterprise_Eb2cCore_Model_Config_Registry for EbayEnterprise_Eb2cCore config
     */
    protected function _buildStoreAndConfig(array $params)
    {
        $store = Mage::getModel('core/store');
        $storeConfig = $this->buildCoreConfigRegistry(array(
            'username' => $params[1],
            'password' => $params[2],
        ));
        $storeCoreConfig = $this->buildCoreConfigRegistry(array(
            'storeId' => $params[0],
        ));
        return array('store' => $store, 'amqp_config' => $storeConfig, 'core_config' => $storeCoreConfig);
    }
    /**
     * Test getting a list of stores with unique AMQP configuration. Any store
     * with a unique store id, AMQP username or AMQP password should be included
     * in the final list.
     */
    public function testGetQueueConfigurationScopes()
    {
        // array of stores and configuration for amqp and eb2ccore, each inner array
        // will have three key/value pairs, a "store", "core_config" and "amqp_config"
        $stores = array_map(
            array($this, '_buildStoreAndConfig'),
            array(
                array('MAINSTORE', 'user', 'secret'),
                array('MAINSTORE', 'user', 'secret'),
                array('ALTSTORE', 'user', 'secret'),
                array('MAINSTORE', 'otheruser', 'secret'),
            )
        );
        // setup a mock app to give back expected stores
        $app = $this->getModelMock('core/app', array('getStores'));
        $app->expects($this->any())
            ->method('getStores')
            ->will($this->returnValue(array_map(
                // Get just the "store" key from each triple in the $stores array - produces
                // an array of Mage_Core_Model_Store objects.
                function ($storeAndConfig) {
                    return $storeAndConfig['store'];
                },
                $stores
            )));

        $coreHelper = $this->getHelperMock('eb2ccore', array('getConfigModel'));
        // setup the stubs for store => config in the Eb2cCore helper
        $coreHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValueMap(array_map(
                // Produces arrays with a Mage_Core_Model_Store as the first element
                // and the Eb2cCore module's config as the second for each triple in
                // the $stores array - allows the stub to return the correct
                // configuration for a given store.
                function ($storeAndConfig) {
                    return array($storeAndConfig['store'], $storeAndConfig['core_config']);
                },
                $stores
            )));
        $helper = $this->getHelperMock('ebayenterprise_amqp');
        // setup the stubs for store => config in the AMQP helper
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValueMap(array_map(
                // Products arrays with a Mage_Core_Model_Store as the first element
                // and the EbayEnterprise_Amqp module's config as the second for each
                // triple in the $stores array - allows the stub to return the correct
                // configuration for a given store.
                function ($storeAndConfig) {
                    return array($storeAndConfig['store'], $storeAndConfig['amqp_config']);
                },
                $stores
            )));

        $configHelper = Mage::helper('ebayenterprise_amqp/config');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($configHelper, '_helper', $helper);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($configHelper, '_coreHelper', $coreHelper);

        // This must be after any factory methods are needed.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $app);

        $this->assertSame(
            array($stores[0]['store'], $stores[2]['store'], $stores[3]['store']),
            $configHelper->getQueueConfigurationScopes()
        );
    }
    /**
     * Test saving the timestamp from the payload in core_config_data.
     */
    public function testUpdateLastTimestamp()
    {
        $configPathToSet = 'config/path/for/timestamp';
        $timestamp = new DateTime('2000-01-01T00:00:00+00:00');
        $store = Mage::getModel('core/store');

        // make a mock Mage_Core_Model_Config_Data to check setting and saving
        // the timestamp value to core_config_data
        $configData = $this->getModelMock('core/config_data', array('save'));
        // make sure the data actually gets saved
        $configData->expects($this->once())
            ->method('save')
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'core/config_data', $configData);

        // stub the config path
        $this->_configMap->expects($this->any())
            ->method('getPathForKey')
            ->will($this->returnValue($configPathToSet));
        // stub payload data
        $this->_payload->expects($this->any())
            ->method('getTimestamp')
            ->will($this->returnValue($timestamp));

        $helper = $this->getHelperMock('ebayenterprise_amqp/config', array('getScopeForStoreSettings'));
        // stub getting scope for store, tested in self::testgetScopeForStoreSettings
        $helper->expects($this->any())
            ->method('getScopeForStoreSettings')
            ->will($this->returnValue(array('default', '0')));

        EcomDev_Utils_Reflection::setRestrictedPropertyValue($helper, '_amqpConfigMap', $this->_configMap);

        // invoke method and test that the config_data model has the expected
        // data set on it
        $configData = $helper->updateLastTimestamp($this->_payload, $store);
        $this->assertSame($configPathToSet, $configData->getPath());
        $valueDateTime = new DateTime($configData->getValue());
        $this->assertSame($timestamp->format('c'), $valueDateTime->format('c'));
        $this->assertSame('0', $configData->getScopeId());
        $this->assertSame('default', $configData->getScope());
    }
    /**
     * Provide a store id, website id, and AMQP configuration for the store,
     * default store and website, and the expected scope, scope id tuple for the
     * provided values.
     * @return array
     */
    public function provideStoreScopesAndConfig()
    {
        $defaultStoreId = '0';
        $altStoreId = '3';
        $websiteId = '1';
        // sets of varying AMQP configurations, all that really matters is they are different from each other
        $configSettings = array(
            array('store_id' => 'STORECODE', 'username' => 'name', 'password' => 'secret'),
            array('store_id' => 'ALTCODE', 'username' => 'name', 'password' => 'secret'),
            array('store_id' => 'STORECODE', 'username' => 'user', 'password' => 'not_so_secret'),
        );
        return array(
            array($altStoreId, $websiteId, $configSettings[0], $configSettings[1], $configSettings[2], array('stores', $altStoreId)),
            array($altStoreId, $websiteId, $configSettings[0], $configSettings[1], $configSettings[0], array('websites', $websiteId)),
            array($altStoreId, $websiteId, $configSettings[0], $configSettings[0], $configSettings[2], array('default', $defaultStoreId)),
            array($altStoreId, null, $configSettings[0], $configSettings[1], $configSettings[0], array('stores', $altStoreId)),
            // this is a bit contrived as if the store is the default store, it would
            // certainly have the same config but this set just hits a slightly
            // different path than when settings are same for store and default store
            array($defaultStoreId, null, $configSettings[0], $configSettings[1], $configSettings[2], array('default', $defaultStoreId)),
        );
    }
    /**
     * Test getting the scope and scope id for a given store view. Should be
     * the lowest level scope with the same AMQP settings as the store.
     * @param string $storeId
     * @param string $websiteId
     * @param array $storeConfigurations
     * @param array $defaultConfigurations
     * @param array $websiteConfigurations
     * @param array $expected
     * @dataProvider provideStoreScopesAndConfig
     */
    public function testGetScopeForStoreSetting(
        $storeId,
        $websiteId,
        array $storeConfigurations,
        array $defaultConfigurations,
        array $websiteConfigurations,
        array $expected
    ) {
        $store = $this->getModelMock('core/store', array('getWebsite'), false, array(array('store_id' => $storeId)));
        $defaultStore = Mage::getModel('core/store', array('store_id' => '0'));
        $website = Mage::getModel('core/website', array('website_id' => $websiteId));

        // Stub the store's getWebsite method
        $getWebsiteMethod = $store->expects($this->any())->method('getWebsite');
        if ($websiteId) {
            $getWebsiteMethod->will($this->returnValue($website));
        } else {
            // if the store has no website, will return false - this is the case for
            // the "default" store view
            $getWebsiteMethod->will($this->returnValue(false));
        }

        $app = $this->getModelMock('core/app', array('getStore'));
        $app->expects($this->any())
            ->method('getStore')
            ->will($this->returnValueMap(array(
                array(0, $defaultStore),
            )));

        $configHelper = $this->getHelperMock(
            'ebayenterprise_amqp/config',
            array('getStoreLevelAmqpConfigurations', 'getWebsiteLevelAmqpConfigurations')
        );

        $configHelper->expects($this->any())
            ->method('getStoreLevelAmqpConfigurations')
            ->will($this->returnValueMap(array(
                array($store, $storeConfigurations),
                array($defaultStore, $defaultConfigurations),
            )));
        $configHelper->expects($this->any())
            ->method('getWebsiteLevelAmqpConfigurations')
            ->will($this->returnValueMap(array(
                array($website, $websiteConfigurations),
            )));

        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $app);
        $this->assertSame(
            $expected,
            $configHelper->getScopeForStoreSettings($store)
        );
    }
}
