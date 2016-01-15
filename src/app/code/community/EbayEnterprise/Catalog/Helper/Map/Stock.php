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

class EbayEnterprise_Catalog_Helper_Map_Stock
{
    const STOCK_CONFIG_PATH = 'ebayenterprise_catalog/feed/stock_map';

    /** @var array holding ROM backorder value types */
    protected $stockMap = [];
    /** @var EbayEnterprise_Catalog_Helper_Data */
    protected $catalogHelper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Catalog_Helper_Factory */
    protected $factory;

    public function __construct(array $parameters = [])
    {
        list($this->catalogHelper, $this->coreHelper, $this->factory) = $this->checkTypes(
            $this->nullCoalesce($parameters, 'catalog_helper', Mage::helper('ebayenterprise_catalog')),
            $this->nullCoalesce($parameters, 'core_helper', Mage::helper('eb2ccore')),
            $this->nullCoalesce($parameters, 'factory', Mage::helper('ebayenterprise_catalog/factory'))
        );
        $this->stockMap = $this->nullCoalesce($parameters, 'stock_map', $this->getDefaultStockMap());
    }

    /**
     * extract the SalesClass from DOMNOdeList object and map to a known Magento 'backorder' value
     * @param DOMNodeList $nodes
     * @param Mage_Catalog_Model_Product $product
     * @return null
     */
    public function extractStockData(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        /** @var string */
        $value = $this->coreHelper->extractNodeVal($nodes);
        /** @var int */
        $productId = (int) $product->getId();
        /** @var array */
        $stockData = $this->buildStockData($productId, $value);
        if (!$productId) {
            $product->setNewProductStockData($stockData);
            return null;
        }
        $this->saveStockItem($stockData, $productId);
        return null;
    }

    /**
     * Update stock item data for a product
     *
     * @param array
     * @param int
     * @return self
     */
    public function saveStockItem(array $stockData, $productId)
    {
        $this->factory->getNewStockItemModel()
            ->loadByProduct($productId)
            ->addData($stockData)
            ->save();
        return $this;
    }

    /**
     * Get backorders data.
     *
     * @param string
     * @return int
     */
    protected function getBackOrderData($value)
    {
        return isset($this->stockMap[$value])
            ? (int) $this->stockMap[$value]
            : Mage_CatalogInventory_Model_Stock::BACKORDERS_NO;
    }

    /**
     * Build stock data
     *
     * @param int
     * @param string
     * @return array
     */
    protected function buildStockData($productId, $value)
    {
        $backorders = $this->getBackOrderData($value);
        return array_merge(
            [
                'product_id' => $productId,
                'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
                'backorders' => $backorders,
                'use_config_backorders' => false,
            ],
            $this->getIsInStockData($backorders)
        );
    }

    /**
     * @param int
     * @return array
     */
    protected function getIsInStockData($backorders)
    {
        return $backorders > Mage_CatalogInventory_Model_Stock::BACKORDERS_NO
            ? ['is_in_stock' => true] : [];
    }

    /**
     * Type hinting for self::__construct $parameters
     *
     * @param  EbayEnterprise_Catalog_Helper_Data
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @param  EbayEnterprise_Catalog_Helper_Factory
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Catalog_Helper_Data $catalogHelper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_Catalog_Helper_Factory $factory
    )
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @return array
     */
    protected function getDefaultStockMap()
    {
        return $this->stockMap ?: (array) $this->catalogHelper
            ->getConfigModel()
            ->getConfigData(self::STOCK_CONFIG_PATH);
    }
}
