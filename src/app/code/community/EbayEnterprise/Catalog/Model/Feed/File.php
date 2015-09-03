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

class EbayEnterprise_Catalog_Model_Feed_File
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    /** @var EbayEnterprise_Eb2cCore_Helper_Data      $_coreHelper */
    protected $_coreHelper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Languages $_languageHelper */
    protected $_languageHelper;
    /** @var EbayEnterprise_Catalog_Helper_Xslt       $_xsltHelper */
    protected $_xsltHelper;
    /** @var EbayEnterprise_Catalog_Helper_Data       $_helper */
    protected $_helper;
    /** @var EbayEnterprise_Catalog_Model_Indexer_Stub */
    protected $_indexerStub;

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
        $this->_logger = Mage::helper('ebayenterprise_magelog');
        $this->_context = Mage::helper('ebayenterprise_magelog/context');
        $this->_coreHelper = Mage::helper('eb2ccore');
        $this->_languageHelper = Mage::helper('eb2ccore/languages');
        $this->_xsltHelper = Mage::helper('ebayenterprise_catalog/xslt');
        $this->_helper = Mage::helper('ebayenterprise_catalog');
        $this->_indexerStub = Mage::getModel('ebayenterprise_catalog/indexer_stub');

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
     * @param EbayEnterprise_Catalog_Interface_Import_Config $config
     *        is a concrete class that implement the interface class and implement
     *        the method getImportConfigData which will return an array of key/value pair
     *        of configuration specific to the feed running this method
     * @param EbayEnterprise_Catalog_Interface_Import_Items $items
     *        is a concrete class that implements this interface class that has two
     *        methods ('buildCollection', and 'createNewItem')
     * @return self
     */
    public function process(
        EbayEnterprise_Catalog_Interface_Import_Config $config,
        EbayEnterprise_Catalog_Interface_Import_Items $items
    ) {
        $cfgData = $config->getImportConfigData();
        $skusToReport = array();
        $this->_removeItemsFromWebsites($cfgData, $items);

        $siteFilters = $this->_helper->loadWebsiteFilters();
        $processedWebsites = array();
        foreach ($siteFilters as $siteFilter) {
            $this->_importedSkus = array();
            if (!in_array($siteFilter['mage_website_id'], $processedWebsites)) {
                // prevent treating each store view as a website
                $this->_processWebsite($siteFilter, $cfgData, $items);
                $processedWebsites[] = $siteFilter['mage_website_id'];
            }
            $this->_processTranslations($siteFilter, $cfgData, $items);
            $skusToReport = array_unique(array_merge($skusToReport, $this->_importedSkus));
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
     * @param EbayEnterprise_Catalog_Interface_Import_Items $items
     * @return self
     */
    protected function _removeItemsFromWebsites(array $cfgData, EbayEnterprise_Catalog_Interface_Import_Items $items)
    {
        $dData = $this->_getSkusToRemoveFromWebsites($cfgData);
        $logData = ['total_skus' => count($dData)];
        $logMessage = 'deleting {total_skus} skus';
        $this->_logger->info($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
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
        foreach ($this->_helper->loadWebsiteFilters() as $siteFilter) {
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
        $result = array();
        $dlDoc = $this->_helper->splitDomByXslt(
            $this->_getDoc(),
            $this->_getXsltPath($cfgData['xslt_deleted_sku'], $cfgData['xslt_module'])
        );
        $xpath = $this->_coreHelper->getNewDomXPath($dlDoc);
        foreach ($xpath->query($cfgData['deleted_base_xpath'], $dlDoc->documentElement) as $item) {
            $catalogId = $item->getAttribute('catalog_id');
            $sku = $this->_helper->normalizeSku($item->nodeValue, $catalogId);

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
     * @param  array    $cfgData
     * @return array
     */
    protected function _getSkusToUpdate(DOMXPath $xpath, array $cfgData)
    {
        $skusToUpdate = array();
        $updateSkuNodes = $xpath->query($cfgData['all_skus_xpath']);
        $logData = ['total_skus' => $updateSkuNodes->length];
        $logMessage = 'Number of SKUs eligible: "{total_skus}"';
        $this->_logger->info($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
        $catalogId = $this->_coreHelper->getConfigModel()->catalogId;
        foreach ($updateSkuNodes as $skuNode) {
            $skusToUpdate[] = $this->_helper->normalizeSku(
                trim($skuNode->nodeValue),
                $catalogId
            );
        }
        return $skusToUpdate;
    }
    /**
     * Get the path to the given XSLT template. Methods assumes all XSLTs are
     * in an XSLT directory within the ebayenterprise_catalog module directory.
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
        return $this->_helper->splitDomByXslt(
            $this->_getDoc(),
            $this->_getXsltPath($template, $module),
            array('lang_code' => $websiteFilter['lang_code']),
            array($this->_xsltHelper, 'xslCallBack'), // Call Back to massage XSL after initial load
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
     * @param  EbayEnterprise_Catalog_Interface_Import_Items $items
     * @return self
     */
    protected function _importExtractedData(DOMDocument $itemDataDoc, $storeId, array $cfgData, EbayEnterprise_Catalog_Interface_Import_Items $items)
    {
        $feedXPath = $this->_coreHelper->getNewDomXPath($itemDataDoc);
        $skusToUpdate = $this->_getSkusToUpdate($feedXPath, $cfgData);
        if (count($skusToUpdate)) {
            $collection = $items->buildCollection($skusToUpdate);
            if ($cfgData['feed_type'] === 'product') {
                $collection->setStore($storeId);
            }
            foreach ($feedXPath->query($cfgData['base_item_xpath']) as $itemNode) {
                $this->_updateItem($feedXPath, $itemNode, $collection, $storeId, $cfgData, $items);
            }
            $logData = ['total_products' => $collection->getSize(), 'feed_type' => $cfgData['feed_type']];
            $logMessage = 'saving collection of {total_products} {feed_type}';
            $this->_logger->info($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
            // keep track of skus we've processed for the website
            $this->_importedSkus = array_unique(array_merge($this->_importedSkus, $skusToUpdate));

            // Stub the indexer so no indexing can take place during massive saves.
            $indexerKey = '_singleton/index/indexer';
            $oldIndexer = $this->reregister($indexerKey, $this->_indexerStub);
            $collection->save();
            $this->reregister($indexerKey, $oldIndexer);
        }
        return $this;
    }

    /**
     * Replace a value in the Mage::_registry with a new value.
     * If new value is not truthy, just deletes the registry entry.
     *
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    protected function reregister($key, $value=null)
    {
        $old = Mage::registry($key);
        Mage::unregister($key);
        if ($value) {
            Mage::register($key, $value);
        }
        return $old;
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
     * @param EbayEnterprise_Catalog_Interface_Import_Items $items
     * @return self
     */
    protected function _updateItem(
        DOMXPath $feedXPath,
        DOMNode $itemNode,
        Varien_Data_Collection $itemCollection,
        $storeId,
        array $cfgData,
        EbayEnterprise_Catalog_Interface_Import_Items $items
    ) {
        $extractor = Mage::getSingleton('ebayenterprise_catalog/feed_extractor');
        $sku = $this->_helper->normalizeSku(
            $extractor->extractSku($feedXPath, $itemNode, $cfgData['extractor_sku_xpath']),
            $this->_coreHelper->getConfigModel()->catalogId
        );
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $item = $itemCollection->getItemById($sku);
        if (is_null($item)) {
            $item = $items->createNewItem($sku);
            $item->setWebsiteIds(array($websiteId));
            $itemCollection->addItem($item);
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
     * @param EbayEnterprise_Catalog_Interface_Import_Items $items
     * @return self
     */
    protected function _processTranslations(array $siteFilter, array $cfgData, EbayEnterprise_Catalog_Interface_Import_Items $items)
    {
        foreach ($this->_languageHelper->getLanguageCodesList() as $language) {
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
     * @param  EbayEnterprise_Catalog_Interface_Import_Items $items
     * @return self
     */
    protected function _processForLanguage(array $siteFilter, array $cfgData, EbayEnterprise_Catalog_Interface_Import_Items $items)
    {
        $splitDoc = $this->_splitByFilter($siteFilter, $cfgData['xslt_single_template_path'], $cfgData['xslt_module']);
        foreach ($this->_languageHelper->getStores($siteFilter['lang_code']) as $store) {
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
     * @param EbayEnterprise_Catalog_Interface_Import_Items $items
     * @return self
     */
    protected function _processWebsite(array $websiteFilter, array $cfgData, EbayEnterprise_Catalog_Interface_Import_Items $items)
    {
        $mageStoreId = $websiteFilter['mage_store_id'];
        return $this->_importExtractedData(
            $this->_splitByFilter($websiteFilter, $cfgData['xslt_default_template_path'], $cfgData['xslt_module']),
            $mageStoreId,
            $cfgData,
            $items
        );
    }
}
