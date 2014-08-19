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

class EbayEnterprise_Eb2cProduct_Model_Feed_File
{
	/** @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;
	/**
	 * Array of information about the feed file to be processed. Expected to be
	 * passed to the constructor and *must* contain the following keys:
	 * 'error_file' => filename of the file used for the error confirmation response feed
	 * 'doc' => EbayEnterprise_Dom_Document with the file to be processed loaded in
	 * @var array
	 */
	protected $_feedDetails;

	/**
	 * @var array hold an list of sku to be imported
	 */
	protected $_importedSkus = array();
	/**
	 * The Mage factory method only allows passing a single array of data as
	 * arguments to constructors. As the constructor requires certain data to be
	 * included in the array of data, treat the required keys as much like actual
	 * function arguments as possible - ensure they are all included and if any
	 * are missing, trigger an error indicating the missing keys.
	 * Array data must include the following keys:
	 * 'doc' and 'error_file'
	 * @see self::_feedDetails
	 * @param  array $feedDetails
	 */
	public function __construct(array $feedDetails)
	{
		$this->_log = Mage::helper('ebayenterprise_magelog');
		$missingKeys = array_diff(array('doc', 'error_file'), array_keys($feedDetails));
		if ($missingKeys) {
			trigger_error(
				sprintf('%s called without required feed details: %s missing.', __METHOD__, implode(', ', $missingKeys)),
				E_USER_ERROR
			);
		}
		if (!$feedDetails['doc'] instanceof EbayEnterprise_Dom_Document) {
			trigger_error(
				sprintf('%s called with invalid doc. Must be instance of EbayEnterprise_Dom_Document', __METHOD__),
				E_USER_ERROR
			);
		}
		$this->_feedDetails = $feedDetails;
	}
	/**
	 * @see self::_feedDetails
	 * @return EbayEnterprise_Dom_Document
	 * @codeCoverageIgnore
	 */
	protected function _getDoc()
	{
		return $this->_feedDetails['doc'];
	}
	/**
	 * @see self::_feedDetails
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _getErrorFile()
	{
		return $this->_feedDetails['error_file'];
	}

	/**
	 * Process the file - making necessary deletes and adds/updates.
	 * @param EbayEnterprise_Eb2cCore_Model_Feed_Import_Config_Interface $config
	 *        is a concrete class that implement the interface class and implement
	 *        the method getImportConfigData which will return an array of key/value pair
	 *        of configuration specific to the feed running this method
	 * @param EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	 *        is a concrete class that implements this interface class that has two
	 *        methods ('buildCollection', and 'createNewItem')
	 * @return self
	 */
	public function process(
		EbayEnterprise_Eb2cCore_Model_Feed_Import_Config_Interface $config,
		EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	)
	{
		$cfgData = $config->getImportConfigData();
		$skusToReport = array();
		$this->_removeItemsFromWebsites($cfgData, $items);
		$skusToReport = array_merge($skusToReport, $this->_importedSkus);

		$siteFilters = Mage::helper('eb2cproduct')->loadWebsiteFilters();
		foreach($siteFilters as $siteFilter) {
			$this->_importedSkus = array();
			$this->_processWebsite($siteFilter, $cfgData, $items)
				->_processTranslations($siteFilter, $cfgData, $items);
			$skusToReport = array_merge($skusToReport, $this->_importedSkus);
		}

		if (count($skusToReport) && $cfgData['feed_type'] === 'product') {
			Mage::dispatchEvent(
				'product_feed_process_operation_type_error_confirmation',
				array(
					'feed_detail'    => $this->_feedDetails,
					'skus'           => $skusToReport,
					'operation_type' => 'import'
				)
			);
		}
		return $this;
	}
	/**
	 * Get a list of SKUs marked for deletion in the feed, load all products to
	 * delete into a single collection and delete the collection.
	 * @param array $cfgData
	 * @param EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	 * @return self
	 */
	protected function _removeItemsFromWebsites(array $cfgData, EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items)
	{
		$dData = $this->_getSkusToRemoveFromWebsites($cfgData);
		$this->_log->logDebug('[%s] deleting %d skus', array(__CLASS__, count($dData)));
		if (!empty($dData)) {
			$skus = array_keys($dData);
			$collection = $items->buildCollection($skus);

			if ($collection->count()) {
				$this->_removeFromWebsites($collection, $dData);

				Mage::dispatchEvent(
					'product_feed_process_operation_type_error_confirmation',
					array('feed_detail' => $this->_feedDetails, 'skus' => $skus, 'operation_type' => 'delete')
				);
			}
		}
		return $this;
	}
	/**
	 * given a collection of products and a collection deleted sku data remove
	 * each product that matches in catalog id and client id in a specific website
	 * @param Mage_Catalog_Model_Resource_Product_Collection $collection
	 * @param array $dData
	 * @return self
	 */
	protected function _removeFromWebsites(Mage_Catalog_Model_Resource_Product_Collection $collection, array $dData)
	{
		foreach (Mage::helper('eb2cproduct')->loadWebsiteFilters() as $siteFilter) {
			foreach ($this->_getSkusInWebsite($dData, $siteFilter) as $dSku) {
				$product = $collection->getItemById($dSku);
				if ($product) {
					$product->setWebsiteIds($this->_removeWebsiteId(
						$product->getWebsiteIds(),
						$siteFilter['mage_website_id']
					));
				}
			}
		}
		$collection->save();

		return $this;
	}
	/**
	 * Get an array of SKUs to be deleted from the feed file being processed.
	 * @param array $cfgData
	 * @return array Array of SKUs to delete
	 */
	protected function _getSkusToRemoveFromWebsites(array $cfgData)
	{
		$productHelper = Mage::helper('eb2cproduct');
		$coreHelper = Mage::helper('eb2ccore');
		$result = array();
		$dlDoc = $productHelper->splitDomByXslt($this->_getDoc(), $this->_getXsltPath($cfgData['xslt_deleted_sku'], $cfgData['xslt_module']));
		$xpath = $coreHelper->getNewDomXPath($dlDoc);
		foreach ($xpath->query($cfgData['deleted_base_xpath'], $dlDoc->documentElement) as $item) {
			$catalogId = $item->getAttribute('catalog_id');
			$sku = $coreHelper->normalizeSku($item->nodeValue, $catalogId);

			$result[$sku] = array(
				'gsi_client_id' => $item->getAttribute('gsi_client_id'),
				'catalog_id' => $catalogId,
			);
		}
		return $result;
	}
	/**
	 * given extracted deleted sku map to key gsi_client_id/catalog_id and the website config data get
	 * all the sku to be removed from the website
	 * @param array $dData the extracted data with operation type delete
	 * @param array $wData a specific website configuration data
	 * @return array
	 */
	protected function _getSkusInWebsite(array $dData, array $wData)
	{
		return array_filter(array_keys($dData), function ($sku) use ($dData, $wData) {
				return (
					$dData[$sku]['gsi_client_id'] === $wData['client_id'] &&
					$dData[$sku]['catalog_id'] === $wData['catalog_id']
				);
			});
	}

