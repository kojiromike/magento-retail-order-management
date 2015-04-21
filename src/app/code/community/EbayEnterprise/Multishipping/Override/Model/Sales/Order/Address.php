<?php
/**
 * Copyright (c) 2013-2015 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Multishipping_Override_Model_Sales_Order_Address extends Mage_Sales_Model_Order_Address
{
	/** @var EbayEnterprise_Multishipping_Model_Resource_Order_Address_Item_Collection */
	protected $_items;
	/** @var EbayEnterprise_Multishipping_Helper_Factory */
	protected $_multishippingFactory;

	protected function _construct()
	{
		parent::_construct();
		list(
			$this->_multishippingFactory
		) = $this->_checkTypes(
			$this->getData('multishipping_factory') ?: Mage::helper('ebayenterprise_multishipping/factory')
		);
	}

	/**
	 * Enforce type checks on construct args array.
	 *
	 * @param EbayEnterprise_Multishipping_Helper_Factory
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Multishipping_Helper_Factory $multishippingFactory
	) {
		return func_get_args();
	}

	/**
	 * Is this address considered to be the primary address for the order.
	 *
	 * @return bool
	 */
	public function isPrimaryShippingAddress()
	{
		return $this->getOrder()->getShippingAddress()->getId() === $this->getId();
	}

	/**
	 * Retrieve address items collection
	 *
	 * @return Mage_Sales_Model_Resource_Order_Item_Collection
	 */
	public function getItemsCollection()
	{
		if (!$this->_items) {
			$this->_items = $this->_multishippingFactory->createItemCollectionForAddress($this);
		}
		return $this->_items;
	}

	/**
	 * Get all available address items
	 *
	 * @return array
	 */
	public function getAllItems()
	{
		return $this->getItemsCollection()->getItems();
	}

	/**
	 * Retrieve all visible items
	 *
	 * @return array
	 */
	public function getAllVisibleItems()
	{
		return $this->getItemsCollection()->getItemsByColumnValue('parent_id', false);
	}

	/**
	 * Check Quote address has Items
	 *
	 * @return bool
	 */
	public function hasItems()
	{
		return (bool) $this->getItemsCollection()->getSize();
	}

	/**
	 * Get address item object by id without
	 *
	 * @param int
	 * @return Mage_Sales_Model_Orders_Item
	 */
	public function getItemById($itemId)
	{
		return $this->getItemsCollection()->getItemById($itemId);
	}

	/**
	 * Retrieve item object by quote item Id
	 *
	 * @param int
	 * @return Mage_Sales_Model_Orders_Item
	 */
	public function getItemByQuoteItemId($itemId)
	{
		return $this->getItemsCollection()->getItemByColumnValue('quote_item_id', $itemId);
	}

	/**
	 * Remove item from collection
	 *
	 * @param int
	 * @return self
	 */
	public function removeItem($itemId)
	{
		$item = $this->getItemById($itemId);
		if ($item) {
			$item->isDeleted(true);
		}
		return $this;
	}

	/**
	 * Add item to address
	 *
	 * @param   Mage_Sales_Model_Order_Item
	 * @return  Mage_Sales_Model_Order_Address
	 */
	public function addItem(Mage_Sales_Model_Order_Item $item)
	{
		$this->getItemsCollection()->addItem($item->setAddress($this));
		return $this;
	}
}
