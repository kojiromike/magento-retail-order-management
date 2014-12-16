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

class EbayEnterprise_Eb2cOrder_Helper_Data extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	/**
	 * Cache of order summary response messages for customerId-orderId keys.
	 * As helpers are singletons, this cache should exist thoughout a request
	 * but no longer.
	 * @var array String responses from the order summary request
	 */
	protected $_orderSummaryResponses = array();
	/**
	 * Cache of Magento order states so as to prevent the order status table
	 * from being queried multiple times.
	 * @var Mage_Sales_Model_Resource_Order_Status_Collection
	 */
	protected $_orderStatusCollection;
	/**
	 * Gets a combined configuration model from core and order
	 * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
	 * @param mixed $store
	 * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('eb2corder/config'));
	}

	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 * @param string $operation, the operation type (create, cancel)
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($operation)
	{
		return Mage::helper('eb2ccore')->getApiUri($this->getConfigModel()->apiService, $operation);
	}

	/**
	 * retrieve order history url
	 * @param Mage_Sales_Model_Order $order, the order object to get the url from
	 * @return string, the url
	 */
	public function getOrderHistoryUrl($order)
	{
		return Mage::getUrl('sales/order/view', array('_store' => $order->getStoreId(), 'order_id' => $order->getId()));
	}

	/**
	 * Retrieves the Magento State mapping to the Eb2c Status passed in eb2cLabelIn
	 * @param eb2cLabelIn - and Eb2c Status Message
	 * @return string mapped state
	 */
	public function mapEb2cOrderStatusToMage($eb2cLabelIn)
	{
		if (is_null($this->_orderStatusCollection)) {
			$this->_orderStatusCollection = Mage::getResourceModel('sales/order_status_collection')
				->joinStates();
		}
		$mageStatus = $this->_orderStatusCollection->getItemByColumnValue('label', $eb2cLabelIn);
		return $mageStatus ? $mageStatus->getState() : Mage_Sales_Model_Order::STATE_NEW;
	}
	/**
	 * Generate a key for the customer id and order id pair. Order summary
	 * searches are based upon these two values so just need to make sure that
	 * given the same customer id and order id, the same response gets returned
	 * from the cache.
	 * @param  string $customerId
	 * @param  string $orderId
	 * @return string
	 */
	protected function _getOrderSummaryCacheKey($customerId, $orderId)
	{
		return sprintf('%s-%s', $customerId, $orderId);
	}
	/**
	 * Get a cached response for the customer id and order id if it exists.
	 * @param  string $customerId
	 * @param  string $orderId
	 * @return string|null cached response from the order summary request
	 */
	public function getCachedOrderSummaryResponse($customerId, $orderId)
	{
		$cacheKey = $this->_getOrderSummaryCacheKey($customerId, $orderId);
		return isset($this->_orderSummaryResponses[$cacheKey]) ?
			$this->_orderSummaryResponses[$cacheKey] :
			null;
	}
	/**
	 * Update the summary response cache. This will overwrite any previously set
	 * responses if given the same customer id and order id.
	 * @param  string $customerId
	 * @param  string $orderId
	 * @param  string $response
	 * @return self
	 */
	public function updateOrderSummaryResponseCache($customerId, $orderId, $response)
	{
		$this->_orderSummaryResponses[$this->_getOrderSummaryCacheKey($customerId, $orderId)] = $response;
		return $this;
	}
	/**
	 * Get a customer object for the current customer via the customer session.
	 * @return Mage_Customer_Model_Customer
	 */
	protected function _getCurrentCustomer()
	{
		return Mage::getSingleton('customer/session')->getCustomer();
	}
	/**
	 * Get a collection of orders for use in the summary display. Only orders
	 * with an increment id in the given set of increment ids should be included.
	 * Orders should only include the order entity_id and order increment_id,
	 * all other data must come from the ROM order summary request so none of it
	 * should be loaded from Magento.
	 * @param  array  $incrementIds
	 * @return Mage_Sales_Model_Resource_Order_Collection
	 */
	protected function _getSummaryOrderCollection($orderSummaryResponses=array())
	{
		$orderSummaryCollection = new Varien_Data_Collection();
		foreach($orderSummaryResponses as $orderSummaryResponse) {
			$orderSummaryCollection->addItem($orderSummaryResponse);
		}
		return $orderSummaryCollection;
	}
	/**
	 * Prefix a customer id with the configured client customer id prefix
	 * @param  string $customerId
	 * @return string
	 */
	public function prefixCustomerId($customerId)
	{
		return Mage::helper('eb2ccore')->getConfigModel()->clientCustomerIdPrefix . $customerId;
	}
	/**
	 * Get the current customer id, prefixed by the client customer prefix
	 * @return string|null null if no current customer logged in
	 */
	protected function _getPrefixedCurrentCustomerId()
	{
		$customerId = $this->_getCurrentCustomer()->getId();
		return $customerId ? $this->prefixCustomerId($customerId) : null;
	}
	/**
	 * Retrieve a collection of orders for the current customer. Orders should
	 * be based upon data retrieved from the order summary service call. Only
	 * orders included in the order summary response and Magento should be
	 * included. Only data from the order summary response + the order entity
	 * id should be included in the order data.
	 * @return Mage_Sales_Model_Resource_Order_Collection
	 */
	public function getCurCustomerOrders()
	{
		$customerId = $this->_getPrefixedCurrentCustomerId();
		if (is_null($customerId)) {
			// when there is no customer, there are no orders
			// return an order collection filtered on a null pk which will ensure the
			// collection will always be empty
			return $this->_getSummaryOrderCollection()->addFieldToFilter('entity_id', null);
		}
		// Search for orders in the OMS for the customer - model handles caching
		// responses so this shouldn't result in any duplicate requests
		$orderHistorySearchResults = Mage::getModel('eb2corder/customer_order_search')
			->getOrderSummaryData($customerId);
		// search results keyed by order increment ids
		return $this->_getSummaryOrderCollection($orderHistorySearchResults);
	}
	/**
	 * Remove a client order id prefix from the increment id. As the prefix on the
	 * increment id may have been any of the configured order id prefixes, need
	 * to check through all possible prefixes configured to find the one to remove.
	 * @param  string $incrementId
	 * @return string
	 */
	public function removeOrderIncrementPrefix($incrementId)
	{
		$coreHelper = Mage::helper('eb2ccore');
		foreach (Mage::app()->getStores(true) as $store) {
			$prefix = $coreHelper->getConfigModel($store->getId())->clientOrderIdPrefix;
			// if the configured prefix matches the start of the increment id, strip
			// off the prefix from the increment
			if (strpos($incrementId, $prefix) === 0) {
				return substr($incrementId, strlen($prefix));
			}
		}
		// must return a string
		return (string) $incrementId;
	}
	/**
	 * Extract all increment ids from an order event xml string.
	 * @param string $xml
	 * @param string $xpath
	 * @return array
	 */
	public function extractOrderEventIncrementIds($xml)
	{
		$xPath = '//WebstoreOrderNumber|//WebOrderId';
		if ($xml) {
			$doc = $this->loadXml($xml);
			$x = Mage::helper('eb2ccore')->getNewDomXPath($doc);
			return $this->_extractData($x->query($xPath));
		}
		return array();
	}
	/**
	 * Take an XML string and load a DOMDocument object.
	 * @param string $xml
	 * @return DOMDocument
	 */
	public function loadXml($xml)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		return $doc;
	}
	/**
	 * Given a DOMNodeList extract all data in it into an array.
	 * @param  DOMNodeList $nodeList
	 * @return array
	 */
	protected function _extractData(DOMNodeList $nodeList)
	{
		$extractedData = array();
		foreach ($nodeList as $node) {
			$extractedData[] = $node->nodeValue;
		}
		return $extractedData;
	}
	/**
	 * Getting a collection of sales/order object that's in an array of increment ids.
	 * @param  array  $incrementIds
	 * @return Mage_Sales_Model_Resource_Order_Collection
	 */
	public function getOrderCollectionByIncrementIds(array $incrementIds=array())
	{
		return Mage::getResourceModel('sales/order_collection')
			->addFieldToFilter('increment_id', array('in' => $incrementIds));
	}
	/**
	 * Calculating the order item gift wrapping row total when the passed in object is
	 * a concrete 'sales/order_item' instance otherwise simply return the gift wrap price.
	 * @param  Varien_Object $item
	 * @return float
	 */
	public function calculateGwItemRowTotal(Varien_Object $item)
	{
		$qty = ($item instanceof Mage_Sales_Model_Order_Item) ? $item->getQtyOrdered() : 1;
		return (float) $qty * $item->getGwPrice();
	}
	/**
	 * Given an amount format according Sale/ Order formatting rules
	 * @param string amount
	 * @return string formatted amount
	 */
	public function formatPrice($amount)
	{
		return Mage::getModel('sales/order')->formatPrice($amount);
	}
}

