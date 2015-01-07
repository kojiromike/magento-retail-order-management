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

class EbayEnterprise_Catalog_Helper_Map_Stock extends Mage_Core_Helper_Abstract
{
	const STOCK_CONFIG_PATH = 'ebayenterprise_catalog/feed/stock_map';

	/**
	 * @var array holding ROM backorder value types
	 */
	protected $_stockMap = array();

	/**
	 * @see self::$_stockMap
	 * @return array
	 */
	protected function _getStockMap()
	{
		if (empty($this->_stockMap)) {
			$this->_stockMap = Mage::helper('ebayenterprise_catalog')
				->getConfigModel()
				->getConfigData(self::STOCK_CONFIG_PATH);
		}
		return $this->_stockMap;
	}

	/**
	 * extract the SalesClass from DOMNOdeList object and map to a known Magento 'backorder' value
	 * @param DOMNodeList $nodes
	 * @param Mage_Catalog_Model_Product $product
	 * @return null
	 */
	public function extractStockData(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		$value = Mage::helper('eb2ccore')->extractNodeVal($nodes);
		$mapData = $this->_getStockMap();
		$productId = (int) $product->getId();
		if ($productId) {
			$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
			if ($stockItem) {
				$backorders = isset($mapData[$value]) ?
					(int) $mapData[$value] : Mage_CatalogInventory_Model_Stock::BACKORDERS_NO;
				$stockData = array(
					'product_id' => $productId,
					'stock_id'   => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
					'backorders' => $backorders,
					'use_config_backorders' => false,
				);
				if ($backorders > Mage_CatalogInventory_Model_Stock::BACKORDERS_NO) {
					$stockData['is_in_stock'] = true; // Seems awkward, but you have to set this true if you want to allow an attempt to add to cart.
				}
				$stockItem->addData($stockData)->save();
			}
		}
		return null;
	}
}
