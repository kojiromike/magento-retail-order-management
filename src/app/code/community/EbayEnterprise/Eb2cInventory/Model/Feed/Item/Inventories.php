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

class EbayEnterprise_Eb2cInventory_Model_Feed_Item_Inventories extends EbayEnterprise_Catalog_Model_Feed_Abstract implements EbayEnterprise_Catalog_Interface_Feed
{
    /** @note The _logger property is set up in parent. */

    /**
     * Set up extractor, stock item and stock status models to use while
     * processing the feed. If feed config data hasn't been set, set it to the
     * inventory feed configuration set in config.xml before calling the
     * parent::_construct method.
     * @return self
     */
    protected function _construct()
    {
        $this->addData(array(
            'extractor' => Mage::getModel('eb2cinventory/feed_item_extractor'),
            'stock_item' => Mage::getModel('cataloginventory/stock_item'),
            'stock_status' => Mage::getSingleton('cataloginventory/stock_status'),
        ));

        // get feed config from config registry if it wasn't set through the
        // constructor params
        if (!$this->hasFeedConfig()) {
            $this->setFeedConfig(
                Mage::helper('eb2cinventory')->getConfigModel()->feedDirectoryConfig
            );
        }
        parent::_construct();
    }
    /**
     * Take a DOMDocument loaded with xml to update product inventory.
     * @param EbayEnterprise_Dom_Document $xmlDom
     * @return self
     */
    public function process(EbayEnterprise_Dom_Document $xmlDom)
    {
        return $this->updateInventories($this->getExtractor()->extractInventoryFeed($xmlDom));
    }
    /**
     * Process downloaded feeds from eb2c.
     *
     * @return int number of feeds processed
     */
    public function processFeeds()
    {
        // Capture the number of feeds processed.
        $res = parent::processFeeds();
        // Only trigger reindexing if at least one feed was processed.
        if ($res) {
            Mage::dispatchEvent('inventory_feed_processing_complete', array());
        }
        return $res;
    }
    /**
     * Update the stock item "is_in_stock" status
     * @param Mage_CatalogInventory_Model_Stock_Item $stockItem Stock item for the product being updated
     * @param int $qty Inventory quantity stock item is being set to
     * @return bool if a change was made
     */
    protected function _updateItemIsInStock(Mage_CatalogInventory_Model_Stock_Item $stockItem, $qty)
    {
        $shouldSet = ($qty > $stockItem->getMinQty());
        if ($shouldSet !== $stockItem->getIsInStock()) {
            $stockItem->setIsInStock($shouldSet);
            return true;
        }
        return false;
    }
    /**
     * Set the available quantity for a given item.
     * @param int $id the product id to update
     * @param int $qty the amount to set
     * @return self
     */
    protected function _setProdQty($id, $qty)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')
            ->loadByProduct($id);
        if ($stockItem->getManageStock()) {
            $oldQty = $stockItem->getQty();
            $change = ($oldQty !== $qty);
            if ($change) {
                $stockItem->setQty($qty);
            }
            $change = $change || $this->_updateItemIsInStock($stockItem, $qty, $id);
            if ($change) {
                $stockItem->save();
            }
        } else {
            $this->_logger->warning('Tried to set stock level for non-managed product id ({product_id}).', $this->_context->getMetaData(__CLASS__, ['product_id' => $id]));
        }

        return $this;
    }
    /**
     * Update the inventory level for a given sku.
     * @param string $sku the stock-keeping unit.
     * @param int $qty the new quantity available to promise.
     * @return self
     */
    protected function _updateInventory($sku, $qty)
    {
        // Get product id from sku.
        $id = Mage::getModel('catalog/product')->getIdBySku($sku);
        if ($id) {
            $this->_setProdQty($id, $qty);
        } else {
            $logData = ['sku' => $sku];
            $logMessage = 'SKU ({sku}) not found for inventory update.';
            $this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
        }
        return $this;
    }
    /**
     * Get the sku from the feedItem object
     * @param Varien_Object $feedItem a nested object of item info
     * @return string the sku
     */
    protected function _extractSku(Varien_Object $feedItem)
    {
        return Mage::helper('ebayenterprise_catalog')->normalizeSku(
            $feedItem->getItemId()->getClientItemId(),
            $feedItem->getCatalogId()
        );
    }
    /**
     * Update cataloginventory/stock_item with eb2c feed data.
     * @param array $feedItems the extracted collection of inventory data
     * @return self
     */
    public function updateInventories(array $feedItems)
    {
        $logData = ['total_items' => count($feedItems)];
        $logMessage = 'Updating inventory for {total_items} items';
        $this->_logger->info($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
        foreach ($feedItems as $feedItem) {
            $sku = $this->_extractSku($feedItem);
            $qty = $feedItem->getMeasurements()->getAvailableQuantity();
            $this->_updateInventory($sku, $qty);
        }
        return $this;
    }
}
