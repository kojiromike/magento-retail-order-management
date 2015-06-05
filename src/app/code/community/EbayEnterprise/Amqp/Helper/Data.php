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

use eBayEnterprise\RetailOrderManagement\Api;

class EbayEnterprise_Amqp_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    public function __construct()
    {
        $this->_coreHelper = Mage::helper('eb2ccore');
        $this->_context = Mage::helper('ebayenterprise_magelog/context');
        $this->_logger = Mage::helper('ebayenterprise_magelog');
    }
    /**
     * @param mixed $store Any valid store identifier
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @codeCoverageIgnore standard implementation of this method, nothing to really test
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_amqp/config'));
    }
    /**
     * Get a new AMQP SDK API object. Hostname, username and password may be
     * provided to override configuration settings (useful for testing AMQP
     * credentials).
     * @param string $queueName
     * @param mixed $store Any valid store identifier. Default to null (current store)
     * @param string $hostname If empty will use value in config
     * @param string $username If empty will use value in config
     * @param string $password If empty will use value in config
     * @return Api\IAmqpApi
     * @codeCoverageIgnore Minimal logic worth testing, just calling constructors with config values
     */
    public function getSdkAmqp($queueName, $store = null, $hostname = null, $username = null, $password = null)
    {
        $coreConfig = $this->_coreHelper->getConfigModel($store);
        $queueName = $this->_processQueueName($queueName, $coreConfig);
        $config = $this->getConfigModel($store);

        $hostname = $hostname ?: $config->hostname;
        $username = $username ?: $config->username;
        $password = $password ?: $config->password;

        $amqpConfig = new Api\AmqpConfig(
            $config->connectionType,
            $config->numberOfMessagesToProcess,
            $hostname,
            $config->port,
            $username,
            $password,
            $config->vhost,
            is_array($config->connectionContext) ? $config->connectionContext : [],
            $config->connectionInsistFlag,
            $config->connectionLoginMethod,
            $config->connectionLocale,
            $config->connectionTimeout,
            $config->connectionReadWriteTimeout,
            $queueName,
            $config->queuePassiveFlag,
            $config->queueDurableFlag,
            $config->queueExclusiveFlag,
            $config->queueAutoDeleteFlag,
            $config->queueNowaitFlag,
            $this->_logger
        );
        return new Api\AmqpApi($amqpConfig, [], $this->_logger);
    }
    /**
     * Replace placeholder strings in the queue name with config values. Currently
     * only replaces {store_id} with the configured storeId.
     * @param string $queueName
     * @param EbayEnterprise_Eb2cCore_Model_Config_Registry $config
     * @return string
     */
    protected function _processQueueName($queueName, EbayEnterprise_Eb2cCore_Model_Config_Registry $config)
    {
        return str_replace(array('{store_id}'), array($config->storeId), $queueName);
    }
}
