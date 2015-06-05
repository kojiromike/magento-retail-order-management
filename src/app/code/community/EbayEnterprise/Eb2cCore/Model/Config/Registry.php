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

/**
 * Single point of access for retrieving configuration values.
 * Looks up configuration paths and values via configuration models registered with the helper.
 *
 * @prop string configVal The store config value represented by the key config_val
 * @prop bool configValFlag A bool store config value represented by the key config_val
 */
class EbayEnterprise_Eb2cCore_Model_Config_Registry
{
    /**
     * The default value to use when looking up config values.
     * @var null|string|bool|int|Mage_Core_Model_Store
     */
    protected $_store = null;
    /**
     * Array of registered config models used to associate config paths with keys.
     * @var EbayEnterprise_Eb2cCore_Model_Config_Interface[]
     */
    protected $_configModels = array();

    /**
     * Add a new config settings model to the collection and update the list of known config paths
     * @param EbayEnterprise_Eb2cCore_Model_Config_Interface $configModel
     * @return self
     */
    public function addConfigModel(EbayEnterprise_Eb2cCore_Model_Config_Interface $configModel)
    {
        array_unshift($this->_configModels, $configModel);
        return $this;
    }

    /**
     * Set a default store to use when getting config values
     * @param null|string|bool|int|Mage_Core_Model_Store $store
     * @return self
     */
    public function setStore($store)
    {
        $this->_store = Mage::app()->getStore($store);
        return $this;
    }

    /**
     * Get the default store
     * @return null|Mage_Core_Model_Store
     */
    public function getStore()
    {
        return $this->_store;
    }

    /**
     * Search through registered config models for one that knows about the key
     * and get the actual config path from it for the lookup.
     *
     * @param string $configKey
     * @param null|string|bool|int|Mage_Core_Model_Store $store
     * @param bool $asFlag
     * @throws Mage_Core_Exception if the config path is not found.
     * @return string|bool
     */
    protected function _getStoreConfigValue($configKey, $store, $asFlag)
    {
        foreach ($this->_configModels as $configModel) {
            if ($configModel->hasKey($configKey)) {
                $configMethod = $asFlag ? 'getStoreConfigFlag' : 'getStoreConfig';
                return Mage::$configMethod($configModel->getPathForKey($configKey), $store);
            }
        }
        throw Mage::exception('Mage_Core', "Configuration path specified by '$configKey' was not found.");
    }

    /**
     * Get the configuration value represented by the given configKey
     * @param string $configKey
     * @param null|string|bool|int|Mage_Core_Model_Store $store
     * @return string
     */
    public function getConfigFlag($configKey, $store = null)
    {
        // if a value is given store, use it, even if it is null/false/empty string/whatever
        $store = count(func_get_args()) > 1 ? $store : $this->getStore();
        return $this->_getStoreConfigValue($configKey, $store, true);
    }

    /**
     * Get the configuration flag value represented by the given configKey
     * @param string $configKey
     * @param null|string|bool|int|Mage_Core_Model_Store $store
     * @return bool
     */
    public function getConfig($configKey, $store = null)
    {
        // if a value is given store, use it, even if it is null/false/empty string/whatever
        $store = count(func_get_args()) > 1 ? $store : $this->getStore();
        return $this->_getStoreConfigValue($configKey, $store, false);
    }

    /**
     * Catch any unknown property references to try to magically retrieve config values.
     * Uses the stored store.
     *
     * @param string $name The property name
     * @return null|string|bool|Mage_Core_Model_Store Boolean if the property name ends with "Flag",
     *         Mage_Core_Model_Store if the property is "store", string otherwise.
     */
    public function __get($name)
    {
        $store = $this->getStore();
        $isFlag = preg_match('/Flag$/', $name); // It's a flag if it ends with "Flag"
        // Get the string config key the path is mapped to - camelCase -> undersore_case
        // and remove the trailing "Flag" if the name used is a flag
        $configKey = Mage::helper('eb2ccore')->underscoreWords(substr($name, 0, strlen($name) - 4 * $isFlag));
        try {
            // Retrieve the actual config value, passing the $isFlag arg.
            return $this->_getStoreConfigValue($configKey, $store, $isFlag);
        } catch (Exception $e) {
            // Be consistent with how PHP treats undefined properties.
            trigger_error(sprintf('Undefined property: %s::%s in php shell code on line %d', get_class($this), $name, __LINE__));
        }
        return null;
    }
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __set($name, $value)
    {
        trigger_error(sprintf('Cannot write property %s::%s in php shell code on line %d', get_class($this), $name, __LINE__), E_USER_ERROR);
    }
    /**
     * abstracting getting config child nodes in an array
     * @param string $path the parent path to set of node to get as array
     * @return array
     * @codeCoverageIgnore
     */
    public function getConfigData($path)
    {
        return Mage::app()->getStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)->getConfig($path);
    }
}
