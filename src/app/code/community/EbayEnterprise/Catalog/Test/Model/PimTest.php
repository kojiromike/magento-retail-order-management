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

class EbayEnterprise_Catalog_Test_Model_PimTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	// configuration keys
	const KEY_ROOT_NODE = EbayEnterprise_Catalog_Model_Pim::KEY_ROOT_NODE;
	const KEY_SCHEMA_LOCATION = EbayEnterprise_Catalog_Model_Pim::KEY_SCHEMA_LOCATION;
	const KEY_EVENT_TYPE = EbayEnterprise_Catalog_Model_Pim::KEY_EVENT_TYPE;
	const KEY_FILE_PATTERN = EbayEnterprise_Catalog_Model_Pim::KEY_FILE_PATTERN;
	const KEY_IS_VALIDATE = EbayEnterprise_Catalog_Model_Pim::KEY_IS_VALIDATE;
	const KEY_ITEM_NODE = EbayEnterprise_Catalog_Model_Pim::KEY_ITEM_NODE;
	const KEY_MAPPINGS = EbayEnterprise_Catalog_Model_Pim::KEY_MAPPINGS;

	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_realCoreHelper;
	/** @var EbayEnterprise_Dom_Document */
	protected $_doc;
	/** @var EbayEnterprise_Catalog_Pim_Batch */
	protected $_batch;
	/** @var EbayEnterprise_Catalog_Pim_Batch */
	protected $_emptyBatch;

	// mocked objects and stubs
	/** @var EbayEnterprise_Catalog_Model_Feed */
	protected $_coreFeed;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var EbayEnterprise_Catalog_Helper_Data */
	protected $_prodHelper;
	/** @var EbayEnterprise_Dom_Document */
	protected $_docMock;
	/** @var array feed configuration */
	protected $_feedTypeConfig;
	/** @var EbayEnterprise_Catalog_Model_Pim_Product_Collection */
	protected $_pimProductCollection;
	/** @var Varien_Data_Collection collection of objects with an entity id field */
	protected $_productIdCollection;
	/** @var Mage_Core_Model_Store */
	protected $_store;
	/** @var Mage_Core_Model_Store */
	protected $_store2;
	/** @var Mage_Core_Model_Store */
	protected $_defaultStore;
	/** @var array store list */
	protected $_storesArray = array();
	/** @var array list of product attribute codes */
	protected $_attributes = array('_gsi_client_id', 'sku', 'name');
	protected $_translatableAttributes = array('description', 'keywords');
	/** @var string expected file path */
	protected $_outboundPath = 'Path/To/Outbound/Dir';
	/** @var string expected file name */
	protected $_tmpFileName = 'MageMaster_File_Name.xml';

	public function setUp()
	{
		$this->_realCoreHelper = Mage::helper('eb2ccore');
		$this->_doc = $this->_realCoreHelper->getNewDomDocument();
		$this->_coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel', 'triggerError', 'parseBool', 'getNewDomDocument'));
		$this->_docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('save', 'loadXML', 'importNode'))
			->getMock();
		$this->_pimProductCollection = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('count', 'getItems'))
			->getMock();
		$this->_feedTypeConfig = $this->_stubFeedConfig();
		$this->_productIdCollection = new Varien_Data_Collection();
		$this->_productIdCollection->addItem(new Varien_Object(array('id' => 1, 'entity_id' => 1)));
		$this->_emptyBatch = Mage::getModel('ebayenterprise_catalog/pim_batch');
		$batchClass = 'EbayEnterprise_Catalog_Model_Pim_Batch';
		$this->_defaultStore = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()
			->setMethods(array('getId'))->getMock();
		$this->_defaultStore->expects($this->any())->method('getId')->will($this->returnValue(0));
		$this->_store = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()
			->setMethods(array('getId'))->getMock();
		$this->_store->expects($this->any())->method('getId')->will($this->returnValue(1));
		$this->_storesArray = array(0 => $this->_defaultStore, 1 => $this->_store);
		$this->_batch = Mage::getModel('ebayenterprise_catalog/pim_batch', array(
			$batchClass::COLLECTION_KEY => $this->_productIdCollection,
			$batchClass::STORES_KEY => array(1 => $this->_store),
			$batchClass::FT_CONFIG_KEY => $this->_feedTypeConfig,
			$batchClass::DEFAULT_STORE_KEY => $this->_defaultStore,
		));
		$this->_coreFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_core')
			->disableOriginalConstructor()
			->setMethods(array('getLocalDirectory'))
			->getMock();
		$this->_prodHelper = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
			->disableOriginalConstructor()
			->setMethods(array('generateFileName'))
			->getMock();

		// suppressing the real session from starting
		$session = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);
	}
	/**
	 * mock the structure of the feed config
	 * @return array
	 */
	protected function _stubFeedConfig()
	{
		$mappings = array();
		foreach ($this->_attributes as $attr) {
			$mappings[$attr] = array('translate' => '0');
		}
		foreach ($this->_translatableAttributes as $attr) {
			$mappings[$attr] = array('translate' => '1');
		}
		return array(
			self::KEY_ROOT_NODE => 'ItemMaster',
			self::KEY_SCHEMA_LOCATION => 'ItemMasterV11.xml',
			self::KEY_EVENT_TYPE => 'ItemMaster',
			self::KEY_FILE_PATTERN => '{event_type}_{store_id}.xml',
			self::KEY_ITEM_NODE => 'Item',
			self::KEY_IS_VALIDATE => 'true',
			self::KEY_MAPPINGS => $mappings,
		);
	}
	/**
	 * Should create an internal DOMDocument to be used to build the feed file
	 * unless one is provided in a 'dom' key in the parameters arg passed to the
	 * constructor.
	 */
	public function testConstructor()
	{
		$this->replaceByMock('helper', 'eb2ccore', $this->_coreHelper);
		$this->_coreHelper->expects($this->any())
			->method('getNewDomDocument')->will($this->returnValue($this->_doc));
		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->disableOriginalConstructor()
			->setMethods(array('_setUpCoreFeed'))
			->getMock();
		$pim->expects($this->once())
			->method('_setUpCoreFeed')
			->will($this->returnValue($this->_coreFeed));
		$pim->__construct();
		$this->assertSame($this->_doc, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_doc'));
		$this->assertSame($this->_coreFeed, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_coreFeed'));
	}
	/**
	 * Test constructing the PIM Model passing it dependencies. Should expect the
	 * core feed model to be completely intialized - never call _setUpCoreFeed
	 */
	public function testConstructorWithArgs()
	{
		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pim->__construct(array('doc' => $this->_doc, 'core_feed' => $this->_coreFeed, 'batch' => $this->_batch));
		$this->assertSame($this->_doc, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_doc'));
		$this->assertSame($this->_coreFeed, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_coreFeed'));
		$this->assertSame($this->_batch, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_batch'));
	}
	/**
	 * Create feed data set from product collection.
	 * Create XML from feed data.
	 * Write out file.
	 * Return path to file created.
	 */
	public function testBuildFeed()
	{
		$pathToFile = $this->_outboundPath . DS . $this->_tmpFileName;

		$this->_prodHelper->expects($this->once())
			->method('generateFileName')
			->will($this->returnValue($this->_tmpFileName));
		$this->_pimProductCollection->expects($this->once())
			->method('count')
			->will($this->returnValue(1));
		$this->_coreFeed->expects($this->any())
			->method('getLocalDirectory')
			->will($this->returnValue($this->_outboundPath));

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_createFeedDataSet', '_createDomFromFeedData'))
			->getMock();
		$pim->expects($this->any())->method('_createFeedDataSet')
			->will($this->returnValue($this->_pimProductCollection));
		$pim->expects($this->any())->method('_createDomFromFeedData')
			->will($this->returnValueMap(array(array($this->_pimProductCollection, $this->_docMock))));

		$this->_docMock->expects($this->once())
			->method('save')
			->with($this->identicalTo($pathToFile))
			->will($this->returnValue(12));
		// inject mocks
		$this->replaceByMock('helper', 'ebayenterprise_catalog', $this->_prodHelper);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_coreFeed', $this->_coreFeed);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_batch', $this->_batch);
		$this->assertSame($pathToFile, $pim->buildFeed($this->_batch));
	}
	/**
	 * @see self::testBuildFeed except this test is to prove that when the method EbayEnterprise_Catalog_Model_Pim::buildFeed
	 *      is invoked by this test it will call a mocked EbayEnterprise_Catalog_Model_Pim_Product_Collection class object
	 *      method EbayEnterprise_Catalog_Model_Pim_Product_Collection::count that return zero which will prevent
	 *      the EbayEnterprise_Catalog_Model_Pim::_createDomFromFeedData method to never be invoked
	 */
	public function testBuildFeedNoPimProductData()
	{
		$pathToFile = '';

		$feedData = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('count'))
			->getMock();
		$feedData->expects($this->once())
			->method('count')
			->will($this->returnValue(0));

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_createFeedDataSet', '_createDomFromFeedData', '_getFeedFilePath'))
			->getMock();
		$pim->expects($this->once())
			->method('_createFeedDataSet')
			->will($this->returnValue($feedData));
		$pim->expects($this->never())
			->method('_createDomFromFeedData');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_batch', $this->_batch);
		$this->assertSame($pathToFile, $pim->buildFeed());
	}
	/**
	 * Create a new PIM Product Collection to contain all of the PIM Product
	 * instances that will be used to build the feed. For each store view, create
	 * a new product collection within that store view of all products in the
	 * provided collection. Then, for each product in the collection, get the
	 * existing PIM Product from the collection or create a new instance and add
	 * it to the collection. Then, update the PIM Product model with the product
	 * in the specific store scope. Finally, return the collection.
	 */
	public function testCreateFeedDataSet()
	{
		$pimProductCollection = $this->getModelMock('ebayenterprise_catalog/pim_product_collection');
		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_createProductCollectionForStore', '_processProductCollection'))
			->getMock();

		$productCollection = $this->getResourceModelMock('catalog/product_collection', array('load'));

		$this->replaceByMock('model', 'ebayenterprise_catalog/pim_product_collection', $pimProductCollection);

		$pim->expects($this->exactly(2))
			->method('_createProductCollectionForStore')
			->with($this->logicalAnd($this->isType('array'), $this->logicalNot($this->isEmpty())), $this->isInstanceOf('Mage_Core_Model_Store'))
			->will($this->returnValue($productCollection));
		$pim->expects($this->exactly(2))
			->method('_processProductCollection')
			->with(
				$this->identicalTo($productCollection),
				$this->identicalTo($pimProductCollection)
			)
			->will($this->returnValue($pimProductCollection));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_batch', $this->_batch);
		$this->assertSame(
			$pimProductCollection,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_createFeedDataSet', array())
		);
	}
	/**
	 * Process all of the products within a given store.
	 * @dataProvider provideTrueFalse
	 */
	public function testProcessProductCollection($isInCollection)
	{
		$storeId = 1;
		$attributeList = array('_gsi_client_id', 'sku');

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_getFeedAttributes', '_getFeedConfig'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedAttributes')->will($this->returnValue($attributeList));
		$pim->expects($this->once())
			->method('_getFeedConfig')->will($this->returnValue($this->_feedTypeConfig));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $this->_doc);

		$pimProductCollection = $this->getModelMock(
			'ebayenterprise_catalog/pim_product_collection',
			array('getItemForProduct', 'addItem')
		);
		$pimProduct = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
			->disableOriginalConstructor()
			->setMethods(array('loadPimAttributesByProduct'))
			->getMock();
		$this->replaceByMock('model', 'ebayenterprise_catalog/pim_product', $pimProduct);

		$product = $this->getModelMock('catalog/product');
		$productCollection = $this->getResourceModelMock('catalog/product_collection', array('getItems', 'getStoreId'));
		$productCollection->expects($this->once())
			->method('getItems')
			->will($this->returnValue(array($product)));
		$productCollection->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));
		$pimProduct->expects($this->once())
			->method('loadPimAttributesByProduct')
			->with(
				$this->identicalTo($product),
				$this->identicalTo($this->_doc),
				$this->identicalTo($this->_feedTypeConfig),
				$this->identicalTo($attributeList)
			)
			->will($this->returnSelf());

		$config = $this->buildCoreConfigRegistry(array(
			'client_id' => 'theclientid', 'catalog_id' => 'thecatalogid'
		));

		$prodHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$prodHelper->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($config));
		$this->replaceByMock('helper', 'eb2ccore', $prodHelper);

		if ($isInCollection) {
			$pimProductCollection->expects($this->once())
				->method('getItemForProduct')
				->with($this->identicalTo($product))
				->will($this->returnValue($pimProduct));
			$pimProductCollection->expects($this->never())
				->method('addItem');
		} else {
			$pimProductCollection->expects($this->once())
				->method('getItemForProduct')
				->with($this->identicalTo($product))
				->will($this->returnValue(null));
			$pimProductCollection->expects($this->once())
				->method('addItem')
				->with($this->identicalTo($pimProduct))
				->will($this->returnSelf());
		}

		$this->assertSame(
			$pimProductCollection,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_processProductCollection',
				array($productCollection, $pimProductCollection)
			)
		);
	}
	/**
	 * Initialize the DOM Document with a root node and message header.
	 */
	public function testStartDocument()
	{
		$headerTemplate = '<MessageHeader></MessageHeader>';
		$mediaUrl = '';

		$feedXml = sprintf(
			EbayEnterprise_Catalog_Model_Pim::XML_TEMPLATE,
			$this->_feedTypeConfig[self::KEY_ROOT_NODE],
			EbayEnterprise_Catalog_Model_Pim::XMLNS,
			$this->_feedTypeConfig[self::KEY_SCHEMA_LOCATION],
			$headerTemplate,
			$mediaUrl
		);
		$this->_docMock->expects($this->once())
			->method('loadXML')
			->with($this->identicalTo($feedXml))
			->will($this->returnSelf());

		$helper = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
			->disableOriginalConstructor()
			->setMethods(array('generateMessageHeader'))
			->getMock();
		$helper->expects($this->once())
			->method('generateMessageHeader')
			->with($this->identicalTo($this->_feedTypeConfig[self::KEY_EVENT_TYPE]))
			->will($this->returnValue($headerTemplate));
		$this->replaceByMock('helper', 'ebayenterprise_catalog', $helper);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getBaseUrl'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getBaseUrl')
			->with($this->identicalTo(Mage_Core_Model_Store::URL_TYPE_MEDIA))
			->will($this->returnValue($mediaUrl));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_getFeedConfig'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedConfig')
			->will($this->returnValue($this->_feedTypeConfig));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $this->_docMock);

		$this->assertSame($pim, EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_startDocument', array()));
	}
	/**
	 * Create the DOMDocument representing the feed. Should initialize (add root
	 * node and message header) the DOMDocument, build out and append fragments
	 * for each item to be included, and validate the resulting DOM.
	 */
	public function testCreateDomFromFeedData()
	{
		$itemNode = 'Item';
		$isValidate = 'true';
		$this->_doc->loadXML('<root></root>');
		$itemFragment = $this->_doc->createDocumentFragment();
		$itemFragment->appendChild($this->_doc->createElement('Item'));

		$pimProduct = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
			->disableOriginalConstructor()
			->getMock();
		$this->replaceByMock('helper', 'eb2ccore', $this->_coreHelper);
		$this->_coreHelper->expects($this->once())
			->method('parseBool')
			->with($this->identicalTo($isValidate))
			->will($this->returnValue(true));
		$this->_pimProductCollection->expects($this->once())
			->method('getItems')
			->will($this->returnValue(array($pimProduct)));

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->disableOriginalConstructor()
			->setMethods(array('_buildItemNode', '_startDocument', '_validateDocument', '_getFeedConfig'))
			->getMock();
		$pim->expects($this->once())
			->method('_startDocument')
			->will($this->returnSelf());
		$pim->expects($this->once())
			->method('_buildItemNode')
			->with($this->identicalTo($pimProduct), $this->identicalTo($itemNode))
			->will($this->returnValue($itemFragment));
		$pim->expects($this->once())
			->method('_validateDocument')
			->will($this->returnSelf());
		$pim->expects($this->once())
			->method('_getFeedConfig')
			->will($this->returnValue($this->_feedTypeConfig));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $this->_doc);

		$this->assertSame($this->_doc, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pim, '_createDomFromFeedData', array($this->_pimProductCollection, $this->_feedTypeConfig)
		));
		$this->assertSame('<root><Item></Item></root>', $this->_doc->C14N());
	}
	/**
	 * Create DOMDocumentFragment with the <Item> node.
	 * For each pim attribute model given, append DOM to represent the value.
	 */
	public function testBuildItemNode()
	{
		$pimAttribute = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$pimAttributes = array($pimAttribute,);

		$pimProduct = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
			->disableOriginalConstructor()
			->setMethods(array('getPimAttributes'))
			->getMock();
		$pimProduct->expects($this->once())
			->method('getPimAttributes')
			->will($this->returnValue($pimAttributes));

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_appendAttributeValue'))
			->getMock();
		$pim->expects($this->once())
			->method('_appendAttributeValue')
			->with($this->isInstanceOf('EbayEnterprise_Dom_Element'), $this->identicalTo($pimAttribute))
			->will($this->returnSelf());

		$childNode = 'Item';
		$this->_doc->loadXML('<root/>');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $this->_doc);
		$itemFragment = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pim, '_buildItemNode', array($pimProduct, $childNode)
		);
		$this->assertTrue($itemFragment->hasChildNodes(), 'Item fragment is empty');
		$this->_doc->documentElement->appendChild($itemFragment);
		$this->assertSame(1, count($this->_doc->childNodes), 'Item node was not added');
	}
	/**
	 * Append the nodes necessary DOMNodes to represent the pim attribute to the
	 * given EbayEnterprise_Dom_Element.
	 */
	public function testAppendAttributeValue()
	{
		$xmlDest = 'BaseAttrubtes/Something';
		$valueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$importedValueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();

		$languageCode = 'en-us';

		$pimAttribute = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$pimAttribute->destinationXpath = $xmlDest;
		$pimAttribute->sku = '45-12345';
		$pimAttribute->language = $languageCode;
		$pimAttribute->value = $valueFragment;
		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('importNode'))
			->getMock();
		$doc->expects($this->once())
			->method('importNode')
			->with($this->identicalTo($valueFragment), $this->isTrue())
			->will($this->returnValue($importedValueFragment));

		$attributeNode = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('appendChild', 'addAttributes'))
			->getMock();
		$itemNode = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setNode'))
			->getMock();

		$itemNode->expects($this->once())
			->method('setNode')
			->with($this->identicalTo($xmlDest))
			->will($this->returnValue($attributeNode));
		$attributeNode->expects($this->once())
			->method('appendChild')
			->with($this->identicalTo($importedValueFragment))
			->will($this->returnValue($importedValueFragment));
		$attributeNode->expects($this->once())
			->method('addAttributes')
			->with($this->identicalTo(array('xml:lang' => $languageCode)));

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_clumpWithSimilar'))
			->getMock();
		$pim->expects($this->once())
			->method('_clumpWithSimilar')
			->will($this->returnValue($attributeNode));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $doc);
		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_appendAttributeValue',
				array($itemNode, $pimAttribute)
			)
		);
	}
	/**
	 * When the PIM attribute being added does not have a language, property will
	 * be null, the xml:lang attribute should not be added.
	 */
	public function testAppendAttributeValueNoTranslations()
	{
		$key = 'item_map';
		$xmlDest = 'BaseAttrubtes/Something';
		$valueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$importedValueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$pimAttribute = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$pimAttribute->destinationXpath = $xmlDest;
		$pimAttribute->sku = '45-12345';
		$pimAttribute->language = null;
		$pimAttribute->value = $valueFragment;
		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('importNode'))
			->getMock();

		$attributeNode = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('appendChild', 'addAttributes'))
			->getMock();
		$itemNode = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setNode'))
			->getMock();

		$doc->expects($this->once())
			->method('importNode')
			->with($this->identicalTo($valueFragment), $this->isTrue())
			->will($this->returnValue($importedValueFragment));
		$itemNode->expects($this->once())
			->method('setNode')
			->with($this->identicalTo($xmlDest))
			->will($this->returnValue($attributeNode));
		$attributeNode->expects($this->once())
			->method('appendChild')
			->with($this->identicalTo($importedValueFragment))
			->will($this->returnValue($importedValueFragment));
		$attributeNode->expects($this->never())
			->method('addAttributes');

		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_clumpWithSimilar'))
			->getMock();
		$pim->expects($this->once())
			->method('_clumpWithSimilar')
			->will($this->returnValue($attributeNode));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $doc);
		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_appendAttributeValue',
				array($itemNode, $pimAttribute, $key)
			)
		);
	}
	/**
	 * Test EbayEnterprise_Catalog_Model_Pim::_appendAttributeValue method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Model_Pim::_appendAttributeValue
	 *                given a mocked EbayEnterprise_Dom_Element object, a mocked EbayEnterprise_Catalog_Model_Pim_Attribute
	 *                object and a key
	 */
	public function testAppendAttributeValueWhenPimAttributeValueIsADomAttr()
	{
		$key = 'item_map';
		$data = array('name' => 'catalog_id', 'value' => '83');
		$domAttribute = new DOMAttr($data['name'], $data['value']);

		$elementMock = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setAttribute'))
			->getMock();
		$elementMock->expects($this->once())
			->method('setAttribute')
			->with($this->identicalTo($data['name']), $this->identicalTo($data['value']))
			->will($this->returnValue($domAttribute));

		$attributeMock = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$attributeMock->value = $domAttribute;

		$pimModelMock = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(null)
			->getMock();

		$this->assertSame($pimModelMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimModelMock, '_appendAttributeValue', array($elementMock, $attributeMock, $key)
		));
	}
	/**
	 * verify the current working document is returned.
	 */
	public function testValidateDocument()
	{
		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('_getFeedConfig'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedConfig')
			->will($this->returnValue($this->_feedTypeConfig));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $this->_doc);

		$api = $this->getModelMock('eb2ccore/api', array('schemaValidate'));
		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$api->expects($this->once())
			->method('schemaValidate')
			->with($this->identicalTo($this->_doc), $this->identicalTo($this->_feedTypeConfig[self::KEY_SCHEMA_LOCATION]))
			->will($this->returnSelf());
		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_validateDocument', array())
		);
	}
	/**
	 * Set up a eb2ccore/feed model to handle setting up the dirs for the outbound
	 * file - all of which is done via constructing the model with a 'dir_config'
	 * in the constructor args.
	 */
	public function testSetUpCoreFeed()
	{
		$configRegistry = $this->buildCoreConfigRegistry(array('pimExportFeed' => array('local_directory' => 'local/path')));
		$helper = $this->getHelperMock('ebayenterprise_catalog/data', array('getConfigModel'));
		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($configRegistry));

		$coreFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_core', array('setUpDirs'))
			->disableOriginalConstructor()
			->getMock();
		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(null)
			->getMock();

		$this->replaceByMock('helper', 'ebayenterprise_catalog', $helper);
		$this->replaceByMock('model', 'ebayenterprise_catalog/feed_core', $coreFeed);

		$this->assertSame(
			$coreFeed,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_setUpCoreFeed')
		);
	}
	/**
	 * setup a collection of products for a specific store.
	 */
	public function testCreateProductCollectionForStore()
	{
		$productIds = array(1, 2, 3, 4, 54);
		$languageCode = 'en-us';
		$store = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getLanguageCode'))
			->getMock();
		$productCollection = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addFieldToFilter', 'addExpressionAttributeToSelect', 'setStore'))
			->getMock();
		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('none'))
			->getMock();

		$store->expects($this->once())
			->method('getLanguageCode')
			->will($this->returnValue($languageCode));
		$productCollection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo('*'))
			->will($this->returnSelf());
		$productCollection->expects($this->once())
			->method('addExpressionAttributeToSelect')
			->with(
				$this->identicalTo('pim_language_code'),
				$this->identicalTo("('" . $languageCode . "')"),
				$this->identicalTo(array())
			)
			->will($this->returnSelf());
		$productCollection->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('entity_id'), $this->identicalTo(array('in' => $productIds)))
			->will($this->returnSelf());
		$productCollection->expects($this->once())
			->method('setStore')
			->with($this->identicalTo($store))
			->will($this->returnSelf());

		$this->replaceByMock('resource_model', 'catalog/product_collection', $productCollection);

		$this->assertSame(
			$productCollection,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim, '_createProductCollectionForStore', array($productIds, $store)
			)
		);
	}

	/**
	 * Test EbayEnterprise_Catalog_Model_Pim::_getFeedAttributes method for the following expectation
	 * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Model_Pim::_getFeedAttributes given
	 *                a known array key to return the attribute map configured for the feed
	 */
	public function testGetFeedAttributes()
	{
		$storeId = 0;
		$attributes = array_merge($this->_attributes, $this->_translatableAttributes);
		$pimModelMock = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array())
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pimModelMock, '_batch', $this->_batch);
		$this->assertSame($attributes, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimModelMock, '_getFeedAttributes', array($storeId)
		));
	}
	/**
	 * @see self::testGetFeedAttributes; however, this test is testing
	 *      when the passed in store id is not equal to the default store view store id
	 *      it expects only translatable attributes to be returned.
	 */
	public function testGetFeedAttributesTranslatableAttributes()
	{
		$storeId = 2;
		$pimModelMock = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array())
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pimModelMock, '_batch', $this->_batch);
		// use array_values because we dont care about the indices of the elements.
		$this->assertSame($this->_translatableAttributes, array_values(EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimModelMock, '_getFeedAttributes', array($storeId)
		)));
	}
	/**
	 * verify a node is moved before a previously existing node
	 * with the same tag name.
	 */
	public function testClumpWithSimilar()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$pim = $this->getModelMockBuilder('ebayenterprise_catalog/pim')
			->setMethods(array('none'))
			->getMock();

		$doc->loadXML('
			<a>
				<c lang="en-us"><![CDATA[thevalue1]]></c>
				<b lang="en-us"><![CDATA[thevalue1]]></b>
				<c lang="de-de"><![CDATA[thevalue1]]></c>
			</a>'
		);

		$x = new DOMXPath($doc);
		$ls = $x->query('c[2]');
		EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_clumpWithSimilar', array($ls->item(0)));

		$ls = $x->query('c[1]');
		$this->assertSame('c', $ls->item(0)->nextSibling->nodeName);
	}
}
