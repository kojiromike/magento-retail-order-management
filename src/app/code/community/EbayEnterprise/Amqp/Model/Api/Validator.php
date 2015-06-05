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

use eBayEnterprise\RetailOrderManagement\Api\Exception\ConnectionError;

class EbayEnterprise_Amqp_Model_Api_Validator
{
    const INVALID_CONFIGURATION = 'EbayEnterprise_Amqp_Api_Validator_Invalid_Configuration';
    const NO_QUEUES_CONFIGURED = 'EbayEnterprise_Amqp_Api_Validator_No_Queues_Configured';
    const CONNECTION_FAILED = 'EbayEnterprise_Amqp_Api_Validator_Connection_Failed';
    const CONNECTION_SUCCESS = 'EbayEnterprise_Amqp_Api_Validator_Connection_Success';
    const UNABLE_TO_VALIDATE = 'EbayEnterprise_Amqp_Api_Validator_Unable_To_Validate';

    /** @var Mage_Core_Model_Store */
    protected $_store;
    /** @var EbayEnterprise_Amqp_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    /**
     * @param array $initParams May contain:
     *                          - 'store' => Mage_Core_Model_Store
     *                          - 'helper' => EbayEnterprise_Amqp_Helper_Data
     *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $initParams = array())
    {
        list($this->_store, $this->_helper, $this->_logger, $this->_context) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'store', Mage::app()->getStore()),
            $this->_nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_amqp')),
            $this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->_nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }
    /**
     * Type hinting for self::__construct $initParams
     * @param  Mage_Core_Model_Store              $store
     * @param  EbayEnterprise_Amqp_Helper_Data    $helper
     * @param  EbayEnterprise_MageLog_Helper_Data $logger
     * @param  EbayEnterprise_MageLog_Helper_Context $context
     * @return mixed[]
     */
    protected function _checkTypes(
        Mage_Core_Model_Store $store,
        EbayEnterprise_Amqp_Helper_Data $helper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context
    ) {
        return array($store, $helper, $logger, $context);
    }
    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param array      $arr
     * @param string|int $field Valid array key
     * @param mixed      $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }
    /**
     * Validate the hostname, username and password all appear valid.
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @throws EbayEnterprise_Amqp_Exception_Configuration_Exception
     * @return self
     */
    protected function _validateConfiguration($hostname, $username, $password)
    {
        $invalidSettings = array();
        if ($hostname === '') {
            $invalidSettings[] = 'Hostname';
        }
        if ($username === '') {
            $invalidSettings[] = 'Username';
        }
        if ($password === '') {
            $invalidSettings[] = 'Password';
        }
        if ($invalidSettings) {
            throw Mage::exception(
                'EbayEnterprise_Amqp_Exception_Configuration',
                $this->_helper->__(self::INVALID_CONFIGURATION, implode(', ', $invalidSettings))
            );
        }
        return $this;
    }
    /**
     * Validate the hostname, username and password all appear valid.
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @throws EbayEnterprise_Amqp_Exception_Connection_Exception
     * @return self
     */
    protected function _validateConnection($hostname, $username, $password)
    {
        $queues = $this->_helper->getConfigModel($this->_store)->queueNames;
        if (!$queues) {
            throw Mage::exception('EbayEnterprise_Amqp_Exception_Configuration', $this->_helper->__(self::NO_QUEUES_CONFIGURED));
        }
        // try to connect using the first configured queue - which one it is doesn't
        // matter much as no messages will be consumed
        $amqpApi = $this->_helper->getSdkAmqp(current($queues), $this->_store, $hostname, $username, $password);
        try {
            $amqpApi->openConnection();
        } catch (ConnectionError $e) {
            $logData = ['error_message' => $e->getMessage()];
            $logMessage = 'Failed to connect to AMQP server with message: {error_message}';
            $this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData, $e));
        }
        if (!$amqpApi->isConnected()) {
            throw Mage::exception('EbayEnterprise_Amqp_Exception_Connection', $this->_helper->__(self::CONNECTION_FAILED));
        }
        return $this;
    }
    /**
     * Test connecting to the AMQP server. Will connect to the AMQP server but will
     * not consume any messages.
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @return array AJAX response body. Should contain a 'message' => string and 'success' => bool
     */
    public function testConnection($hostname, $username, $password)
    {
        try {
            $this->_validateConfiguration($hostname, $username, $password)
                ->_validateConnection($hostname, $username, $password);
        } catch (EbayEnterprise_Amqp_Exception $e) {
            return array('message' => $e->getMessage(), 'success' => false);
        } catch (Exception $e) {
            $logData = ['error_message' => $e->getMessage()];
            $logMessage = 'Failed to connect to AMQP server with message: {error_message}';
            $this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData, $e));
            return array('message' => $this->_helper->__(self::UNABLE_TO_VALIDATE), 'success' => false);
        }
        return array('message' => $this->_helper->__(self::CONNECTION_SUCCESS), 'success' => true);
    }
}
