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

use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents;

class EbayEnterprise_Eb2cOrder_Model_Orderrejected
{
	/** @var OrderEvents\OrderRejected $_payload */
	protected $_payload;
	/** @var EbayEnterprise_Eb2cOrder_Helper_Event $_orderEventHelper */
	protected $_orderEventHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/**
	 * @param array $initParams Must have this key:
	 *                          - 'payload' => OrderEvents\OrderRejected
	 *                          - 'orderEventHelper' => EbayEnterprise_Eb2cOrder_Helper_Event
	 *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
	 */
	public function __construct(array $initParams=array())
	{
		list($this->_payload, $this->_orderEventHelper, $this->_logger) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'payload', $initParams['payload']),
			$this->_nullCoalesce($initParams, 'order_event_helper', Mage::helper('eb2corder/event')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog'))
		);
	}
	/**
	 * Type hinting for self::__construct $initParams
	 * @param  OrderEvents\OrderRejected $payload
	 * @param  EbayEnterprise_Eb2cOrder_Helper_Event $orderEventHelper
	 * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg
	 * @param  EbayEnterprise_MageLog_Helper_Data $logger
	 * @return array
	 */
	protected function _checkTypes(
		OrderEvents\OrderRejected $payload,
		EbayEnterprise_Eb2cOrder_Helper_Event $orderEventHelper,
		EbayEnterprise_MageLog_Helper_Data $logger
	) {
		return array($payload, $orderEventHelper, $logger);
	}
	/**
	 * Return the value at field in array if it exists. Otherwise, use the default value.
	 * @param  array $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}
	/**
	 * Processing order rejected event by loading the order using the customer order id
	 * from the payload, if we have a valid order in Magento we proceed to attempt
	 * to cancel the order.
	 * @return self
	 */
	public function process()
	{
		$incrementId = trim($this->_payload->getCustomerOrderId());
		if ($incrementId === '') {
			$this->_logger->logWarn('[%s] Received empty customer order id.', array(__CLASS__));
			return $this;
		}
		$order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
		if (!$order->getId()) {
			$this->_logger->logWarn('[%s] Customer order id %s was not found.', array(__CLASS__, $incrementId));
			return $this;
		}
		// canceling the order
		$this->_orderEventHelper->attemptCancelOrder($order, $this->_payload->getEventType());
		return $this;
	}
}
