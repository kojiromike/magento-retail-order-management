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

class EbayEnterprise_Eb2cOrder_Helper_Event_Shipment
{
	const CUSTOM_CARRIER_CODE = 'custom';
	const TRACKING_TITLE = 'Custom Value';
	const ENTITY_TYPE = 'shipment';

	/**  @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;
	/** @var Mage_Eav_Model_Config $_eavConfig */
	protected $_eavConfig;

	public function __construct()
	{
		$this->_log = Mage::helper('ebayenterprise_magelog');
		$this->_eavConfig = Mage::getSingleton('eav/config');
	}
	/**
	 * Caching known Magento shipping carrier codes and titles as an array.
	 * @var array $_carriers
	 */
	protected $_carriers = array();
	/**
	 * Build a shipment Qty(s) array to prepare a 'sales/order_shipment' instance.
	 * The array keys will be the values of 'sales/order_item::getItemId' map to the
	 * quantity values from the passed in 'OrderEvents\IOrderItemIterable' instance.
	 * @param  OrderEvents\IOrderItemIterable $orderItems
	 * @param  Varien_Data_Collection $items
	 * @return array
	 */
	protected function _buildShipmentQtys(OrderEvents\IOrderItemIterable $orderItems, Varien_Data_Collection $items)
	{
		$qtys = array();
		foreach ($orderItems as $orderItem) {
			$this->_appendToQtyArray($orderItem, $items, $qtys);
		}
		return $qtys;
	}
	/**
	 * Append a new quantity index to the passed in 'qtys' array when a match Magento
	 * order item is found in the pass in items collection.
	 * @param  OrderEvents\IOrderItem $orderItem
	 * @param  Varien_Data_Collection $items
	 * @param  array $qtys
	 * @return self
	 */
	protected function _appendToQtyArray(OrderEvents\IOrderItem $orderItem, Varien_Data_Collection $items, array &$qtys)
	{
		$item = $items->getItemByColumnValue('sku', $orderItem->getItemId());
		if ($item) {
			// Magento only support integer quantity
			$qtys[$item->getItemId()] = (int) $orderItem->getShippedQuantity();
		}
		return $this;
	}
	/**
	 * Prepare shipment using Qty(s) array for a 'sales/order' object.
	 * @param  array $qtys
	 * @param  Mage_Sales_Model_Order $order
	 * @return Mage_Sales_Model_Order_Shipment
	 */
	protected function _addShipmentToOrder(array $qtys, Mage_Sales_Model_Order $order)
	{
		return $order->prepareShipment($qtys);
	}
	/**
	 * Adding tracks to a 'sales/order_shipment' instance using the passed in 'OrderEvents\IOrderItemIterable' instance,
	 * loop through each shipped order item, get the array list of tracking data and prepare the
	 * tracking data to be added to the 'sales/order_shipment_track' object.
	 * @param  OrderEvents\IOrderItemIterable $orderItems
	 * @param  Mage_Sales_Model_Order_Shipment $shipment
	 * @return self
	 */
	protected function _addTrackingToShipment(OrderEvents\IOrderItemIterable $orderItems, Mage_Sales_Model_Order_Shipment $shipment)
	{
		foreach ($orderItems as $orderItem) {
			$this->_addAllTrackings($orderItem->getTrackingNumbers(), $shipment);
		}
		return $this;
	}
	/**
	 * Add tracks from a passed in 'OrderEvents\ITrackingNumberIterable' instance to a passed in
	 * 'sales/order_shipment' instance.
	 * @param  OrderEvents\ITrackingNumberIterable $trackingNumbers
	 * @param  Mage_Sales_Model_Order_Shipment $shipment
	 * @return self
	 */
	protected function _addAllTrackings(OrderEvents\ITrackingNumberIterable $trackingNumbers, Mage_Sales_Model_Order_Shipment $shipment)
	{
		foreach ($trackingNumbers as $track) {
			$shipment->addTrack($this->_initNewTrackInstance($track));
		}
		return $this;
	}

