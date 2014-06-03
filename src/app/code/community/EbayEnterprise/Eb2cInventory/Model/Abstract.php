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

class EbayEnterprise_Eb2cInventory_Model_Abstract extends Varien_Object
{
	/**
	 * Filter out items that don't need to get sent to eb2c.
	 *
	 * @param Mage_Sales_Model_Quote_Item $item, the item to check if manage stock is enabled
	 */
	public function filterInventoriedItems($item)
	{
		return (bool) ($item->getProduct()->getStockItem()->getManageStock() && !$item->getIsVirtual());
	}

	/**
	 * Filter the array of quote items down to only those that have managed stock
	 * @param  Mage_Sales_Model_Quote_Item[]  $quoteItems Items to filter
	 * @return Mage_Sales_Model_Quote_Item[]  Filtered items
	 */
	public function getInventoriedItems(array $quoteItems)
	{
		return array_filter($quoteItems, array($this, 'filterInventoriedItems'));
	}
}
