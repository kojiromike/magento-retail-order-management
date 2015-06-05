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

use eBayEnterprise\RetailOrderManagement\Payload\Exception;
use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\IOrderEvent;

class EbayEnterprise_Amqp_Model_Runner
{
    protected $_eventPrefix = 'ebayenterprise_amqp_message';
    /** @var EbayEnterprise_Amqp_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_Amqp_Helper_Config */
    protected $_amqpConfigHelper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    /**
     * @param array $initParams May accept:
     *                          - 'helper' => EbayEnterprise_Amqp_Helper_Data
     *                          - 'amqpConfigHelper' => EbayEnterprise_Amqp_Helper_Config
     *                          - 'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
     *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $initParams = array())
    {
        list($this->_helper, $this->_amqpConfigHelper, $this->_coreHelper, $this->_logger, $this->_context) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_amqp')),
            $this->_nullCoalesce($initParams, 'amqp_config_helper', Mage::helper('ebayenterprise_amqp/config')),
            $this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
            $this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->_nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }
    /**
     * Type hinting for self::__construct $initParams
     * @param EbayEnterprise_Amqp_Helper_Data $helper
     * @param EbayEnterprise_Amqp_Helper_Config $amqpConfigHelper
     * @param EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
     * @param EbayEnterprise_MageLog_Helper_Data $logger
     * @param EbayEnterprise_MageLog_Helper_Context $context
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Amqp_Helper_Data $helper,
        EbayEnterprise_Amqp_Helper_Config $amqpConfigHelper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context
    ) {
        return array($helper, $amqpConfigHelper, $coreHelper, $logger, $context);
    }
    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param array $arr
     * @param string|int $field Valid array key
     * @param mixed $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }
    /**
     * For each store with a unique AMQP configuration, consume messages from
     * each configured queue.
     * @return self
     */
    public function processQueues()
    {
        // consume queues for each store with a unique set of AMQP configuration
        foreach ($this->_amqpConfigHelper->getQueueConfigurationScopes() as $store) {
            $this->_consumeStoreQueues($store);
        }
        return $this;
    }
    /**
     * Consume messages from all queues, configured for the given store.
     * @param Mage_Core_Model_Store $store
     * @return self
     */
    protected function _consumeStoreQueues(Mage_Core_Model_Store $store)
    {
        // consume messages from each queue configured within the scope of the store
        foreach ($this->_helper->getConfigModel($store)->queueNames as $queueName) {
            $this->_consumeQueue($queueName, $store);
        }
        return $this;
    }
    /**
     * Fetch messages from the queue within the store scope.
     * @param string $queue Name of the AMQP queue
     * @param Mage_Core_Model_Store $store
     * @return self
     */
    protected function _consumeQueue($queue, Mage_Core_Model_Store $store)
    {
        $sdk = $this->_helper->getSdkAmqp($queue, $store);
        $payloads = $sdk->fetch();
        // avoid use of foreach to allow exception handling during Current
        while ($payloads->valid()) {
            try {
                $payload = $payloads->current();
            } catch (Exception\Payload $e) {
                // log and skip over any messages that cannot be handled by the SDK
                $logData = ['queue' => $queue, 'error_message' => $e->getMessage()];
                $logMessage = 'Received bad payload on queue {queue}: {error_message}';
                $this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData, $e));
                $payloads->next();
                continue;
            }
            $this->_dispatchPayload($payload, $store);
            $payloads->next();
        }
        return $this;
    }
    /**
     * Dispacth an event in Magento with the payload and store scope it was received in.
     * @param IOrderEvent $payload
     * @param Mage_Core_Model_Store $store
     * @return self
     */
    protected function _dispatchPayload(IOrderEvent $payload, Mage_Core_Model_Store $store)
    {
        $eventName = $this->_eventPrefix . '_' . $this->_coreHelper->underscoreWords($payload->getEventType());
        $logData = ['event_name' => $eventName];
        $logMessage = 'Dispatching event "{event_name}" for payload.';
        $this->_logger->info($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
        Mage::dispatchEvent($eventName, array('payload' => $payload, 'store' => $store));
        return $this;
    }
}
