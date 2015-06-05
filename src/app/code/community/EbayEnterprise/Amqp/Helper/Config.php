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

use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\ITestMessage;

class EbayEnterprise_Amqp_Helper_Config
{
    // Id of the "default" store view. Maeg_Core_Model_App simply uses the bare
    // value. Mage_Catalog_Model_Abstract includes a const but that seems an
    // inappropriate place to reference as the "master" source for this value.
    // For lack of a better option, including it again here.
    const DEFAULT_STORE_ID = "0";
    const DEFAULT_SCOPE_CODE = 'default';
    const WEBSITE_SCOPE_CODE = 'websites';
    const STORE_SCOPE_CODE = 'stores';
    const STORE_ID_CONFIG_KEY = 'store_id';
    const USERNAME_CONFIG_KEY = 'username';
    const PASSWORD_CONFIG_KEY = 'password';
    const TIMESTAMP_FORMAT = 'c';
    /** @var EbayEnterprise_Amqp_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var Mage_Core_Helper_Data */
    protected $_mageHelper;
    /** @var EbayEnterprise_Amqp_Model_Config */
    protected $_amqpConfigMap;
    /** @var EbayEnterprise_Eb2cCore_Model_Config */
    protected $_coreConfigMap;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;

    public function __construct()
    {
        $this->_helper = Mage::helper('ebayenterprise_amqp');
        $this->_coreHelper = Mage::helper('eb2ccore');
        $this->_mageHelper = Mage::helper('core');
        $this->_amqpConfigMap = Mage::getSingleton('ebayenterprise_amqp/config');
        $this->_coreConfigMap = Mage::getSingleton('eb2ccore/config');
        $this->_logger = Mage::helper('ebayenterprise_magelog');
    }
    /**
     * Get an array of stores with unique AMQP configuration.
     * @return Mage_Core_Model_Store[]
     */
    public function getQueueConfigurationScopes()
    {
        // cache of seen, unique AMQP configurations
        $configurations = array();
        // list of stores to produce unique AMQP configuration
        $uniqueStores = array();
        foreach (Mage::app()->getStores(true) as $store) {
            $amqpConfig = $this->getStoreLevelAmqpConfigurations($store);
            if (!in_array($amqpConfig, $configurations, true)) {
                $configurations[] = $amqpConfig;
                $uniqueStores[] = $store;
            }
        }
        return $uniqueStores;
    }
    /**
     * Return config values that may vary by website/store for the given
     * store scope.
     * @param Mage_Core_Model_Store $store
     * @return array Config values for store id, AMQP username and AMQP password
     */
    public function getStoreLevelAmqpConfigurations(Mage_Core_Model_Store $store)
    {
        $coreConfig = $this->_coreHelper->getConfigModel($store);
        $amqpConfig = $this->_helper->getConfigModel($store);
        return array(
            'store_id' => $coreConfig->storeId,
            'username' => $amqpConfig->username,
            'password' => $amqpConfig->password,
        );
    }
    /**
     * Return config values that may vary by website/store for the given
     * website.
     * @param  Mage_Core_Model_Website $website
     * @return array Config values for store id, AMQP username and AMQP password
     */
    public function getWebsiteLevelAmqpConfigurations(Mage_Core_Model_Website $website)
    {
        $storeIdPath = $this->_coreConfigMap->getPathForKey(self::STORE_ID_CONFIG_KEY);
        $usernamePath = $this->_amqpConfigMap->getPathForKey(self::USERNAME_CONFIG_KEY);
        $passwordPath = $this->_amqpConfigMap->getPathForKey(self::PASSWORD_CONFIG_KEY);

        $defaultCoreConfig = $this->_coreHelper->getConfigModel(Mage::app()->getStore(0));
        $defaultAmqpConfig = $this->_helper->getConfigModel(Mage::app()->getStore(0));

        // get website level config values, falling back to any not available to the
        // website with default store config values
        return array(
            'store_id' => $website->getConfig($storeIdPath) ?: $defaultCoreConfig->storeId,
            'username' => $website->getConfig($usernamePath) ?: $defaultAmqpConfig->username,
            'password' => $this->_mageHelper->decrypt($website->getConfig($passwordPath)) ?: $defaultAmqpConfig->password,
        );
    }
    /**
     * Update the core_config_data setting for timestamp from the last test
     * message received. Value should be saved in the most appropriate scope for
     * the store being processed. E.g. if the store is the default store, or has
     * the same AMQP configuration as the default store, the timestamp should be
     * updated in the default scope. If the stores AMQP configuration matches a
     * website level configuration, should be saved within that website's scope.
     * @param ITestMessage $payload
     * @param Mage_Core_Model_Store $store
     * @return Mage_Core_Model_Config_Data
     */
    public function updateLastTimestamp(ITestMessage $payload, Mage_Core_Model_Store $store)
    {
        list($scope, $scopeId) = $this->getScopeForStoreSettings($store);
        return Mage::getModel('core/config_data')
            ->addData(array(
                'path' => $this->_amqpConfigMap->getPathForKey('last_test_message_timestamp'),
                'value' => $payload->getTimestamp()->format(self::TIMESTAMP_FORMAT),
                'scope' => $scope,
                'scope_id' => $scopeId,
            ))
            ->save();
    }
    /**
     * Return the lowest possible scope and scope id with the same configurations
     * as the given store with "default" scope being the lowest, website second
     * and store view last. E.g. if the store has the same AMQP configuration as
     * a website with an id of 2 but different from the default store's AMQP
     * configuration, this should return 'website' and 2. If the store has the
     * same configuration as (or is) the default store, should return 'default'
     * and 0.
     * @param  Mage_Core_Model_Store $store
     * @return mixed[] Tuple of scope "type" and scope id
     */
    public function getScopeForStoreSettings(Mage_Core_Model_Store $store)
    {
        $storeSettings = $this->getStoreLevelAmqpConfigurations($store);
        $defaultStoreSettings = $this->getStoreLevelAmqpConfigurations(Mage::app()->getStore(0));
        // start at the lowest possible level, the "default" scope and work up
        if ($this->_isDefaultStore($store) || $storeSettings === $defaultStoreSettings) {
            return array(self::DEFAULT_SCOPE_CODE, self::DEFAULT_STORE_ID);
        }
        // If no match for the default scope, check the website the store belongs to.
        $website = $store->getWebsite();
        if ($website && $storeSettings === $this->getWebsiteLevelAmqpConfigurations($website)) {
            return array(self::WEBSITE_SCOPE_CODE, $website->getId());
        }
        // if no match for default or website, scope must be at the store level
        return array(self::STORE_SCOPE_CODE, $store->getId());
    }
    /**
     * Determine if the store is the "default" store scope.
     * @param  Mage_Core_Model_Store $store
     * @return bool
     */
    protected function _isDefaultStore(Mage_Core_Model_Store $store)
    {
        // cast to string as id may be an int or int as a string and const value is a string
        return (string) $store->getId() === self::DEFAULT_STORE_ID;
    }
}
