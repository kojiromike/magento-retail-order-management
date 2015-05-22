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

class EbayEnterprise_Inventory_Helper_Quantity
{
	/**
	 * Get the quantity requeted for a single item.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @return int
	 */
	public function getRequestedItemQuantity(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		// Child item quantity is the static quantity of the child
		// item within a single unit of the parent. E.g. a configurable
		// product's child item's quantity, will always be 1 - one child in
		// the configurable item. Bundle items, however, will be
		// the number of the child items in a single unit of the
		// parent bundle, depending on how the bundle was configured when
		// added to cart - actual quantity of the child is child
		// quantity * parent quantity.
		$parentItem = $item->getParentItem();
		return $item->getQty() * ($parentItem ? $parentItem->getQty() : 1);
	}

	/**
	 * Calculate the total quantity requested of a given item. All items in the
	 * quote with the same SKU as the given item will be counted toward the
	 * total quantity.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @param Mage_Sales_Model_Quote_Item_Abstract[]
	 * @return int
	 */
	public function calculateTotalQuantityRequested(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		array $allItems
	) {
		$sku = $item->getSku();
		$quantitiesBySku = $this->calculateTotalQuantitiesBySku($allItems);
		return isset($quantitiesBySku[$sku]) ? $quantitiesBySku[$sku] : 0;
	}

	/**
	 * Calculate the total quantity requested for all items in the given set
	 * of items. Quantities will be keyed by unique SKU.
	 *
	 * @param Mage_Sale_Model_Quote_Item_Abstract
	 * @return array Key => value pairs of SKU => quantity
	 */
	public function calculateTotalQuantitiesBySku(array $items)
	{
		$quantitiesBySku = [];
		foreach ($items as $item) {
			$sku = $item->getSku();
			if (!isset($quantitiesBySku[$sku])) {
				$quantitiesBySku[$sku] = 0;
			}
			$quantitiesBySku[$sku] += $this->getRequestedItemQuantity($item);
		}
		return $quantitiesBySku;
	}
}