	/**
	 * given an array of website id, and and website id to remove from it
	 *
	 * @param array $websiteIds
	 * @param string $websiteId the id remove from the list of website ids
	 * @return array
	 */
	protected function _removeWebsiteId(array $websiteIds, $websiteId)
	{
		return array_filter($websiteIds, function ($id) use ($websiteId) {
				return ($websiteId !== $id);
			});
	}
	/**
	 * Get an array of SKUs included in the given DOMXPath.
	 * @param  DOMXPath $xpath
	 * @param array $cfgData
	 * @return array
	 */
	protected function _getSkusToUpdate(DOMXPath $xpath, array $cfgData)
	{
		if (empty($this->_importedSkus)) {
			$updateSkuNodes = $xpath->query($cfgData['all_skus_xpath']);
			$helper = Mage::helper('eb2ccore');
			$cfg = $helper->getConfigModel();
			foreach ($updateSkuNodes as $skuNode) {
				$this->_importedSkus[] = $helper->normalizeSku(trim($skuNode->nodeValue), $cfg->catalogId);
			}
		}
		return $this->_importedSkus;
	}
	/**
	 * Get the path to the given XSLT template. Methods assumes all XSLTs are
	 * in an XSLT directory within the eb2cproduct module directory.
	 * @param  string $templateName File name of the XSLT
	 * @param  string $module the modulename including packagename
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _getXsltPath($templateName, $module)
	{
		return Mage::getModuleDir('', $module) . DS . 'xslt' . DS . $templateName;
	}
	/**
	 * Get a DOMDocument including only the data that should be set for a given
	 * language using the specified XSLT.
	 * @param array $websiteFilter
	 * @param string $template
	 * @param string $module
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _splitByFilter($websiteFilter, $template, $module)
	{
		return Mage::helper('eb2cproduct')->splitDomByXslt(
			$this->_getDoc(),
			$this->_getXsltPath($template, $module),
			array('lang_code' => $websiteFilter['lang_code']),
			array($this, '_xslCallBack'), // Call Back to massage XSL after initial load
			$websiteFilter // Site Context
		);
	}
	/**
	 * load a product collection base on a given set of product data and apply
	 * the product data to the collection and then save
	 * product data is expected to have known SKU in order to load the collection
	 * @param  DOMDocument $itemDataDoc
	 * @param  int $storeId
	 * @param  array $cfgData
	 * @param  EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	 * @return self
	 */
	protected function _importExtractedData(DOMDocument $itemDataDoc, $storeId, array $cfgData, EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items)
	{
		$feedXPath = Mage::helper('eb2ccore')->getNewDomXPath($itemDataDoc);
		$skusToUpdate = $this->_getSkusToUpdate($feedXPath, $cfgData);
		if (count($skusToUpdate)) {
			$collection = $items->buildCollection($skusToUpdate);
			if ($cfgData['feed_type'] === 'product') {
				$collection->setStore($storeId);
			}
			foreach ($feedXPath->query($cfgData['base_item_xpath']) as $itemNode) {
				$this->_updateItem($feedXPath, $itemNode, $collection, $storeId, $cfgData, $items);
			}
			$this->_log->logDebug('[%s] saving collection of %d %s', array(__CLASS__, $collection->getSize(), $cfgData['feed_type']));
			$collection->save();
		}
		return $this;
	}

