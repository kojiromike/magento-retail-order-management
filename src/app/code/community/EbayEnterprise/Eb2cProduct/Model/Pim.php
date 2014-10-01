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


class EbayEnterprise_Eb2cProduct_Model_Pim
{
	const XML_TEMPLATE = '<%1$s xmlns:xsi="%2$s" xsi:schemaLocation="%3$s">%4$s</%1$s>';
	const XMLNS = 'http://www.w3.org/2001/XMLSchema-instance';

	const KEY_EVENT_TYPE = 'event_type';
	const KEY_SCHEMA_LOCATION = 'schema_location';
	const KEY_ROOT_NODE = 'root_node';
	const KEY_FILE_PATTERN = 'file_pattern';
	const KEY_MAPPINGS = 'mappings';
	const KEY_IS_VALIDATE = 'is_validate';
	const KEY_ITEM_NODE = 'item_node';

	const KEY_DOC = 'doc';
	const KEY_CORE_FEED = 'core_feed';
	const KEY_BATCH = 'batch';

	const WARNING_CANNOT_GENERATE_FEED = '[%s] %s could not be generated because of missing required product data or the sku exceeded %d characters.';

	/**
	 * document object used when building the feed contents
	 * @var EbayEnterprise_Dom_Document
	 */
	protected $_doc;
	/**
	 * core feed model used to handle the file system
	 * @var EbayEnterprise_Eb2cCore_Model_Feed
	 */
	protected $_coreFeed;
	/**
	 * collection of PIM Product instances
	 * @var EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection
	 */
	protected $_pimProducts;
	// @var EbayEnterprise_Eb2cProduct_Model_Pim_Batch
	protected $_batch;
	/**
	 * Set up the doc, batch and core feed
	 */
	public function __construct(array $initParams=array())
	{
		list($this->_batch, $this->_coreFeed, $this->_doc) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, self::KEY_BATCH, Mage::getModel('eb2cproduct/pim_batch')),
			$this->_nullCoalesce($initParams, self::KEY_CORE_FEED, $this->_setUpCoreFeed()),
			$this->_nullCoalesce($initParams, self::KEY_DOC, Mage::helper('eb2ccore')->getNewDomDocument())
		);
	}
	/**
	 * Just for type hinting
	 *
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Batch $batch
	 * @param  EbayEnterprise_Eb2cCore_Model_Feed         $coreFeed
	 * @param  EbayEnterprise_Dom_Document                $doc
	 * @return array the arguments
	 */
	public function _checkTypes(
		EbayEnterprise_Eb2cProduct_Model_Pim_Batch $batch,
		EbayEnterprise_Eb2cCore_Model_Feed $coreFeed,
		EbayEnterprise_Dom_Document $doc
	) {
		return array($batch, $coreFeed, $doc);
	}
	/**
	 * return the $field element of the array if it exists;
	 * otherwise return $default
	 * @param  array  $arr
	 * @param  string $field
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr=array(), $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}
	/**
	 * retrieve the config for the feed.
	 * @see self::$_batch
	 * @return array
	 */
	protected function _getFeedConfig()
	{
		return $this->_batch->getFeedTypeConfig();
	}
	/**
	 * Get the id of the default store view for the current batch
	 * @return int store view id
	 */
	protected function _getDefaultStoreViewId()
	{
		return $this->_batch->getDefaultStore()->getId();
	}
	/**
	 * generate the outgoing product feed.
	 * @param  array  $productIds     list of product id's to include in the feed.
	 * @param  array  $feedTypeConfig configuration data for the current feed.
	 * @param  array  $stores         list of stores to use as context for getting values.
	 * @return array  array of full filepath where the feed files was saved.
	 */
	public function buildFeed()
	{
		Mage::helper('ebayenterprise_magelog')->logDebug(
			"[%s] Exportable Entity Ids:\n%s",
			array(__CLASS__, json_encode($this->_batch->getProductIds()))
		);
		$feedFilePath = '';
		$feedDataSet = $this->_createFeedDataSet();
		if ($feedDataSet->count()) {
			$feedFilePath = $this->_getFeedFilePath();
			$feedDoc = $this->_createDomFromFeedData($feedDataSet);
			$feedDoc->save($feedFilePath);
		} else {
			$skuLength = EbayEnterprise_Eb2cProduct_Helper_Pim::MAX_SKU_LENGTH;
			Mage::helper('ebayenterprise_magelog')->logWarn(
				static::WARNING_CANNOT_GENERATE_FEED,
				array( __METHOD__, basename($feedFilePath), $skuLength)
			);
		}
		return $feedFilePath;
	}
	/**
	 * Build all PIM Product instances used to build the feed.
	 * @return EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection collection of PIM Product instances
	 */
	protected function _createFeedDataSet()
	{
		/* The DOM nodes must be in a specific order. Because it has all product details, we process the default store first -
		 * as a side-effect, Other store views contain only the differences from the default store. If those views were
		 * processed first, the DOM nodes would be created out of order.
		 *
		 * The curating of the productIds array is another side-effect of _createProductCollectionForStore. During each pass,
		 * Exceptions thrown on invalid products /remove/ those products from $pimProducts. We also need to remove elements
		 * from the productIds array so subsequent passes don't try to process them.
		 */
		$productIds = $this->_batch->getProductIds();
		/** @var EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts */
		$pimProducts = Mage::getModel('eb2cproduct/pim_product_collection');
		$pimProducts = $this->_processProductCollection(
			$this->_createProductCollectionForStore($productIds, $this->_batch->getDefaultStore()),
			$pimProducts,
			$productIds
		);
		/* Process the remaining store views: */
		foreach ($this->_batch->getStores() as $store) {
			$pimProducts = $this->_processProductCollection(
				$this->_createProductCollectionForStore($productIds, $store),
				$pimProducts,
				$productIds
			);
		}
		return $pimProducts;
	}
	/**
	 * build out the dom document with the supplied feed data.
	 *
	 * @param EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 * @return EbayEnterprise_Dom_Document dom document representing the feed
	 */
	protected function _createDomFromFeedData(EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts)
	{
		$config = $this->_getFeedConfig();
		$this->_startDocument();
		$doc = $this->_doc;

		$pimRoot = $doc->documentElement;
		$itemNode = $config[self::KEY_ITEM_NODE];
		foreach ($pimProducts->getItems() as $pimProduct) {
			$itemFragment = $this->_buildItemNode($pimProduct, $itemNode);
			if ($itemFragment->hasChildNodes()) {
				$pimRoot->appendChild($itemFragment);
			}
		}
		if (Mage::helper('eb2ccore')->parseBool($config[self::KEY_IS_VALIDATE])) {
			$this->_validateDocument();
		}
		return $doc;
	}
	/**
	 * setup a collection of products for a specific store.
	 * @param  array                              $productIds list of product entity id's
	 * @param  Mage_Core_Model_Store              $store      magento store
	 * @return Mage_Catalog_Model_Resource_Product_Collection product collection
	 */
	protected function _createProductCollectionForStore(array $productIds, Mage_Core_Model_Store $store)
	{
		return Mage::getResourceModel('catalog/product_collection')
			->setStore($store)
			->addExpressionAttributeToSelect('pim_language_code', "('" . $store->getLanguageCode() . "')", array())
			->addAttributeToSelect('*')
			->addFieldToFilter('entity_id', array('in' => $productIds));
	}
	/**
	 * Process all of the products within a given store.
	 *
	 * @param  Mage_Catalog_Model_Resource_Product_Collection $products products for a specific store
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 * @param  string $key
	 * @param array $productIds
	 * @return EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 */
	protected function _processProductCollection(
		Mage_Catalog_Model_Resource_Product_Collection $products,
		EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts,
		array &$productIds = null
	)
	{
		$excludedProductIds = array();
		$currentStoreId = $products->getStoreId();
		$config = Mage::helper('eb2ccore')->getConfigModel($currentStoreId);
		$clientId = $config->clientId;
		$catalogId = $config->catalogId;
		foreach ($products->getItems() as $product) {
			$product->setStoreId($currentStoreId);
			$pimProduct = $pimProducts->getItemForProduct($product);
			if (!$pimProduct) {
				$pimProduct = Mage::getModel('eb2cproduct/pim_product', array(
					'client_id' => $clientId, 'catalog_id' => $catalogId, 'sku' => $product->getSku()
				));
				$pimProducts->addItem($pimProduct);
			}
			try {
				$pimProduct->loadPimAttributesByProduct(
					$product, $this->_doc, $this->_getFeedConfig(), $this->_getFeedAttributes($currentStoreId)
				);
			} catch(EbayEnterprise_Eb2cProduct_Model_Pim_Product_Validation_Exception $e) {
				Mage::helper('ebayenterprise_magelog')->logWarn(
					'[%s] Product excluded from export (%s)',
					array( __METHOD__, $e->getMessage())
				);
				$excludedProductIds[]= $product->getId();
				$pimProducts->deleteItem($pimProduct);
			}
		}
		if($productIds) {
			$productIds = array_diff($productIds, $excludedProductIds);
		}
		return $pimProducts;
	}
	/**
	 * Take a key for the map export feed configuration and a store id
	 * determine if the passed in store id is for the default store view
	 * if so return all mapped attributes otherwise return only
	 * translatable attributes.
	 * @param int $storeId the entity id of Mage_Core_Model_Store
	 * @return array
	 */
	protected function _getFeedAttributes($storeId)
	{
		$config = $this->_getFeedConfig();
		return ((int) $storeId === (int) $this->_getDefaultStoreViewId())?
			array_keys($config[self::KEY_MAPPINGS]) :
			$this->_getTranslatableAttributes($config[self::KEY_MAPPINGS]);
	}
	/**
	 * get only translatable attributes
	 * @param array $mapAttributes
	 * @return array
	 */
	protected function _getTranslatableAttributes(array $mapAttributes)
	{
		return array_filter(array_map(function($key) use ($mapAttributes) {
				return ($mapAttributes[$key]['translate'] === '1') ? $key : null;
			}, array_keys($mapAttributes)));
	}
	/**
	 * Create DOMDocumentFragment with the <Item> node.
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Product $pimProduct
	 * @param string $childNode
	 * @return DOMDocumentFragment
	 */
	protected function _buildItemNode(EbayEnterprise_Eb2cProduct_Model_Pim_Product $pimProduct, $childNode)
	{
		$fragment = $this->_doc->createDocumentFragment();
		$attributes = $pimProduct->getPimAttributes();
		if (count($attributes)) {
			$itemNode = $fragment->appendChild($this->_doc->createElement($childNode));
			foreach (array_unique($attributes) as $pimAttribute) {
				$this->_appendAttributeValue($itemNode, $pimAttribute);
			}
		}
		return $fragment;
	}
	/**
	 * Append the nodes necessary DOMNodes to represent the pim attribute to the
	 * given EbayEnterprise_Dom_Element.
	 * @param  EbayEnterprise_Dom_Element                     $itemNode     container node
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Attribute $pimAttribute attribute value model
	 * @return self
	 */
	protected function _appendAttributeValue(
		EbayEnterprise_Dom_Element $itemNode,
		EbayEnterprise_Eb2cProduct_Model_Pim_Attribute $pimAttribute
	)
	{
		if ($pimAttribute->value instanceof DOMAttr) {
			$itemNode->setAttribute($pimAttribute->value->name, $pimAttribute->value->value);
		} elseif ($pimAttribute->value instanceof DOMNode) {
			$attributeNode = $itemNode->setNode($pimAttribute->destinationXpath);
			$attributeNode->appendChild($this->_doc->importNode($pimAttribute->value, true));
			if ($pimAttribute->language) {
				$attributeNode->addAttributes(array('xml:lang' => $pimAttribute->language));
			}
			$this->_clumpWithSimilar($attributeNode);
		}
		return $this;
	}
	/**
	 * validate the dom against the configured xsd.
	 * @return self
	 */
	protected function _validateDocument()
	{
		Mage::helper('ebayenterprise_magelog')->logInfo("[%s] Validating document:\n%s", array(__METHOD__, $this->_doc->C14N()));
		$config = $this->_getFeedConfig();
		Mage::getModel('eb2ccore/api')
			->schemaValidate($this->_doc, $config[self::KEY_SCHEMA_LOCATION]);
		return $this;
	}
	/**
	 * set up the core feed model need to manage directories.
	 * @return EbayEnterprise_Eb2cCore_Model_Feed core feed model
	 */
	protected function _setUpCoreFeed()
	{
		return Mage::getModel('eb2ccore/feed', array(
			'feed_config' => Mage::helper('eb2cproduct')->getConfigModel()->pimExportFeed
		));
	}
	/**
	 * generate the full file path for the outbound feed file.
	 * @return string file path
	 */
	protected function _getFeedFilePath()
	{
		$feedConfig = $this->_getFeedConfig();
		$helper = Mage::helper('eb2cproduct');
		$coreConfig = Mage::helper('eb2ccore')->getConfigModel();
		$filenameOverrides = array('store_id' => $coreConfig->setStore($this->_batch->getDefaultStore())->storeId);
		return $this->_coreFeed->getLocalDirectory() . DS .
			$helper->generateFileName($feedConfig[self::KEY_EVENT_TYPE], $feedConfig[self::KEY_FILE_PATTERN], $filenameOverrides);
	}
	/**
	 * setup the document with the root node and the message header.
	 * @return self
	 */
	protected function _startDocument()
	{
		$config = $this->_getFeedConfig();
		$this->_doc->loadXML(sprintf(
			self::XML_TEMPLATE,
			$config[self::KEY_ROOT_NODE],
			self::XMLNS,
			$config[self::KEY_SCHEMA_LOCATION],
			Mage::helper('eb2cproduct')->generateMessageHeader($config[self::KEY_EVENT_TYPE]),
			Mage::helper('eb2ccore')->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
		));
		return $this;
	}
	/**
	 * move the specified element to be the immediately preceding sibling of the
	 * first existing element with the same tag name.
	 * NOTE: order of similar elements within a clump is not guaranteed.
	 * WARNING: any operations on $attributeNode must be done using the instance
	 *          returned by this function.
	 * @param  DOMElement $attributeNode
	 * @return DOMElement
	 */
	protected function _clumpWithSimilar(DOMElement $attributeNode)
	{
		// if the value  element and a same-named element already exists,
		// insert the value element before the exigent one instead of appending.
		$xpath = new DOMXPath($attributeNode->ownerDocument);
		$nodeList = $xpath->query($attributeNode->tagName, $attributeNode->parentNode);
		if ($nodeList->length > 1) {
			$attributeNode = $attributeNode->parentNode->insertBefore($attributeNode, $nodeList->item(0));
		}
		return $attributeNode;
	}
}
