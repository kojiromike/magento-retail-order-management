<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Feed_Item_Inventories extends Mage_Core_Model_Abstract
{
	protected $_helper;
	protected $_stockItem;
	protected $_product;

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$this->_helper = $this->_getHelper();
		$this->_stockItem = $this->_getStockItem();
		$this->_product = $this->_getProduct();

		return $this;
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2cinventory');
		}
		return $this->_helper;
	}

	/**
	 * Get cataloginventory/stock_item instantiated object.
	 *
	 * @return cataloginventory/stock_item
	 */
	protected function _getStockItem()
	{
		if (!$this->_stockItem) {
			$this->_stockItem = Mage::getModel('cataloginventory/stock_item');
		}
		return $this->_stockItem;
	}


	/**
	 * Get catalog/product instantiated object.
	 *
	 * @return catalog/product
	 */
	protected function _getProduct()
	{
		if (!$this->_product) {
			$this->_product = Mage::getModel('catalog/product');
		}
		return $this->_product;
	}

	/**
	 * Get the item inventory feed from eb2c.
	 *
	 * @return array, All the feed xml document, from eb2c server.
	 */
	protected function _getItemInventoriesFeeds()
	{
		$feeds = array();
		$localPath = Mage::getBaseDir('var') . DS . $this->_getHelper()->getConfigModel()->feedLocalReceivedPath;
		if (!is_dir($localPath)) {
			umask(0);
			@mkdir($localPath, 0777, true);
		}
		$remoteFile = $this->_getHelper()->getConfigModel()->feedRemoteReceivedPath;
		$configPath =  $this->_getHelper()->getConfigModel()->configPath;
		// downloading feed from eb2c server down to local server
		if ($this->_getHelper()->getFileTransferHelper()->getFile($localPath, $remoteFile, $configPath, null)) {
			$feeds = glob($localPath . '*.xml');
		}
		return $feeds;
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return void
	 */
	public function processFeeds()
	{
		$feeds = $this->_getItemInventoriesFeeds();
		$domDocument = $this->_getHelper()->getDomDocument();
		foreach ($feeds as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			// run inventory updates
			$this->_inventoryUpdates($domDocument);

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				unlink($feed);
			}
		}
	}

	/**
	 * update cataloginventory/stock_item with eb2c feed data.
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _inventoryUpdates($doc)
	{
		$feedXpath = new DOMXPath($doc);

		$inventories = $feedXpath->query('//Inventory');
		foreach ($inventories as $inventory) {
			$gsiClientId = $inventory->getAttribute('gsi_client_id');
			$clientItemId = $feedXpath->query('//Inventory[@gsi_client_id="' . $gsiClientId . '"]/ItemId/ClientItemId');
			$availableQuantity = $feedXpath->query('//Inventory[@gsi_client_id="' . $gsiClientId . '"]/Measurements/AvailableQuantity');

			$sku = '';
			if ($clientItemId->length) {
				$sku = trim($clientItemId->item(0)->nodeValue);
			}

			$qty = 0;
			if ($availableQuantity->length) {
				$qty = (int) $availableQuantity->item(0)->nodeValue;
			}

			if ($sku !== '') {
				// we have a valid item, let's get the product id
				$this->_getProduct()->loadByAttribute('sku', $sku);

				if ($this->_getProduct()->getId()) {
					// we've gotten a valid magento product, let's update its stock
					$this->_getStockItem()->loadByProduct($this->_getProduct()->getId())
    					->setQty($qty)
    					->save();
				}
			}
		}
	}
}
