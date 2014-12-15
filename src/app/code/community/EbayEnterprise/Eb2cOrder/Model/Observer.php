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
	/** @var EbayEnterprise_Eb2cOrder_Helper_Event_Shipment */
	protected $_shipmentEventHelper;
	/** @var EbayEnterprise_Eb2cOrder_Helper_Data */
	protected $_orderHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_log;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_orderCfg;
	/** @var EbayEnterprise_Eb2cOrder_Helper_Event */
	protected $_orderEventHelper;

	/**
	 * Initialize properties
	 */
	public function __construct()
	{
		$this->_shipmentEventHelper = Mage::helper('eb2corder/event_shipment');
		$this->_orderHelper = Mage::helper('eb2corder');
		$this->_log = Mage::helper('ebayenterprise_magelog');
		$this->_orderCfg = $this->_orderHelper->getConfigModel();
		$this->_orderEventHelper = Mage::helper('eb2corder/event');
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
			Mage::getSingleton('core/session')->addError(
				$this->_orderHelper->__($e->getMessage()));
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
		$orderHelper = $this->_orderHelper;
		// Collection of orders that need to be updated with order summary response
		// data.
		$orderCollection = $observer->getEvent()->getOrderCollection();
		// array of Varien_Objects keyed by increment id
		// API call is cached in the order search model by customer id so this
		// shouldn't result in duplicate calls
		$customerId = $orderCollection->getCustomerId();
		$prefixCustomerId = $orderHelper->prefixCustomerId($customerId);
		$orderLookup = Mage::getModel('eb2corder/customer_order_search');
		$summaryData = $orderLookup->getOrderSummaryData($prefixCustomerId);

		foreach ($orderCollection as $order) {
			$incrementId = $order->getIncrementId();
			$orderData = $summaryData[$incrementId];
			$orderDate = $orderData->getOrderDate();
			$orderTotal = $orderData->getOrderTotal();
			$omsOrderStatus = $orderData->getStatus();
			$mageOrderStatus =
				$orderHelper->mapEb2cOrderStatusToMage($omsOrderStatus);
			$orderOverlayData = array(
				'created_at'  => $orderDate,
				'grand_total' => $orderTotal,
				'status'      => $mageOrderStatus,
			);
			$order->addData($orderOverlayData);
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
	 * Consume the event 'ebayenterprise_amqp_message_order_rejected'. Pass the payload
	 * from the event down to the 'eb2corder/orderrejected' instance. Invoke the process
	 * method on the 'eb2corder/orderrejected' instance.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function processAmqpMessageOrderRejected(Varien_Event_Observer $observer)
	{
		Mage::getModel('eb2corder/orderrejected', array(
			'payload' => $observer->getEvent()->getPayload(),
			'order_event_helper' => $this->_orderEventHelper,
			'logger' => $this->_log
		))->process();
		return $this;
	}
	/**
	 * Listen for an order cancel event.
	 * Load a collection using the extracted order increment ids.
	 * Update each order's state and status to 'canceled' and the associated status respectively.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function updateCanceledStatus(Varien_Event_Observer $observer)
	{
		$message = trim($observer->getEvent()->getMessage());
		$this->_log->logDebug("\n[%s]: received cancel event with message:\n%s", array(__CLASS__, $message));
		$orderCollection = $this->_loadOrdersFromXml($message);
		$eventName = $observer->getEvent()->getName();
		foreach ($orderCollection as $order) {
			$this->_orderEventHelper->attemptCancelOrder($order, $eventName);
		}
		return $this;
	}
	/**
	 * Responsible for extracting order increment ids from a passed in DOM document
	 * and then load a collection of sales/order instances for any increment ids in
	 * the document.
	 * @param  string $xml
	 * @return Varien_Data_Collection
	 */
	protected function _loadOrdersFromXml($xml)
	{
		return $this->_orderHelper->getOrderCollectionByIncrementIds(
			$this->_orderHelper->extractOrderEventIncrementIds($xml)
		);
	}
	/**
	 * Consume the event 'ebayenterprise_amqp_message_order_shipped'. Pass the payload
	 * from the event down to the 'eb2corder/ordershipped' instance. Invoke the process
	 * method on the 'eb2corder/ordershipped' instance.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function processAmqpMessageOrderShipped(Varien_Event_Observer $observer)
	{
		Mage::getModel('eb2corder/ordershipped', array(
			'payload' => $observer->getEvent()->getPayload(),
			'shipment_event_helper' => $this->_shipmentEventHelper,
			'logger' => $this->_log
		))->process();
		return $this;
	}
}
