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

class EbayEnterprise_Eb2cInventory_Helper_Data extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	protected $_operation;

	public function __construct()
	{
		$cfg = $this->getConfigModel(null);
		$this->_operation = array(
			'allocate_inventory'    => $cfg->apiOptInventoryAllocation,
			'check_quantity'        => $cfg->apiOptInventoryQty,
			'get_inventory_details' => $cfg->apiOptInventoryDetails,
			'rollback_allocation'   => $cfg->apiOptInventoryRollbackAllocation,
		);
	}

	/**
	 * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
	 * Get inventory config instantiated object.
	 * @param mixed $store
	 * @return EbayEnterprise_Eb2cInventory_Model_Config
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('eb2cinventory/config'));
	}

	/**
	 * Getting the NS constant value
	 *
	 * @return string, the ns value
	 */
	public function getXmlNs()
	{
		$cfg = $this->getConfigModel(null);
		return $cfg->apiXmlNs;
	}

	/**
	 * Generate eb2c API operation Uri from configuration settings and constants
	 * @param string $optIndex, the operation index of the associative array
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($optIndex)
	{
		$cfg = $this->getConfigModel(null);
		return Mage::helper('eb2ccore')->getApiUri($cfg->apiService, $this->_operation[$optIndex]);
	}

	/**
	 * Generate eb2c API Universally unique ID used to globally identify to request.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 *
	 * @return string, the request id
	 */
	public function getRequestId($entityId)
	{
		$cfg = Mage::helper('eb2ccore')->getConfigModel(null);
		return implode('-', array($cfg->clientId, $cfg->storeId, $entityId));
	}

	/**
	 * Generate eb2c API Universally unique ID to represent the reservation.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 *
	 * @return string, the reservation id
	 */
	public function getReservationId($entityId)
	{
		$cfg = Mage::helper('eb2ccore')->getConfigModel(null);
		return implode('-', array($cfg->clientId, $cfg->storeId, $entityId));
	}
	/**
	 * Test if the item needs to have its quantity checked for available
	 * inventory.
	 * @param  Mage_Sales_Model_Quote_Item $item The item to check
	 * @return boolean True if inventory is managed, false if not
	 */
	public function isItemInventoried(Mage_Sales_Model_Quote_Item $item)
	{
		// never consider a child product as inventoried, allow the parent deal
		// with inventory and let the child not need to worry about it as the parent
		// will be the item to keep track of qty being ordered.
		// Both checks needed as child items will not have a parent item id prior
		// to being saved and a parent item prior to being added to the parent (e.g.
		// immediately after being loaded from the DB).
		if ($item->getParentItemId() || $item->getParentItem()) {
			return false;
		}
		// when dealing with parent items, if any child of the product is managed
		// stock, consider the entire item as managed stock - allows for the parent
		// config product in the quote to deal with inventory while allowing child
		// products to not care individually
		if ($item->getHasChildren()) {
			foreach ($item->getChildren() as $childItem) {
				if ($childItem->getProduct()->getStockItem()->getManageStock()) {
					return true;
				}
			}
			// if none of the children were managed stock, the parent is not inventoried
			return false;
		}
		// if dealing with a standalone product, no parent or children, use the
		// manage stock setting of that product
		return $item->getProduct()->getStockItem()->getManageStock();
	}
	/**
	 * Filter the array of items down to only those that have stock levels managed by the inventory service
	 * @param  array $quoteItems Array of items to filter
	 * @return array Array of items that are managed stock
	 */
	public function getInventoriedItems(array $quoteItems)
	{
		return array_filter($quoteItems, array($this, 'isItemInventoried'));
	}
	/**
	 * given a Mage_Sales_Model_Quote_Address object determine if it has the required
	 * shipping data to make an inventory detail request
	 * @param Mage_Sales_Model_Quote_Address $address the address object to get data from
	 * @return bool true has the required data otherwise false
	 */
	public function hasRequiredShippingDetail(Mage_Sales_Model_Quote_Address $address)
	{
		return (
			(trim($address->getStreet(1)) !== '') &&
			(trim($address->getCity()) !== '') &&
			(trim($address->getRegionCode()) !== '') &&
			(trim($address->getCountryId()) !== '') &&
			(trim($address->getPostcode()) !== '')
		);
	}
}
