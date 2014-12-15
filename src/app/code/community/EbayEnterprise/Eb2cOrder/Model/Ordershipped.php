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

class EbayEnterprise_Eb2cOrder_Model_Ordershipped
{
	/** @var OrderEvents\IOrderShipped $_payload */
	protected $_payload;
	/** @var EbayEnterprise_Eb2cOrder_Helper_Event_Shipment $_shipmentEventHelper */
	protected $_shipmentEventHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/**
	 * @param array $initParams Must have this key:
	 *                          - 'payload' => OrderEvents\IOrderShipped
	 *                          - 'shipmentEventHelper' => EbayEnterprise_Eb2cOrder_Helper_Event_Shipment
	 *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
	 */
	public function __construct(array $initParams=array())
	{
		list($this->_payload, $this->_shipmentEventHelper, $this->_logger) = $this->_checkTypes(
			$initParams['payload'],
			$this->_nullCoalesce($initParams, 'shipment_event_helper', Mage::helper('eb2corder/event_shipment')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog'))
		);
	}
	/**
	 * Type hinting for self::__construct $initParams
	 * @param  OrderEvents\IOrderShipped $payload
	 * @param  EbayEnterprise_Eb2cOrder_Helper_Event_Shipment $shipmentEventHelper
	 * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg
	 * @param  EbayEnterprise_MageLog_Helper_Data $logger
	 * @return array
	 */
	protected function _checkTypes(
		OrderEvents\IOrderShipped $payload,
		EbayEnterprise_Eb2cOrder_Helper_Event_Shipment $shipmentEventHelper,
		EbayEnterprise_MageLog_Helper_Data $logger
	) {
		return array($payload, $shipmentEventHelper, $logger);
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
	 * Return an instance of `sales/order` when the payload have a valid customer increment id,
	 * the order increment id correspond to a sales order in the Magento store, and the order in the Magento
	 * store is shippable. However, if any of these conditions are not met a null value will be returned.
	 * @return Mage_Sales_Model_Order | null
	 */
	protected function _getOrder()
	{
		$incrementId = $this->_payload->getCustomerOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
		if (!$order->getId()) {
			$logMsgOrderNotFound = '[%s] The shipment could not be added. The order (id: %s) was not found in this Magento store.';
			$this->_logger->logWarn($logMsgOrderNotFound, array(__CLASS__, $incrementId));
			return null;
		}
		if (!$order->canShip()) {
			$logMsgOrderNotShippable = '[%s] Order (%s) can not be shipped.';
			$this->_logger->logWarn($logMsgOrderNotShippable, array(__CLASS__, $incrementId));
			return null;
		}
		return $order;
	}
	/**
	 * Processing order shipped event by loading the order using the customer order id
	 * from the payload, if we have a valid order in Magento we proceed to attempt
	 * to add shipment data to the order.
	 * @return self
	 */
	public function process()
	{
		$order = $this->_getOrder();
		if ($order) {
			$this->_shipmentEventHelper->process($order, $this->_payload);
		}
		return $this;
	}
}
