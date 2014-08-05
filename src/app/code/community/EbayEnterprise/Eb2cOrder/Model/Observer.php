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

class EbayEnterprise_Eb2cOrder_Model_Observer
{
	const BACKORDER_EVENT_NAME = 'backorder';
	/**
	 * @var EbayEnterprise_MageLog_Helper_Data
	 */
	protected $_log;
	/**
	 * @var EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	protected $_orderCfg;
	/**
	 * @return self
	 */
	public function __construct()
	{
		$this->_log = Mage::helper('ebayenterprise_magelog');
		$this->_orderCfg = Mage::helper('eb2corder')->getConfigModel();
	}
	/**
	 * Replace the currently loaded order with a new instance containing
	 * data from the order detail response.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function replaceCurrentOrder(Varien_Event_Observer $observer)
	{
		$order = null;
		try{
			// @see Mage_Sales_Controller_Abstract::_loadValidOrder
			$order = Mage::getModel('eb2corder/detail')
				->injectOrderDetail(Mage::registry('current_order'));
		} catch (EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound $e) {
			Mage::getSingleton('core/session')->addError(Mage::helper('eb2corder')->__($e->getMessage()));
			Mage::app()->getResponse()->setRedirect(
				$this->_getRedirectUrl($observer->getEvent()->getName())
			);
		}
		if ($order) {
			Mage::unregister('current_order');
			Mage::register('current_order', $order);
		}
		return $this;
	}
	/**
	 * Extrapolate the redirect URL base on the observer event name. This is base on
	 * the assumption that if you get to this level that you are either a guest or
	 * a logged in customer. If you are a guest then we presume that you are making
	 * guest order lookup search and that all magento check have validated your order,
	 * however, OMS return a 400 because your order is not in their system, then the
	 * only place to go back to is the guest order form. If you are a logged in customer
	 * and you get to this level and order detail fail you will be redirected to the
	 * order history page. The edge case here is the observer event name is empty
	 * therefore you get redirected to the base URL.
	 * @param string $eventName
	 * @return string
	 */
	protected function _getRedirectUrl($eventName)
	{
		if (trim($eventName)) {
			return (strpos($eventName, 'sales_guest') !== false)
				? Mage::getUrl('sales/guest/form')
				: Mage::getUrl('sales/order/history');
		}
		return Mage::getUrl();
	}
	/**
	 * Observer method to add OMS order summary data to order objects after
	 * loading.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function updateOrdersWithSummaryData($observer)
	{
		$orderHelper = Mage::helper('eb2corder');
		// Collection of orders that need to be updated with order summary response
		// data.
		$orderCollection = $observer->getEvent()->getOrderCollection();
		// array of Varien_Objects keyed by increment id
		// API call is cached in the order search model by customer id so this
		// shouldn't result in duplicate calls
		$summaryData = Mage::getModel('eb2corder/customer_order_search')
			->getOrderSummaryData($orderHelper->prefixCustomerId($orderCollection->getCustomerId()));

		foreach ($orderCollection as $order) {
			$orderData = $summaryData[$order->getIncrementId()];
			$order->addData(array(
				'created_at' => $orderData->getOrderDate(),
				'grand_total' => $orderData->getOrderTotal(),
				'status' => $orderHelper->mapEb2cOrderStatusToMage($orderData->getStatus()),
			));
		}
		return $this;
	}
	/**
	 * register the order and the shipment from the detail response.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function prepareForPrintShipment(Varien_Event_Observer $observer)
	{
		$this->replaceCurrentOrder($observer);
		$order = Mage::registry('current_order');
		Mage::unregister('current_shipment');
		Mage::register('current_shipment', $order->getShipmentsCollection()->getItemById(
			Mage::app()->getRequest()->getParam('shipment_id')
		));
		return $this;
	}
	/**
	 * Listened to the event 'ebayenterprise_order_event_back_order' when it get
	 * dispatch in order to consume and extract order increment ids load a collection
	 * with it and then proceed to update each order state and status to 'holded'
	 * and the configured status respectively.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function updateBackOrderStatus(Varien_Event_Observer $observer)
	{
		$orders = $this->_loadOrdersFromXml(trim($observer->getEvent()->getMessage()));
		if ($orders) {
			foreach ($orders as $order) {
				$this->_updateOrderStatus(
					$order, Mage_Sales_Model_Order::STATE_HOLDED,
					$this->_orderCfg->eventOrderStatusBackorder,
					static::BACKORDER_EVENT_NAME
				);
			}
		}
		return $this;
	}
	/**
	 * Responsible for extracting order increment ids from a passed in xml string
	 * and then load a collection of sales/order that's in the list of increment ids.
	 * @param string $xml
	 * @return Mage_Sales_Model_Resource_Order_Collection | null
	 */
	protected function _loadOrdersFromXml($xml)
	{
		if ($xml) {
			$orderHelper = Mage::helper('eb2corder');
			return $orderHelper->getOrderCollectionByIncrementIds(
				$orderHelper->extractOrderEventIncrementId($xml)
			);
		}
		return null;
	}
	/**
	 * Attempt to update the state and status of a passed in order with the passed
	 * in status and state. Logged any exception that get thrown when saving
	 * the sales/order object.
	 * @param  Mage_Sales_Model_Order $order
	 * @param  string $state
	 * @param  string $status
	 * @param  string $eventName
	 * @return self
	 */
	protected function _updateOrderStatus(Mage_Sales_Model_Order $order, $state, $status, $eventName)
	{
		$order->setState($state, $status);
		try {
			$order->save();
		} catch (Exception $e) {
			// Catching any exception that might be thrown due to saving an order
			// with a configured status and state.
			$this->_log->logInfo(
				'[%s] Exception "%s" was thrown while saving status for order #: %s for the following event %s status.',
				array(__CLASS__, $e->getMessage(), $order->getIncrementId(), $eventName)
			);
		}
		return $this;
	}
}
