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

class EbayEnterprise_Catalog_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /** @var Mage_Index_Model_Indexer */
    protected $indexerStub;

    /**
     * @param array $args May contain key/value for:
     * - logger => EbayEnterprise_MageLog_Helper_Data
     * - log_context => EbayEnterprise_MageLog_Helper_Context
     * - indexer_stub => Mage_Index_Model_Indexer
     */
    public function __construct(array $args = [])
    {
        list(
            $this->logger,
            $this->context,
            $this->indexerStub
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($args, 'indexer_stub', Mage::getModel('ebayenterprise_catalog/indexer_stub'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @param Mage_Index_Model_Indexer
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext,
        Mage_Index_Model_Indexer $indexer
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param string
     * @param array
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * @var int, the default category id
     */
    protected $defaultParentCategoryId = null;

    /**
     * @see self::getCustomAttributeCodeSet - method
     */
    protected $customAttributeCodeSets = [];
    /**
     * @var array boilerplate for initializing a new product with limited information.
     */
    protected $prodTplt;

    /**
     * @throws EbayEnterprise_Catalog_Model_Config_Exception
     * @return array the static defaults for a new product
     */
    protected function getProdTplt()
    {
        if (!$this->prodTplt) {
            $cfg = $this->getConfigModel();
            if (!$this->hasProdType($cfg->dummyTypeId)) {
                throw new EbayEnterprise_Catalog_Model_Config_Exception('Config Error: dummy type id is invalid.');
            }
            $defStockData = [
                'is_in_stock' => $cfg->dummyInStockFlag,
                'manage_stock' => $cfg->dummyManageStockFlag,
                'qty' => (int) $cfg->dummyStockQuantity,
            ];
            $this->prodTplt = [
                'attribute_set_id' => (int) $this->getDefProdAttSetId(),
                'category_ids' => [$this->getDefStoreRootCatId()],
                'description' => $cfg->dummyDescription,
                'price' => (float) $cfg->dummyPrice,
                'short_description' => $cfg->dummyShortDescription,
                'status' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
                'stock_data' => $defStockData,
                'store_ids' => [$this->getDefStoreId()],
                'type_id' => $cfg->dummyTypeId,
                'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                'website_ids' => $this->getAllWebsiteIds(),
                'weight' => (int) $cfg->dummyWeight,
            ];
        }
        return $this->prodTplt;
    }

    /**
     * @return array all website ids
     */
    protected function getAllWebsiteIds()
    {
        return (array) Mage::getModel('core/website')->getCollection()->getAllIds();
    }
    /**
     * @return int the default store id
     */
    protected function getDefStoreId()
    {
        return (int) Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId();
    }
    /**
     * @return int the root category id for the default store
     */
    protected function getDefStoreRootCatId()
    {
        return (int) Mage::app()->getStore()->getRootCategoryId();
    }
    /**
     * @return int the default attribute set id for all products.
     */
    protected function getDefProdAttSetId()
    {
        return (int) Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getDefaultAttributeSetId();
    }
    /**
     * abstracting getting locale code
     * @return string, the locale code
     * @codeCoverageIgnore
     */
    protected function getLocaleCode()
    {
        return Mage::app()->getLocale()->getLocaleCode();
    }

    /**
     * Get the base url for a given store
     *
     * @param $storeId
     * @return string the store base url
     */
    public function getStoreUrl($storeId)
    {
        return Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    }

    /**
     * Set the current store context
     * @param $storeId
     * @return self
     */
    public function setCurrentStore($storeId)
    {
        Mage::app()->setCurrentStore($storeId);
        return $this;
    }
    /**
     * @return string the default locale language code
     */
    public function getDefaultLanguageCode()
    {
        return Mage::helper('eb2ccore')->mageToXmlLangFrmt($this->getLocaleCode());
    }

    /**
     * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
     * Get Product config instantiated object.
     * @param mixed $store
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_catalog/config'));
    }

    /**
     * @param $at
     * @return bool true if the eav config has at least one instance of the given attribute.
     */
    public function hasEavAttr($at)
    {
        return 0 < (int) Mage::getSingleton('eav/config')
            ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $at)
            ->getId();
    }
    /**
     * Get a catalog product eav attribute id for the attribute identified by
     * the given code.
     * @param string $attributeCode Attribute code for the product attribute
     * @return int id of the attribute
     */
    public function getProductAttributeId($attributeCode)
    {
        return Mage::getModel('eav/entity_attribute')
            ->loadByCode('catalog_product', $attributeCode)
            ->getId();
    }
    /**
     * @return bool true if Magento knows about the product type.
     * @param string $type
     */
    public function hasProdType($type)
    {
        $types = Mage_Catalog_Model_Product_Type::getTypes();
        return isset($types[$type]);
    }
    /**
     * extract node value
     * @return string, the extracted content
     * @param DOMNodeList $nodeList
     */
    public function extractNodeVal(DOMNodeList $nodeList)
    {
        return ($nodeList->length) ? $nodeList->item(0)->nodeValue : null;
    }
    /**
     * extract node attribute value
     * @return string, the extracted content
     * @param DOMNodeList $nodeList
     * @param string $attributeName
     */
    public function extractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
    {
        return ($nodeList->length) ? $nodeList->item(0)->getAttribute($attributeName) : null;
    }

    /**
     * get a model loaded with the data for $sku if it exists;
     * otherwise, get a new _UNSAVED_ model populated with dummy data.
     * @param string $sku
     * @param string $name
     * @return Mage_Catalog_Model_Product
     */
    public function prepareProductModel($sku, $name = '')
    {
        $product = $this->loadProductBySku($sku);
        if (!$product->getId()) {
            $this->applyDummyData($product, $sku, $name);
        }
        return $product;
    }

    /**
     * instantiate new product object and apply dummy data to it
     * @param string $sku
     * @param array $additionalData optional
     * @return Mage_Catalog_Model_Product
     */
    public function createNewProduct($sku, array $additionalData = [])
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product');
        return $this->applyDummyData($product, $sku, $additionalData);
    }

    /**
     * Fill a product model with dummy data so that it can be saved and edited later.
     * @see http://www.magentocommerce.com/boards/viewthread/289906/
     * @param  Mage_Catalog_Model_Product $prod product model to be autofilled
     * @param  string $sku the new product's sku
     * @param  array $additionalData optional
     * @return Mage_Catalog_Model_Product
     */
    protected function applyDummyData(Mage_Catalog_Model_Product $prod, $sku, array $additionalData = [])
    {
        $prodData = array_merge($this->getProdTplt(), $additionalData);
        $name = isset($prodData['name']) ? $prodData['name'] : null;
        $prodData['name'] = $name ?: "Incomplete Product: $sku";
        $prodData['sku'] = $prodData['url_key'] = $sku;
        return $prod->addData($prodData);
    }
    /**
     * load product by sku
     * @param string $sku product sku
     * @param null|string|bool|int|Mage_Core_Model_Store $store magento store
     * @return Mage_Catalog_Model_Product
     */
    public function loadProductBySku($sku, $store = null)
    {
        return Mage::helper('catalog/product')->getProduct(
            $sku,
            $store,
            'sku'
        );
    }
    /**
     * Return an array of attribute_codes
     * @param int $attributeSetId
     * @return array
     */
    public function getCustomAttributeCodeSet($attributeSetId)
    {
        if (empty($this->customAttributeCodeSets[$attributeSetId])) {
            $codeSet = [];
            $attributeSet = Mage::getModel('catalog/product_attribute_api')->items($attributeSetId);
            foreach ($attributeSet as $attribute) {
                $codeSet[] = $attribute['code'];
            }
            $this->customAttributeCodeSets[$attributeSetId] = $codeSet;
        }
        return $this->customAttributeCodeSets[$attributeSetId];
    }
    /**
     * Flattens translations into arrays keyed by language
     * @param array $languageSet
     * @return array in the form a['lang-code'] = 'localized value'; empty array if $languageSet is null
     */
    public function parseTranslations(array $languageSet = null)
    {
        $parsedLanguages = [];
        if (!empty($languageSet)) {
            foreach ($languageSet as $language) {
                $parsedLanguages[$language['lang']] = $language['description'];
            }
        }
        return $parsedLanguages;
    }
    /**
     * Sets configurable_attributes_data
     * @param string $productTypeId ('configurable', 'simple' etc).
     * @param Varien_Object $source the source data field
     * @param Mage_Catalog_Model_Product $product the product we are setting
     * @return array of configurable_attributes_data
     */
    public function getConfigurableAttributesData($productTypeId, Varien_Object $source, Mage_Catalog_Model_Product $product)
    {
        if ($product->getId()
                && $product->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                && $productTypeId === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                && $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product)) {
            $logData = ['sku' => $product->getSku()];
            $logMessage = 'Cannot change existing configurable attributes; update discarded for SKU "{sku}"';
            $this->logger->warning($logMessage, $this->context->getMetaData(__CLASS__, $logData));
            return null;
        }
        return $source->getData('configurable_attributes_data');
    }

    /**
     * mapped pattern element with actual values
     *
     * @param array $keyMap a composite array with map key value
     * @param string $pattern the string pattern
     * @return array
     */
    public function mapPattern(array $keyMap, $pattern)
    {
        return array_reduce(array_keys($keyMap), function ($result, $key) use ($keyMap, $pattern) {
            $result = (trim($result) === '')? $pattern : $result;
            return str_replace(sprintf('{%s}', $key), $keyMap[$key], $result);
        });
    }

    /**
     * generate file name by feed type
     * @param string $feedType Type of feed to be processed
     * @param string $format Filename format string to use to build the filename
     * @param array  $overrides use an array to specify the values for the filename.
     * @return string the errorconfirmations file name
     */
    public function generateFileName($feedType, $format, array $overrides = [])
    {
        return $this->mapPattern(
            array_replace(Mage::helper('ebayenterprise_catalog/feed')->getFileNameConfig($feedType), $overrides),
            $format
        );
    }

    /**
     * generate message header by feed type
     * @param string $feedType
     * @return string message header content xml nodes and child nodes
     */
    public function generateMessageHeader($feedType)
    {
        $cfg = Mage::helper('eb2ccore')->getConfigModel(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
        $map = Mage::helper('ebayenterprise_catalog/feed')->getHeaderConfig($feedType);
        $map['event_type'] = $feedType;
        return $this->mapPattern($map, $cfg->feedHeaderTemplate);
    }

    /**
     * Get the absolute path to the global processing directory set
     * in configuration.
     *
     * @throws EbayEnterprise_Catalog_Exception_Feed_File
     * @return string
     */
    public function getProcessingDirectory()
    {
        $helper = Mage::helper('eb2ccore');
        $path = Mage::getBaseDir('var') . DS . Mage::helper('eb2ccore')->getConfigModel()->feedProcessingDirectory;
        $helper->createDir($path);
        if (!$helper->isDir($path)) {
            throw new EbayEnterprise_Catalog_Exception_Feed_File("Can not create the following directory (${path})");
        }
        return $path;
    }

    /**
     * build the outbound file name by feed type
     * @param string $feedType
     * @return string
     */
    public function buildErrorFeedFilename($feedType)
    {
        return $this->getProcessingDirectory() . DS .
            $this->generateFileName(
                $feedType,
                Mage::helper('eb2ccore')->getConfigModel(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
                    ->errorFeedFilenameFormat
            );
    }

    /**
     * get store root category id
     * @return int store root category id
     * @codeCoverageIgnore
     */
    public function getStoreRootCategoryId()
    {
        return Mage::app()->getWebsite(true)->getDefaultStore()->getRootCategoryId();
    }

    /**
     * abstracting getting an array of stores
     * @return array
     * @codeCoverageIgnore
     */
    public function getStores()
    {
        return Mage::app()->getStores(true);
    }

    /**
     * get the storeview language/country code (en-US, fr-FR)
     * @param Mage_Core_Model_Store $store
     * @return string|null
     */
    public function getStoreViewLanguage(Mage_Core_Model_Store $store)
    {
        $storeCodeParsed = explode('_', $store->getName(), 3);
        if (count($storeCodeParsed) > 2) {
            return strtolower(Mage::helper('eb2ccore')->mageToXmlLangFrmt($storeCodeParsed[2]));
        }

        return null;
    }

    /**
     * get parent default category id
     * @return int default parent category id
     */
    public function getDefaultParentCategoryId()
    {
        if (is_null($this->defaultParentCategoryId)) {
            $this->defaultParentCategoryId = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('parent_id', ['eq' => 0])
                ->setPageSize(1)
                ->getFirstItem()
                ->getId();
        }
        return $this->defaultParentCategoryId;
    }

    /**
     * take dom document object and string xslt file template to transform
     * whatever the passed in xslt file template transform the new doc to
     * @param EbayEnterprise_Dom_Document $doc the document got get the nodelist
     * @param string $xsltFilePath the xslt stylesheet template file absolute fulle path
     * @param array $params parameters for the xslt
     * @param callable $postXsltLoadCall function to be called after loading XSLT, but before Processing it
     * @param array $websiteFilter
     * @return EbayEnterprise_Dom_Document
     */
    public function splitDomByXslt(EbayEnterprise_Dom_Document $doc, $xsltFilePath, array $params = [], $postXsltLoadCall = null, $websiteFilter = [])
    {
        $helper = Mage::helper('eb2ccore');
        // create a DOMDocument for the xsl
        $xslDom = $helper->getNewDomDocument();
        $xslDom->load($xsltFilePath);
        if (is_callable($postXsltLoadCall)) {
            call_user_func($postXsltLoadCall, $xslDom, $websiteFilter);
        }
        // load the xsl document into a XSLProcessor
        $xslProcessor = $helper->getNewXsltProcessor();
        $xslProcessor->importStyleSheet($xslDom);
        $xslProcessor->setParameter('', $params);
        // create a DOMDocument from the transformed XML
        $transformed = $helper->getNewDomDocument();
        $transformed->loadXML($xslProcessor->transformToXML($doc));
        return $transformed;
    }

    /**
     * Appends an <xsl:template match='' /> node to the XSLT DOM.
     * XSLT 1.0 won't let us use a variable reference, we have to form the
     * xpath as a string, and build a node and insert it.
     *
     * @param DOMDocument $xslDoc an already loaded DOM
     * @param string $xpathExpression to match
     * @return bool true (node inserted), or false (node insert failed)
     */
    function appendXslTemplateMatchNode($xslDoc, $xpathExpression)
    {
        $templateNode          = $xslDoc->createElement('xsl:template', '', 'http://www.w3.org/1999/XSL/Transform');
        $matchAttribute        = $xslDoc->createAttribute('match');
        $matchAttribute->value = $xpathExpression;
        $templateNode->appendChild($matchAttribute);
        $rc = $xslDoc->documentElement->insertBefore($templateNode);
        return $rc;
    }

    /**
     * Loads a key/ value pair with the relevant config fields of each Magento Web Store which allows us
     * to match an incoming feed to that specific destination.
     *
     * @param $mageStoreId
     * @return array of key/value pairs mapping an inbound feed to the given Magento Web Store.
     */
    protected function loadStoreviewConfig($mageStoreId)
    {
        $config = Mage::helper('eb2ccore')->getConfigModel($mageStoreId);
        return [
            'catalog_id'      => $config->catalogId,
            'client_id'       => $config->clientId,
            'store_id'        => $config->storeId,
            'lang_code'       => $config->languageCode,
            'mage_store_id'   => $mageStoreId,
            'mage_website_id' => Mage::getModel('core/store')->load($mageStoreId)->getWebsiteId(),
        ];
    }

    /**
     * Loads the relevant config fields of each Magento Web Site that allows us
     * to match an incoming feed to the appropriate destination.
     *
     * @TODO Should this actually return filters for every storeview or should
     * it just be websites?
     *
     * @return array of unique key/value pairs mapping an inbound feed to a Magento Web Site.
     */
    public function loadWebsiteFilters()
    {
        $allWebsites = [];
        // Default Store it has its own special configuration.
        $allWebsites[Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID] = $this->loadStoreviewConfig(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {

                    $filter = $this->loadStoreviewConfig($store->getId());
                    $this->logger->debug('Adding filter for storeview.', $this->context->getMetaData(__CLASS__, $filter));
                    $allWebsites[$store->getId()] = $filter;

                }
            }
        }
        return $allWebsites;
    }

    /**
     * Loads the relevant config fields of each Magento Web Site that allows us
     * to match an incoming feed to the appropriate destination. Should only
     * return store views that have configuration that is different than the
     * configuration of the default store view.
     *
     * @TODO This method is currently just loadWebsiteFilters with stores containing
     * the default configuration for feeds removed. I am suspicious of the degree
     * of correctness of loadWebsiteFilters, so this method may not always be definable
     * as is.
     *
     * @return array of unique key/value pairs mapping an inbound feed to a Magento Web Site.
     */
    public function loadStoreviewFilters()
    {
        return $this->uniqueSiteFilters($this->loadWebsiteFilters());
    }

    /**
     * Given a collection of site filters, return only those that represent
     * a unique feed configuration, at the lowest level at which that filter
     * might apply.
     *
     * @param array Store view data, keyed by store id, built by loadWebsiteFilters
     * @return array
     */
    protected function uniqueSiteFilters($siteFilters)
    {
        // Function to create hash of elements of a site filter that we care
        // about being unique.
        $hashSite = function ($site) {
            return sprintf(
                '%s|%s|%s|%s',
                $site['catalog_id'], $site['client_id'], $site['store_id'], $site['lang_code']
            );
        };

        // If there is no default store, no other store can have the same configuration
        // as it, so return all site filters.
        if (!isset($siteFilters[Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID])) {
            return $siteFilters;
        }

        $defaultSite = $siteFilters[Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID];
        $defaultSiteHash = $hashSite($defaultSite);
        // Always include the default site, which will be filtered out in the
        // loop when adding non-default configured sites.
        $uniqueSites = [$defaultSite];

        foreach ($siteFilters as $storeId => $siteFilter) {
            if ($hashSite($siteFilter) !== $defaultSiteHash) {
                $uniqueSites[$storeId] = $siteFilter;
            }
        }

        return $uniqueSites;
    }

    /**
     * get attribute set id by attribute set name
     * @param string $name the attribute set name
     * @return int the entity id of the attribute set or null when not found
     */
    public function getAttributeSetIdByName($name)
    {
        return Mage::getModel('eav/entity_attribute_set')
            ->getCollection()
            ->setEntityTypeFilter(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId())
            ->addFieldToFilter('attribute_set_name', $name)
            ->getFirstItem()
            ->getAttributeSetId();
    }
    /**
     * Get the default store view store id
     * @return int, the default store view store id
     */
    public function getDefaultStoreViewId()
    {
        return (int) Mage::app()->getDefaultStoreView()->getId();
    }
    /**
     * check if a string is a valid ISO-3166-1-alpha-2 code.
     * @param string $name the string to check if it is a valid ISO Country code.
     * @return bool true is valid ISO country code otherwise false.
     */
    public function isValidIsoCountryCode($name)
    {
        $collection = Mage::getResourceModel('directory/country_collection')
            ->addFieldToFilter('iso2_code', $name);
        return ($collection->count() > 0);
    }
    /**
     * Ensure the sku/client id/style id matches the same format expected for skus
     * {catalogId}-{item_id}
     *
     * @param  string $itemId    Product item/style/client/whatevs id
     * @param  string $catalogId Product catalog id
     * @return string            Normalized style id
     */
    public function normalizeSku($itemId, $catalogId)
    {
        if (!empty($itemId)) {
            $pos = strpos($itemId, $catalogId . '-');
            if ($pos === false || $pos !== 0) {
                return sprintf('%s-%s', $catalogId, $itemId);
            }
        }
        return $itemId;
    }

    /**
     * remove the client id found in a given sku {item_id}
     *
     * @param  string $itemId    Product item/style/client/whatevs id
     * @param  string $catalogId Product catalog id
     * @return string            Normalized style id
     */
    public function denormalizeSku($itemId, $catalogId)
    {
        return (!empty($itemId) && strpos($itemId, $catalogId . '-') === 0)?
            str_replace($catalogId . '-', '', $itemId) : $itemId;
    }

    /**
     * given a product object and a country code retrieve the hts_code value for this product
     * matching a given country code
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $countryCode the two letter code for a country (US, CA, DE, etc...)
     * @return string | null the htscode matching the country code for that product otherwise null
     */
    public function getProductHtsCodeByCountry(Mage_Catalog_Model_Product $product, $countryCode)
    {
        $htsCodes = unserialize($product->getHtsCodes());
        if ($htsCodes) {
            foreach ($htsCodes as $htsCode) {
                if ($countryCode === $htsCode['destination_country']) {
                    return $htsCode['hts_code'];
                }
            }
        }

        return null;
    }

    /**
     * Save an EAV collection, disabling the indexer if the collection is
     * larger than a configured size.
     *
     * @param Mage_Eav_Model_Entity_Collection_Abstract
     * @return self
     */
    public function saveCollectionStubIndexer(Mage_Eav_Model_Entity_Collection_Abstract $collection)
    {
        $config = $this->getConfigModel();
        $stubIndexer = $config->maxPartialReindexSkus < $collection->getSize();
        if ($stubIndexer) {
            // Stub the indexer so no indexing can take place during massive saves.
            $indexerKey = '_singleton/index/indexer';
            $oldIndexer = $this->reregister($indexerKey, $this->indexerStub);
        }
        $failureCount = 0;
        $logData = ['product_count' => $collection->getSize()];
        $logMessage = 'Saving {product_count} products with stubbed indexer.';
        $this->logger->info($logMessage, $this->context->getMetaData(__CLASS__, $logData));
        $failMessage = 'Failed to save product with sku {sku}.';
        foreach ($collection as $item) {
            try {
                $item->save();
            } catch (Exception $e) {
                $failureCount++;
                $failLogData = [
                    'sku' => $item->getSku(),
                    'exception' => $e,
                ];
                $this->logger
                    ->logException($e, $this->context->getMetaData(__CLASS__, $failLogData))
                    ->error($failMessage, $this->context->getMetaData(__CLASS__, $failLogData));
            }
        }
        $logMessage = 'Finished saving {product_count} products with {failure_count} failures.';
        $logData['failure_count'] = $failureCount;
        $this->logger->info($logMessage, $this->context->getMetaData(__CLASS__, $logData));
        if ($stubIndexer) {
            $this->reregister($indexerKey, $oldIndexer);
        }
        return $this;
    }

    /**
     * Replace a value in the Mage::_registry with a new value.
     * If new value is not truthy, just deletes the registry entry.
     *
     * @param string
     * @param mixed
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
}
