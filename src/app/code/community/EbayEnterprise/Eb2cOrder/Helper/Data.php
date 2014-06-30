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
{
	/**
	 * Gets a combined configuration model from core and order
	 * @return EbayEnterprise_Eb2cCore_Config_Registry
	 */
	public function getConfig()
	{
		return Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2corder/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 * @param string $operation, the operation type (create, cancel)
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($operation)
	{
		return Mage::helper('eb2ccore')->getApiUri($this->getConfig()->apiService, $operation);
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
		$mageState = Mage::getModel('sales/order_status')
			->getCollection()
			->joinStates()
			->setPageSize(1)
			->addFieldToFilter('label', array('eq' => $eb2cLabelIn))
			->getFirstItem()
			->getState();
		return !empty($mageState) ? $mageState : Mage_Sales_Model_Order::STATE_NEW;
	}
	/**
	 * Retrieve a collection of orders for order history and recent orders blocks based on the current customer in session.
	 * Since these are manually constructed from the Eb2c response, we don't use a real Mage_Sales_Model_Resource_Order_Collection.
	 *
	 * @return Varien_Data_Collection
	 */
	public function getCurCustomerOrders()
	{
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
		$orderSearchObj = Mage::getModel('eb2corder/customer_order_search');
		$helper = Mage::helper('eb2corder');
		$cfg = $helper->getConfig();
		// making eb2c customer order search request base on current session customer id and then
		// parse result in a collection of varien object
		$orderHistorySearchResults = $orderSearchObj->parseResponse(
			$orderSearchObj->requestOrderSummary($cfg->clientCustomerIdPrefix . $customerId)
		);
		$limit = (int) Mage::app()->getRequest()->getParam('limit') ?: -1;
		$orders = Mage::registry('customer_order_search_results') ?: new Varien_Data_Collection();
		if (count($orders) === $limit) {
			return $orders;
		}
		foreach ($orderHistorySearchResults as $orderId => $summaryData) {
			$order = Mage::getModel('eb2corder/customer_order_detail_order_adapter')
				->loadByIncrementId($orderId);
			if ($order->getId()) {
				$orders->addItem($order);
			}
			if ($orders->count() === $limit) {
				break;
			}
		}
		Mage::unregister('customer_order_search_results');
		Mage::register('customer_order_search_results', $orders);
		return $orders;
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
		$prefix = '';
		foreach (Mage::app()->getStores(true) as $store) {
			$prefix = $this->getConfig($store->getId())->clientOrderIdPrefix;
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
	 * perform order detail request and return an object containing the data.
	 * @param  string $orderId
	 * @return Varien_Object
	 */
	public function fetchOrderDetail($orderId)
	{
		$detail = Mage::registry('ebayenterprise_order_detail_response');
		if (!$detail || $detail->getOrder()->getRealOrderId() !== $orderId) {
			Varien_Profiler::start(__METHOD__);
			Mage::unregister('ebayenterprise_order_detail_response');
			$detail = Mage::getModel('eb2corder/customer_order_detail');
			$responseText = $detail->requestOrderDetail($orderId);
			$detail->parseResponse($responseText);
			Mage::register('ebayenterprise_order_detail_response', $detail);
			Varien_Profiler::stop(__METHOD__);
		}
		return $detail;
	}
	/**
	 * remove items from a collection
	 * @param Varien_Data_Collection $items
	 * @return Varien_Data_Collection
	 */
	public function emptyCollection(Varien_Data_Collection $items)
	{
		foreach ($items->getAllIds() as $itemId) {
			$items->removeItemByKey($itemId);
		}
		return $items;
	}
}
