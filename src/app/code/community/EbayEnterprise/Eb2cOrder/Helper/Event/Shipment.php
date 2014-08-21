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

class EbayEnterprise_Eb2cOrder_Helper_Event_Shipment
{
	const CUSTOM_CARRIER_CODE = 'custom';
	const TRACKING_TITLE = 'Custom Value';
	/**
	 * @var EbayEnterprise_MageLog_Helper_Data
	 */
	protected $_log;

	public function __construct()
	{
		$this->_log = Mage::helper('ebayenterprise_magelog');
	}
	/**
	 * Caching known Magento shipping carrier codes and titles as an array.
	 * @var array $_carriers
	 */
	protected $_carriers = array();
	/**
	 * Extract shipment data from a passed-in XML string parameter to build
	 * an array containing increment IDs of orders map to an array of shipment
	 * data. Each shipment data contain an array of order items with product
	 * SKU, item shipped Qty, and an array of tracking data. The tracking data
	 * contain Tracking numbers and Tracking URLs.
	 * @param  string $xml
	 * @return array
	 */
	public function extractShipmentData($xml)
	{
		$data = array();
		if ($xml) {
			$doc = Mage::helper('eb2corder')->loadXml($xml);
			$xpath = Mage::helper('eb2ccore')->getNewDomXPath($doc);
			$prefix = 'a';
			$xpath->registerNamespace($prefix, $doc->documentElement->lookupnamespaceURI(null));
			foreach ($xpath->query("//$prefix:OrderEvent/$prefix:Shipment") as $shipment) {
				$incrementId = $this->_extractValue($xpath, $shipment, "$prefix:OrderHeader/$prefix:WebstoreOrderNumber");
				if ($incrementId) {
					$data[$incrementId] = $this->_extractShipmentDetails($xpath, $shipment, $prefix);
				}
			}
		}
		return $data;
	}
	/**
	 * Extract a node value from a passed in XPath object, a context node and an XPath query string expression.
	 * @param  DOMXPath $xpath
	 * @param  DOMNode $node
	 * @param  string $expression The XPath query string
	 * @return string | null
	 */
	protected function _extractValue(DOMXPath $xpath, DOMNode $node, $expression)
	{
		return Mage::helper('eb2ccore')->extractNodeVal($xpath->query($expression, $node));
	}
	/**
	 * Extract all shipment details for creating or updating a Magento order shipment.
	 * @param  DOMXPath $xpath
	 * @param  DOMNode $shipment
	 * @param  string $prefix The registered name-space for XPath
	 * @return array
	 */
	protected function _extractShipmentDetails(DOMXPath $xpath, DOMNode $shipment, $prefix)
	{
		$details = array();
		foreach ($xpath->query("$prefix:Shipments/$prefix:Shipment", $shipment) as $detail) {
			$shipmentId = $this->_extractValue($xpath, $detail, "$prefix:ShipmentId");
			if ($shipmentId) {
				$details[$shipmentId] = $this->_extractOrderItem($xpath, $xpath->query("$prefix:OrderItems/$prefix:OrderItem", $detail), $prefix);
			}
		}
		return $details;
	}
	/**
	 * Extract shipment data for `sales/order_shipment_item’ instance and `sales/order_shipment_track’ instance.
	 * @param  DOMXPath $xpath
	 * @param  DOMNodeList $orderItems
	 * @param  string $prefix The registered name-space for XPath
	 * @return array
	 */
	protected function _extractOrderItem(DOMXPath $xpath, DOMNodeList $orderItems, $prefix)
	{
		$items = array();
		foreach ($orderItems as $item) {
			$items[] = array(
				'sku' => $this->_extractValue($xpath, $item, "$prefix:Product/$prefix:SKU"),
				'quantity' => $this->_extractValue($xpath, $item, "$prefix:Product/$prefix:Quantity"),
				'tracking' => $this->_extractTrackingInfo(
					$xpath, $xpath->query("$prefix:TrackingNumberList/$prefix:TrackingInfo", $item), $prefix
				)
			);
		}
		return $items;
	}
	/**
	 * Extract shipment data specifically for a `sales/order_shipment_track’ instance.
	 * @param  DOMXPath $xpath
	 * @param  DOMNodeList $trackingInfos
	 * @param  string $prefix The registered name-space for XPath
	 * @return array
	 */
	protected function _extractTrackingInfo(DOMXPath $xpath, DOMNodeList $trackingInfos, $prefix)
	{
		$tracking = array();
		foreach ($trackingInfos as $info) {
			$tracking[] = array(
				'tracking_number' => $this->_extractValue($xpath, $info, "$prefix:TrackingNumber"),
				'tracking_url' => $this->_extractValue($xpath, $info, "$prefix:TrackingURL"),
			);
		}
		return $tracking;
	}
	/**
	 * Build a shipment Qty(s) array to prepare a 'sales/order_shipment' instance.
	 * The array keys will be the values of 'sales/order_item::getItemId' map to the
	 * quantity values from the passed in shipment data array.
	 * @param  array $shipmentData
	 * @param  Varien_Data_Collection $items
	 * @return array
	 */
	protected function _buildShipmentQtys(array $shipmentData=array(), Varien_Data_Collection $items)
	{
		$qtys = array();
		foreach ($shipmentData as $data) {
			$item = $items->getItemByColumnValue('sku', $data['sku']);
			if ($item) {
				$qtys[$item->getItemId()] = $data['quantity'];
			}
		}
		return $qtys;
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
	 * Adding tracks to a 'sales/order_shipment' instance using the passed in shipment data array,
	 * loop through each shipment data item, get the array list of tracking data and prepare the
	 * tracking data to be added to the 'sales/order_shipment_track' object.
	 * @param  array $shipmentData
	 * @param  Mage_Sales_Model_Order_Shipment $shipment
	 * @return self
	 */
	protected function _addTrackingToShipment(array $shipmentData, Mage_Sales_Model_Order_Shipment $shipment)
	{
		foreach ($shipmentData as $data) {
			foreach ($data['tracking'] as $track) {
				$shipment->addTrack($this->_initNewTrackInstance($track));
			}
		}
		return $this;
	}
	/**
	 * Instantiate a new 'sales/order_shipment_track' object and initialize it with a passed in track data.
	 * @param  array $track
	 * @return Mage_Sales_Model_Order_Shipment_Track
	 */
	protected function _initNewTrackInstance(array $track)
	{
		return Mage::getModel('sales/order_shipment_track', $this->_buildTrackData($track));
	}
	/**
	 * Build track data array using passed in track data parameter.
	 * @param  array $track
	 * @return array
	 */
	protected function _buildTrackData(array $track)
	{
		$data = $this->_derivePartialTrackData($track['tracking_url']);
		$data['number'] = $track['tracking_number'];
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
	 * Taking a 'sales/order' instance and an array of shipment data as parameters and then adding
	 * the shipment and tracking information to the `sales/order’ instance. Reconcile any discrepancies
	 * by validating the expected shipment data is indeed in the Magento order; otherwise, log the
	 * discrepancies.
	 * @param  Mage_Sales_Model_Order $order
	 * @param  array $shipmentData
	 * @return self
	 */
	public function process(Mage_Sales_Model_Order $order, array $shipmentData)
	{
		$incrementId = $order->getIncrementId();
		foreach ($shipmentData as $shipmentId => $data) {
			$qtys = $this->_buildShipmentQtys($data, $order->getItemsCollection());
			if (!empty($qtys)) {
				$shipment = $this->_addShipmentToOrder($qtys, $order)
					->setData('increment_id', $shipmentId);
				$this->_addTrackingToShipment($data, $shipment)
					->_registerShipment($shipment, $incrementId)
					->_saveShipment($shipment, $incrementId)
					->_reconcileShipment($data, $shipmentId, $incrementId);
			}
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
	 * Reconcile the successfully created order shipment against the expected shipment data. Log any
	 * discrepancies between the shipment data and the 'sales/order_shipment' data.
	 * @param  array $shipmentData
	 * @param  string $shipmentId
	 * @param  string $orderIncrementId
	 * @return self
	 */
	protected function _reconcileShipment(array $shipmentData, $shipmentId, $orderIncrementId)
	{
		$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
		if (!$shipment->getI()) {
			$logMessage = '[%s] Magento did not add an expected shipment to the following order (id: %s).';
			$this->_log->logWarn($logMessage, array(__CLASS__, $orderIncrementId));
		}
		$this->_reconcileItems($shipment->getItemsCollection(), $shipmentData, 'sku', 'sku', 'item')
			->_reconcileTracks($shipment->getTracksCollection(), $shipmentData);
		return $this;
	}
	/**
	 * Reconcile shipment items by checking if the expected shipment items are in
	 * Magento. Log when the expected items are not found in Magento.
	 * @param  Varien_Data_Collection $items
	 * @param  array $itemData
	 * @param  string $columnName The name of the column to get the item by
	 * @param  string $key The key to extract the value to be passed in to get the item by
	 * @param  string $section The specific shipment section such as shipment item or shipment track
	 * @return self
	 */
	protected function _reconcileItems(Varien_Data_Collection $items, array $itemData, $columnName, $key, $section='item')
	{
		foreach ($itemData as $data) {
			$value = $data[$key];
			$item = $items->getItemByColumnValue($columnName, $value);
			if (is_null($item)) {
				$logMessage = '[%s] Magento did not add an expected-to-be-shipped %s (%s) to the shipment.';
				$this->_log->logWarn($logMessage, array(__CLASS__, $section, $value));
			}
		}
		return $this;
	}
	/**
	 * Reconcile shipment tracks by checking if the expected shipment tracks are in Magento.
	 * Log when the expected tracks are not found in Magento.
	 * @param  Varien_Data_Collection $tracks
	 * @param  array $trackData
	 * @return self
	 */
	protected function _reconcileTracks(Varien_Data_Collection $tracks, array $trackData)
	{
		foreach ($trackData as $itemData) {
			$this->_reconcileItems($tracks, $itemData['tracking'], 'track_number', 'tracking_number', 'track');
		}
		return $this;
	}
}
