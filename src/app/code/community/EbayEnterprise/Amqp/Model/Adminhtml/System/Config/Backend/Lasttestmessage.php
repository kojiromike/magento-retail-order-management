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

class EbayEnterprise_Amqp_Model_Adminhtml_System_Config_Backend_Lasttestmessage extends Mage_Core_Model_Config_Data
{
    const NO_TEST_MESSAGE_RECEIVED = 'EbayEnterprise_Amqp_No_Test_Message_Received';
    const TIMESTAMP_FORMAT = 'c';
    /** @var EbayEnterprise_Amqp_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    protected function _construct()
    {
        $this->_helper = $this->getData('helper') ?: Mage::helper('ebayenterprise_amqp');
        $this->_coreHelper = $this->getData('core_helper') ?: Mage::helper('eb2ccore');
        $this->_logger = $this->getData('logger') ?: Mage::helper('ebayenterprise_magelog');
        $this->_context = Mage::helper('ebayenterprise_magelog/context');
    }
    /**
     * Set the value of the "config" to the last timestamp captured from an AMQP
     * test message.
     * @return self
     */
    protected function _afterLoad()
    {
        $dateTime = $this->_getLastTimestamp();
        $value = $dateTime ? $dateTime->format(self::TIMESTAMP_FORMAT) : $this->_helper->__(self::NO_TEST_MESSAGE_RECEIVED);
        return $this->setValue($value);
    }
    /**
     * Get the last timestamp captured from a test message and return a new DateTime
     * object for the timestamp. If no last test message exists or is not a parseable
     * date time, will return null.
     * @return DateTime|null
     */
    protected function _getLastTimestamp()
    {
        $lastTimestamp = $this->getValue();
        $timestamp = null;
        try {
            // If the value isn't set, don't create a new DateTime. new DateTime(null)
            // gives a DateTime for the current time, which is not desirable here.
            $timestamp = $lastTimestamp ? $this->_coreHelper->getNewDateTime($lastTimestamp) : null;
        } catch (Exception $e) {
            $logData = ['last_timestamp' => $lastTimestamp];
            $logMessage = 'Invalid timestamp for last AMQP test message timestamp: {last_timestamp}.';
            $this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData, $e));
        }
        return $timestamp;
    }
}