	/**
	 * Instantiate a new 'sales/order_shipment_track' object and initialize it with a passed in track data.
	 * @param  OrderEvents\ITrackingNumber $track
	 * @return Mage_Sales_Model_Order_Shipment_Track
	 */
	protected function _initNewTrackInstance(OrderEvents\ITrackingNumber $track)
	{
		return Mage::getModel('sales/order_shipment_track', $this->_buildTrackData($track));
	}
	/**
	 * Build track data array using passed in track number instance.
	 * @param  OrderEvents\ITrackingNumber $track
	 * @return array
	 */
	protected function _buildTrackData(OrderEvents\ITrackingNumber $track)
	{
		$data = $this->_derivePartialTrackData($track->getUrl());
		$data['number'] = $track->getTrackingNumber();
		return $data;
	}
	/**
	 * Retrieving an array of carrier code map to title from the 'shipping/config'
	 * model. The carrier codes are being retrieved from configuration and they are being
	 * mapped to an instance of their class.
	 * @see    Mage_Shipping_Model_Config::getAllCarriers
	 * @see    Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Tracking::getCarriers
	 * @return array
	 */
	protected function _getCarriers()
	{
		if (empty($this->_carriers)) {
			$carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId());
			$this->_carriers['custom'] = Mage::helper('sales')->__('Custom Value');
			foreach ($carrierInstances as $code => $carrier) {
				if ($carrier->isTrackingAvailable()) {
					$this->_carriers[$code] = $carrier->getConfigData('title');
				}
			}
		}
		return $this->_carriers;
	}
	/**
	 * Deriving the track carrier code and title using the tracking URL by matching
	 * the occurrences of known configured shipping carrier code against the tracking
	 * host URL. Use custom carrier code and title when no matching configured shipping
	 * carrier code is found in the tracking URL host.
	 * @param  string $trackingUrl
	 * @return array
	 */
	protected function _derivePartialTrackData($trackingUrl)
	{
		$parse = parse_url($trackingUrl);
		$host = $parse['host'];
		foreach ($this->_getCarriers() as $code => $title) {
			if (strpos($host, $code) !== false) {
				return array('carrier_code' => $code, 'title' => $title);
			}
		}
		return array('carrier_code' => static::CUSTOM_CARRIER_CODE, 'title' => static::TRACKING_TITLE);
	}
	/**
	 * Get new shipment increment id
	 * @param  Mage_Sales_Model_Order $order
	 * @return string
	 */
	protected function _getNewShipmentIncrementId(Mage_Sales_Model_Order $order)
	{
		return $this->_eavConfig->getEntityType(static::ENTITY_TYPE)->fetchNewIncrementId($order->getStoreId());
	}
	/**
	 * Taking a 'sales/order' instance and an instance of 'OrderEvents\IOrderShipped' as parameters and then adding
	 * the shipment and tracking information to the 'sales/order' instance. Reconcile any discrepancies
	 * by validating the expected shipment data is indeed in the Magento order; otherwise, log the
	 * discrepancies.
	 * @param  Mage_Sales_Model_Order $order
	 * @param  OrderEvents\IOrderShipped $payload
	 * @return self
	 */
	public function process(Mage_Sales_Model_Order $order, OrderEvents\IOrderShipped $payload)
	{
		$orderItems = $payload->getOrderItems();
		$qtys = $this->_buildShipmentQtys($orderItems, $order->getItemsCollection());
		if (!empty($qtys)) {
			$incrementId = $order->getIncrementId();
			$shipmentId = $this->_getNewShipmentIncrementId($order);
			$shipment = $this->_addShipmentToOrder($qtys, $order)
				->setData('increment_id', $shipmentId);
			$this->_addTrackingToShipment($orderItems, $shipment)
				->_registerShipment($shipment, $incrementId)
				->_saveShipment($shipment, $incrementId)
				->_reconcileShipment($orderItems, $shipmentId, $incrementId);
		}
		return $this;
	}
	/**
	 * @see Mage_Sales_Model_Order_Shipment::register
	 * Registering all shipment items.
	 * @param  Mage_Sales_Model_Order_Shipment $shipment
	 * @param  string $incrementId
	 * @return self
	 */
	protected function _registerShipment(Mage_Sales_Model_Order_Shipment $shipment, $incrementId)
	{
		try {
			// attempting to register all shipment items.
			$shipment->register();
		} catch (Mage_Core_Exception $e) {
			$logMessage = '[%s] Exception "%s" was thrown while registering shipment items for order (id: %s).';
			$this->_log->logErr($logMessage, array(__CLASS__, $e->getMessage(), $incrementId));
		}
		return $this;
	}
	/**
	 * Saving shipment and order in one transaction.
	 * @param  Mage_Sales_Model_Order_Shipment $shipment
	 * @param  string $incrementId
	 * @return self
	 */
	protected function _saveShipment(Mage_Sales_Model_Order_Shipment $shipment, $incrementId)
	{
		$order = $shipment->getOrder();
		$order->setIsInProcess(true);
		$transactionSave = Mage::getModel('core/resource_transaction')
			->addObject($shipment)
			->addObject($order);
		try {
			$transactionSave->save();
		} catch (Exception $e) {
			// Logging error when Exception is thrown while saving order shipment data.
			$logMessage = '[%s] Exception "%s" was thrown while saving shipment confirmation data to the order (id: %s).';
			$this->_log->logErr($logMessage, array(__CLASS__, $e->getMessage(), $incrementId));
		}
		return $this;
	}
	/**
	 * Reconcile the successfully created order shipment against the expected payload. Log any
	 * discrepancies between the shipment data and the 'sales/order_shipment' data.
	 * @param  OrderEvents\IOrderItemIterable $orderItems
	 * @param  string $shipmentId
	 * @param  string $orderIncrementId
	 * @return self
	 */
	protected function _reconcileShipment(OrderEvents\IOrderItemIterable $orderItems, $shipmentId, $orderIncrementId)
	{
		$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
		if (!$shipment->getId()) {
			$logMessage = '[%s] Magento did not add an expected shipment to the following order (id: %s).';
			$this->_log->logWarn($logMessage, array(__CLASS__, $orderIncrementId));
		}
		$this->_reconcileItems($shipment->getItemsCollection(), $orderItems)
			->_reconcileTracks($shipment->getTracksCollection(), $orderItems);
		return $this;
	}
	/**
	 * Reconcile shipment items by checking if the expected shipment items are in
	 * Magento. Log when the expected items are not found in Magento.
	 * @param  Varien_Data_Collection $items
	 * @param  OrderEvents\IOrderItemIterable $orderItems
	 * @return self
	 */
	protected function _reconcileItems(Varien_Data_Collection $items, OrderEvents\IOrderItemIterable $orderItems)
	{
		foreach ($orderItems as $orderItem) {
			$this->_verifyItem($items, $orderItem);
		}
		return $this;
	}
	/**
	 * Verify that the passed in order item is in the collection of Magento order items.
	 * @param  Varien_Data_Collection $items
	 * @param  OrderEvents\IOrderItem $orderItem
	 * @return self
	 */
	protected function _verifyItem(Varien_Data_Collection $items, OrderEvents\IOrderItem $orderItem)
	{
		$sku = $orderItem->getItemId();
		$item = $items->getItemByColumnValue('sku', $sku);
		if (is_null($item)) {
			$logMessage = '[%s] Magento did not add an expected-to-be-shipped item (%s) to the shipment.';
			$this->_log->logWarn($logMessage, array(__CLASS__, $sku));
		}
		return $this;
	}
	/**
	 * Reconcile shipment tracks by checking if the expected shipment tracks are in Magento.
	 * Log when the expected tracks are not found in Magento.
	 * @param  Varien_Data_Collection $tracks
	 * @param  OrderEvents\IOrderItemIterable $orderItems
	 * @return self
	 */
	protected function _reconcileTracks(Varien_Data_Collection $tracks, OrderEvents\IOrderItemIterable $orderItems)
	{
		foreach ($orderItems as $orderItem) {
			$this->_verifyAllTracks($tracks, $orderItem->getTrackingNumbers());
		}
		return $this;
	}
	/**
	 * Loop through each track and verify it exists in Magento.
	 * @param  Varien_Data_Collection $tracks
	 * @param  OrderEvents\ITrackingNumberIterable $trackingNumbers
	 * @return self
	 */
	protected function _verifyAllTracks(Varien_Data_Collection $tracks, OrderEvents\ITrackingNumberIterable $trackingNumbers)
	{
		foreach ($trackingNumbers as $trackingNumber) {
			$this->_verifyTrack($tracks, $trackingNumber);
		}
		return $this;
	}
	/**
	 * Verify that the passed in track number is in the collections of Magento shipment tracks, otherwise logs warning message.
	 * @param  Varien_Data_Collection $tracks
	 * @param  OrderEvents\ITrackingNumber $trackingNumber
	 * @return self
	 */
	protected function _verifyTrack(Varien_Data_Collection $tracks, OrderEvents\ITrackingNumber $trackingNumber)
	{
		$number = $trackingNumber->getTrackingNumber();
		$track = $tracks->getItemByColumnValue('track_number', $number);
		if (is_null($track)) {
			$logMessage = '[%s] Magento did not add an expected-to-be-shipped Track number (%s) to the shipment.';
			$this->_log->logWarn($logMessage, array(__CLASS__, $number));
		}
		return $this;
	}
}
