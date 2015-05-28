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

class EbayEnterprise_Order_Helper_Data extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var EbayEnterprise_Order_Helper_Factory */
	protected $_factory;

	public function __construct()
	{
		$this->_coreHelper = Mage::helper('eb2ccore');
		$this->_factory = Mage::helper('ebayenterprise_order/factory');
	}

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
			->addConfigModel(Mage::getSingleton('ebayenterprise_order/config'));
	}

	/**
	 * Given an order, returns the public order history URL.
	 *
	 * @param Mage_Sales_Model_Order
	 * @return string
	 */
	public function getOrderHistoryUrl(Mage_Sales_Model_Order $order)
	{
		$params = [
			'oar_billing_lastname' => $order->getCustomerLastname(),
			'oar_email' => $order->getCustomerEmail(),
			'oar_order_id' => $order->getIncrementId(),
			'oar_type' => 'email',
		];
		$mageUrlParams = [
			'_nosid' => true,
			'_query' => $params,
		];
		return Mage::getUrl('sales/guest/view', $mageUrlParams);
	}

	/**
	 * Get the configurable order cancel reasons. Return an array
	 * if the order cancel reasons are configured with data otherwise return null.
	 *
	 * @return array | null
	 */
	protected function _getOrderCancelReason()
	{
		$map = $this->getConfigModel()->cancelReasonMap;
		return is_array($map) ? $map : null;
	}

	/**
	 * Determine if order cancel reasons are configured.
	 *
	 * @return array | null
	 */
	public function hasOrderCancelReason()
	{
		$reasons = $this->_getOrderCancelReason();
		return !empty($reasons);
	}

	/**
	 * Build an order cancel reasons array with key 'value' and `label'.
	 *
	 * @return array
	 */
	public function getCancelReasonOptionArray()
	{
		$reasons = [];
		$map = $this->_getOrderCancelReason() ?: [];
		$reasons[] = ['value' => '', 'label' => ''];
		foreach ($map as $value => $label) {
			$reasons[] = ['value' => $value, 'label' => $label];
		}
		return $reasons;
	}

	/**
	 * Get the order can reason description base on the passed in reason code.
	 * If not found return a null value.
	 *
	 * @param  string
	 * @return string | null
	 */
	public function getCancelReasonDescription($reasonCode)
	{
		$map = (array) $this->_getOrderCancelReason();
		return isset($map[$reasonCode]) ? $map[$reasonCode] : null;
	}

	/**
	 * Prefix a customer id with the configured client customer id prefix
	 * @param  string
	 * @return string
	 */
	public function prefixCustomerId($customerId)
	{
		return $this->_coreHelper->getConfigModel()->clientCustomerIdPrefix . $customerId;
	}

	/**
	 * Get the current customer id, prefixed by the client customer prefix
	 * @return string | null null if no current customer logged in
	 */
	protected function _getPrefixedCurrentCustomerId()
	{
		$customerId = $this->_factory->getCurrentCustomer()->getId();
		return $customerId ? $this->prefixCustomerId($customerId) : null;
	}

	/**
	 * Make ROM order summary search when we have a valid logged in customer
	 * id in the customer session otherwise simply return an empty collection.
	 *
	 * @return Varien_Data_Collection
	 */
	public function getCurCustomerOrders()
	{
		$customerId = $this->_getPrefixedCurrentCustomerId();
		return !is_null($customerId)
			// Search for orders in the OMS for the customer
			? $this->_factory->getNewRomOrderSearch($customerId)->process()
			// when there is no customer, there are no orders
			// return an empty Varien_Data_Collection
			: $this->_coreHelper->getNewVarienDataCollection();
	}

	/**
	 * Remove a client order id prefix from the increment id. As the prefix on the
	 * increment id may have been any of the configured order id prefixes, need
	 * to check through all possible prefixes configured to find the one to remove.
	 * @param  string
	 * @return string
	 */
	public function removeOrderIncrementPrefix($incrementId)
	{
		$stores = $this->_getAllStores();
		foreach ($stores as $store) {
			$prefix = $this->_coreHelper->getConfigModel($store->getId())->clientOrderIdPrefix;
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
	 * Get all stores
	 *
	 * @return array
	 */
	protected function _getAllStores()
	{
		return Mage::app()->getStores(true);
	}
}
