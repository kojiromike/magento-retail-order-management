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

class EbayEnterprise_Eb2cCore_Helper_Quote_Item
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_config;

    public function __construct()
    {
        $this->_config = Mage::helper('eb2ccore')->getConfigModel();
    }

    /**
     * Test if the item needs to have its quantity checked for available
     * inventory.
     * @param  Mage_Sales_Model_Quote_Item $item The item to check
     * @return bool True if inventory is managed, false if not
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
                $childStock = $childItem->getProduct()->getStockItem();
                if ($this->isManagedStock($childStock)) {
                    // This Parent is inventoried. Child's ROM setting is 'No backorders', and Manage Stock check hasn't been manually overridden
                    return true;
                }
            }
            // if none of the children were managed stock, the parent is not inventoried
            return false;
        }
        return $this->isManagedStock($item->getProduct()->getStockItem());
    }

    /**
     * If the inventory configuration allow order item to be backorderable simply check if the
     * Manage stock for the item is greater than zero. Otherwise, if the inventory configuration
     * do not allow order items to be backorderable, then ensure the item is not backorder and has
     * manage stock.
     *
     * @param  Mage_CatalogInventory_Model_Stock_Item
     * @return bool
     */
    protected function isManagedStock(Mage_CatalogInventory_Model_Stock_Item $stock)
    {
        return (
            ($this->_config->isBackorderable || (int) $stock->getBackorders() === Mage_CatalogInventory_Model_Stock::BACKORDERS_NO)
            && $stock->getManageStock() > 0
        );
    }
}
