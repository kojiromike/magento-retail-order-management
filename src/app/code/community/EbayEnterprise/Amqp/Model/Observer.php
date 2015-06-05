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

class EbayEnterprise_Amqp_Model_Observer
{
    /** @var EbayEnterprise_Amqp_Helper_Config */
    protected $_configHelper;
    /**
     * @param array $initParams May contain:
     *                          - 'config_helper' => EbayEnterprise_Amqp_Helper_Config
     */
    public function __construct(array $initParams = array())
    {
        list($this->_configHelper) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'config_helper', Mage::helper('ebayenterprise_amqp/config'))
        );
        $this->_configHelper = Mage::helper('ebayenterprise_amqp/config');
    }
    /**
     * Type checks for self::__construct $initParams
     * @param  EbayEnterprise_Amqp_Helper_Config $configHelper
     * @return mixed[]
     */
    protected function _checkTypes(EbayEnterprise_Amqp_Helper_Config $configHelper)
    {
        return array($configHelper);
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
     * Event observer for handling test message payloads from the queue. Expect
     * the observer to contain a TestMessage payload and a Mage_Core_Model_Store.
     * @param Varien_Event_Observer $observer
     * @return self
     */
    public function processTestMessage(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $this->_configHelper->updateLastTimestamp($event->getPayload(), $event->getStore());
        return $this;
    }
}