	/**
	 * Update a single product with data from the feed. Should check for the
	 * product to already exist in the collection and when it does, update the
	 * product in the collection. When the product doesn't exist yet, it should
	 * create a new product, set the extracted data on it and add it to the
	 * collection.
	 *
	 * @param DOMXPath $feedXPath
	 * @param DOMNode $itemNode
	 * @param Varien_Data_Collection $itemCollection
	 * @param int $storeId
	 * @param array $cfgData
	 * @param EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	 * @return self
	 */
	protected function _updateItem(
		DOMXPath $feedXPath,
		DOMNode $itemNode,
		Varien_Data_Collection $itemCollection,
		$storeId, array $cfgData, EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	)
	{
		$extractor = Mage::getSingleton('eb2cproduct/feed_extractor');
		$coreHelper = Mage::helper('eb2ccore');
		$sku = $coreHelper->normalizeSku(
			$extractor->extractSku($feedXPath, $itemNode, $cfgData['extractor_sku_xpath']),
			$coreHelper->getConfigModel()->catalogId
		);
		$websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
		$item = $itemCollection->getItemById($sku);
		if (is_null($item)) {
			$item = $items->createNewItem($sku);
			$item->setWebsiteIds(array($websiteId));
			$itemCollection->addItem($item);
			$this->_log->logDebug('[%s] creating new %s %s', array(__CLASS__, $cfgData['feed_type'], $sku));
		} elseif ($cfgData['feed_type'] === 'product') {
			$item->setUrlKey(false);
		}
		$item->setStoreId($storeId);
		$webSiteIds = array_unique(array_merge($item->getWebsiteIds(), array($websiteId)));
		$item->setWebsiteIds($webSiteIds);
		$item->addData($extractor->extractItem($feedXPath, $itemNode, $item, $cfgData));
		return $this;
	}
	/**
	 * Get all languages configured in Magento and process item data for
	 * each language.
	 * @param array $siteFilter
	 * @param array $cfgData
	 * @param EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	 * @return self
	 */
	protected function _processTranslations(array $siteFilter, array $cfgData, EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items)
	{
		foreach (Mage::helper('eb2ccore/languages')->getLanguageCodesList() as $language) {
			if ($siteFilter['lang_code'] === $language) {
				$this->_processForLanguage($siteFilter, $cfgData, $items);
			}
		}
		return $this;
	}
	/**
	 * Process the feed for a single language by extracting data for the given
	 * language and then importing the data for each store view with that language.
	 * @param  array $siteFilter
	 * @param  array $cfgData
	 * @param  EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	 * @return self
	 */
	protected function _processForLanguage(array $siteFilter, array $cfgData, EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items)
	{
		$this->_log->logDebug('[%s] processing %s language', array(__CLASS__, $siteFilter['lang_code']));
		$splitDoc = $this->_splitByFilter($siteFilter, $cfgData['xslt_single_template_path'], $cfgData['xslt_module']);
		foreach (Mage::helper('eb2ccore/languages')->getStores($siteFilter['lang_code']) as $store) {
			// do not reprocess the default store
			$storeId = $store->getId();
			if ($siteFilter['mage_store_id'] === $storeId && $storeId !== Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {
				$this->_importExtractedData($splitDoc, $storeId, $cfgData, $items);
			}
		}
		return $this;
	}
	/**
	 * Process the feed for this website. Should process the file using
	 * the default language XSLT and the language the default store view is
	 * configured for.
	 * @param array $websiteFilter
	 * @param array $cfgData
	 * @param EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items
	 * @return self
	 */
	protected function _processWebsite(array $websiteFilter, array $cfgData, EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface $items)
	{
		$this->_log->logDebug('[%s] site filter = %s', array(__CLASS__, json_encode($websiteFilter)));
		$mageStoreId = $websiteFilter['mage_store_id'];
		return $this->_importExtractedData(
			$this->_splitByFilter($websiteFilter, $cfgData['xslt_default_template_path'], $cfgData['xslt_module']),
			$mageStoreId, $cfgData, $items
		);
	}
	/**
	 * This is a callback; adds additional template handling for configurable variables that XSLT 1.0 just doesn't do.
	 */
	protected function _xslCallBack(DOMDocument $xslDoc, array $websiteFilter)
	{
		$helper = Mage::helper('eb2cproduct');
		foreach( array('Item', 'PricePerItem', 'Content') as $nodeToMatch) {
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@catalog_id and @catalog_id!='{$websiteFilter['catalog_id']}')]");
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@gsi_client_id and @gsi_client_id!='{$websiteFilter['client_id']}')]");
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@gsi_store_id and @gsi_store_id!='{$websiteFilter['store_id']}')]");
		}
		$xslDoc->loadXML($xslDoc->saveXML());
	}
}
