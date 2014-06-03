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
	const PIM_CONFIG_PATH = 'eb2cproduct/feed_pim_mapping';
	const XML_TEMPlATE = '<%1$s xmlns:xsi="%2$s" xsi:schemaLocation="%3$s">%4$s</%1$s>';
	const XMLNS = 'http://www.w3.org/2001/XMLSchema-instance';

	const KEY_EVENT_TYPE = 'event_type';
	const KEY_SCHEMA_LOCATION = 'schema_location';
	const KEY_ROOT_NODE = 'root_node';
	const KEY_FILE_PATTERN = 'file_pattern';
	const KEY_MAPPINGS = 'mappings';
	const KEY_IS_VALIDATE = 'is_validate';
	const KEY_ITEM_NODE = 'item_node';

	const KEY_DOCS = 'docs';
	const KEY_CORE_FEED = 'core_feed';

	const ERROR_INVALID_DOC = '%s called with invalid doc. Must be instance of EbayEnterprise_Dom_Document';
	const ERROR_INVALID_CORE_FEED = '%s called with invalid core feed. Must be instance of EbayEnterprise_Eb2cCore_Model_Feed';

	const WARNING_CANNOT_GENERATE_FEED = '[%s] %s could not be generated because of missing required product data or the sku exceeded %d characters.';

	/**
	 * document object used when building the feed contents
	 * @var array of EbayEnterprise_Dom_Document
	 */
	protected $_docs = array();
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
	/**
	 * @var array map between event type and feed root node
	 */
	protected $_feedsMap = array();
	/**
	 * Set up the doc and core feed
	 */
	public function __construct(array $initParams=array())
	{

		if (isset($initParams[self::KEY_DOCS]) && empty($initParams[self::KEY_DOCS])) {
			Mage::helper('eb2ccore')->triggerError(sprintf(self::ERROR_INVALID_DOC, __METHOD__));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		$this->_docs = isset($initParams[self::KEY_DOCS]) ? $initParams[self::KEY_DOCS] : $this->_getFeedDoms();
		if (isset($initParams[self::KEY_CORE_FEED]) && !$initParams[self::KEY_CORE_FEED] instanceof EbayEnterprise_Eb2cCore_Model_Feed) {
			Mage::helper('eb2ccore')->triggerError(sprintf(self::ERROR_INVALID_CORE_FEED, __METHOD__));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		$this->_coreFeed = isset($initParams[self::KEY_CORE_FEED]) ?
			$initParams[self::KEY_CORE_FEED] :
			$this->_setUpCoreFeed();
	}
	/**
	 * getting an array of DOMDocument object per event type
	 * @return array
	 */
	protected function _getFeedDoms()
	{
		$helper = Mage::helper('eb2ccore');
		$docs = array();
		foreach (array_keys($this->_getFeedsMap()) as $key) {
			$docs[$key] = $helper->getNewDomDocument();
		}
		return $docs;
	}
	/**
	 * @see self::_feedsMap caching the mapping in this class property
	 * @return array
	 */
	protected function _getFeedsMap()
	{
		if (empty($this->_feedsMap)) {
			$cfg = Mage::helper('eb2cproduct')->getConfigModel();
			$this->_feedsMap = $cfg->getConfigData(self::PIM_CONFIG_PATH);
		}

		return $this->_feedsMap;
	}
	/**
	 * generate the outgoing product feed.
	 * @param  array  $productIds list of product id's to include in the feed.
	 * @return array             array of full filepath where the feed files was saved.
	 */
	public function buildFeed(array $productIds)
	{
		$feedFilePath = array();
		foreach (array_keys($this->_getFeedsMap()) as $key) {
			$feedDataSet = $this->_createFeedDataSet($productIds, $key);
			$fileName = $this->_getFeedFilePath($key);
			if ($feedDataSet->count()) {
				$feedDoc = $this->_createDomFromFeedData($feedDataSet, $key);
				$feedFilePath[$key] = $fileName;
				$feedDoc->save($feedFilePath[$key]);
			} else {
				$skuLength = EbayEnterprise_Eb2cProduct_Helper_Pim::MAX_SKU_LENGTH;
				Mage::helper('ebayenterprise_magelog')->logWarn(
					static::WARNING_CANNOT_GENERATE_FEED,
					array( __METHOD__, basename($fileName), $skuLength)
				);
			}
		}
		return $feedFilePath;
	}
	/**
	 * Build all PIM Product instances used to build the feed.
	 * @param  array  $productIds entity id's of products
	 * @param  string $key
	 * @return EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection collection of PIM Product instances
	 */
	protected function _createFeedDataSet(array $productIds, $key)
	{
		$pimProducts = Mage::getModel('eb2cproduct/pim_product_collection');
		foreach (Mage::helper('eb2ccore/languages')->getStores() as $store) {
			$pimProducts = $this->_processProductCollection(
				$this->_createProductCollectionForStore($productIds, $store),
				$pimProducts,
				$key
			);
		}
		return $pimProducts;
	}
	/**
	 * build out the dom document with the supplied feed data.
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $feedData collection of PIM Product instances
	 * @param  string $key
	 * @return EbayEnterprise_Dom_Document                    dom document representing the feed
	 */
	protected function _createDomFromFeedData(EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts, $key)
	{
		$this->_startDocument();
		$doc = $this->_docs[$key];
		$map = $this->_getFeedsMap();

		$pimRoot = $doc->documentElement;
		$itemNode = $map[$key][self::KEY_ITEM_NODE];
		foreach ($pimProducts->getItems() as $pimProduct) {
			$itemFragment = $this->_buildItemNode($pimProduct, $itemNode, $key);
			if ($itemFragment->hasChildNodes()) {
				$pimRoot->appendChild($itemFragment);
			}
		}
		if (Mage::helper('eb2cproduct')->parseBool($map[$key][self::KEY_IS_VALIDATE])) {
			$this->_validateDocument($key);
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
	 * @param  Mage_Catalog_Model_Resource_Product_Collection      $products    products for a specific store
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 * @param  string $key
	 * @return EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 */
	protected function _processProductCollection(
		Mage_Catalog_Model_Resource_Product_Collection $products,
		EbayEnterprise_Eb2cProduct_Model_Pim_Product_Collection $pimProducts, $key
	)
	{
		$config = Mage::helper('eb2ccore')->getConfigModel($products->getStoreId());
		$clientId = $config->clientId;
		$catalogId = $config->catalogId;
		foreach ($products->getItems() as $product) {
			$pimProduct = $pimProducts->getItemForProduct($product);
			if (!$pimProduct) {
				$pimProduct = Mage::getModel('eb2cproduct/pim_product', array(
					'client_id' => $clientId, 'catalog_id' => $catalogId, 'sku' => $product->getSku()
				));
				$pimProducts->addItem($pimProduct);
			}
			try {
				$pimProduct->loadPimAttributesByProduct($product, $this->_docs[$key],
					$key, $this->_getFeedAttributes($key));
			} catch(EbayEnterprise_Eb2cProduct_Model_Pim_Product_Validation_Exception $e) {
				Mage::helper('ebayenterprise_magelog')->logWarn(
					'[%s] Product excluded from export (%s)',
					 array( __METHOD__, $e->getMessage())
				);
				$pimProducts->deleteItem($pimProduct);
			}
		}
		return $pimProducts;
	}
	/**
	 * given a key in the feed configuration maps get all the mapping attributes
	 * @param string $key
	 * @return array
	 */
	protected function _getFeedAttributes($key)
	{
		$map = $this->_getFeedsMap();
		return array_keys($map[$key][self::KEY_MAPPINGS]);
	}
	/**
	 * Create DOMDocumentFragment with the <Item> node.
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Product $pimProduct
	 * @param string $childNode
	 * @param string $key
	 * @return DOMDocumentFragment
	 */
	protected function _buildItemNode(EbayEnterprise_Eb2cProduct_Model_Pim_Product $pimProduct, $childNode, $key)
	{
		$fragment = $this->_docs[$key]->createDocumentFragment();
		$itemNode = $fragment->appendChild($this->_docs[$key]->createElement($childNode));
		$attributes = $pimProduct->getPimAttributes();
		foreach (array_unique($attributes) as $pimAttribute) {
			$this->_appendAttributeValue($itemNode, $pimAttribute, $key);
		}
		return $fragment;
	}
	/**
	 * Append the nodes necessary DOMNodes to represent the pim attribute to the
	 * given EbayEnterprise_Dom_Element.
	 * @param  EbayEnterprise_Dom_Element                     $itemNode     container node
	 * @param  EbayEnterprise_Eb2cProduct_Model_Pim_Attribute $pimAttribute attribute value model
	 * @param string $key
	 * @return self
	 */
	protected function _appendAttributeValue(
		EbayEnterprise_Dom_Element $itemNode,
		EbayEnterprise_Eb2cProduct_Model_Pim_Attribute $pimAttribute, $key
	) {
		if ($pimAttribute->value instanceof DOMAttr) {
			$itemNode->setAttribute($pimAttribute->value->name, $pimAttribute->value->value);
		} elseif ($pimAttribute->value instanceof DOMNode) {
			$attributeNode = $itemNode->setNode($pimAttribute->destinationXpath);
			$attributeNode->appendChild($this->_docs[$key]->importNode($pimAttribute->value, true));
			if ($pimAttribute->language) {
				$attributeNode->addAttributes(array('xml:lang' => $pimAttribute->language));
			}
			$this->_clumpWithSimilar($attributeNode);
		}
		return $this;
	}
	/**
	 * validate the dom against the configured xsd.
	 * @param string $key
	 * @return self
	 */
	protected function _validateDocument($key)
	{
		Mage::helper('ebayenterprise_magelog')->logInfo("[%s] Validating document:\n%s", array(__METHOD__, $this->_docs[$key]->C14N()));
		$config = $this->_getFeedsMap();
		Mage::getModel('eb2ccore/api')
			->schemaValidate($this->_docs[$key], $config[$key][self::KEY_SCHEMA_LOCATION]);
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
	 * @param string $key
	 * @return string file path
	 */
	protected function _getFeedFilePath($key)
	{
		$helper = Mage::helper('eb2cproduct');
		$map = $this->_getFeedsMap();
		return $this->_coreFeed->getLocalDirectory() . DS .
			$helper->generateFileName($map[$key][self::KEY_EVENT_TYPE], $map[$key][self::KEY_FILE_PATTERN]);
	}
	/**
	 * setup the document with the root node and the message header.
	 * @return self
	 */
	protected function _startDocument()
	{
		foreach ($this->_getFeedsMap() as $key => $map) {
			$this->_docs[$key]->loadXml(sprintf(
				self::XML_TEMPlATE,
				$map[self::KEY_ROOT_NODE],
				self::XMLNS,
				$map[self::KEY_SCHEMA_LOCATION],
				Mage::helper('eb2cproduct')->generateMessageHeader($map[self::KEY_EVENT_TYPE]),
				Mage::helper('eb2ccore')->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
			));
		}
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
