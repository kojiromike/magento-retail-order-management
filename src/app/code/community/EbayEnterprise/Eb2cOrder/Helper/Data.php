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
	 * Given an amount format according Sale/ Order formatting rules
	 * @param string amount
	 * @return string formatted amount
	 */
	public function formatPrice($amount)
	{
		return Mage::getModel('sales/order')->formatPrice($amount);
	}
}
