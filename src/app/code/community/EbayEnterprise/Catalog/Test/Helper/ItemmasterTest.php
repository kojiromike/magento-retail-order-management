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

class EbayEnterprise_Catalog_Test_Helper_ItemmasterTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var Mage_Catalog_Model_Product empty product object */
	public $product;
	/** @var Mage_Catalog_Model_Product configurable "style" product */
	public $configProduct;
	/** @var Mage_Catalog_Model_Product simple product used by the configurable */
	public $simpleProduct;
	/**
	 * Scripted resource model used to lookup parent configurable products
	 * by child product ids.
	 * @var Mock_Mage_Catalog_Model_Resource_Product_Type_Configurable
	 */
	public $configTypeResource;
	/** @var Mage_Eav_Model_Attribute_Option instanse set up with color data */
	public $colorOption;
	/**
	 * Mock collection scripted to return the color option when given the id in color data
	 * @var Mock_Mage_Eav_Model_Resource_Attribute_Option_Collection
	 */
	public $colorOptionCollection;
	/** @var array key/value pairs of color option data */
	public $colorData = array('value' => 'Red', 'default_value' => '12', 'id' => '1');
	/** @var EbayEnterprise_Dom_Document instance to pass to map methods */
	public $doc;
	/**
	 * Mock ebayenterprise_catalog/itemmaster helper. Scripted to return the color options
	 * collection when calling _getColorAttributeOptionsCollection
	 * @var Mock_EbayEnterprise_Catalog_Helper_Map_Itemmaster test object
	 */
	public $itemmasterHelper;
	/** @var Mock_EbayEnterprise_Eb2cCore_Model_Config_Registry mock config for eb2ccore */
	public $coreConfig;
	/**
	 * Mock core helper scripted to return a mocked set of config data and a
	 * @var Mock_EbayEnterprise_Eb2cCore_Helper_Data
	 */
	public $coreHelper;
	/** @var string mocked catalog id configuration */
	public $catalogId = '11';
	/** @var string expected product style id */
	public $styleId = 'ABC123';
	/** @var string expected product style description */
	public $styleName = 'Product Style';
	/**
	 * Set up mock and test objects used throughout the tests.
	 */
	public function setUp()
	{
		parent::setUp();

		$this->product = Mage::getModel('catalog/product');

		$configId = 1;
		$configSku = sprintf('%s-%s', $this->catalogId, $this->styleId);
		$simpleId = 2;
		$simpleSku = sprintf('%s-%s', $this->catalogId, 'SIMPLE1');

		$this->configProduct = Mage::getModel(
			'catalog/product',
			array(
				'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
				'sku' => $configSku,
				'name' => sprintf($this->styleName),
				'entity_id' => $configId,
			)
		);

		$this->simpleProduct = Mage::getModel(
			'catalog/product',
			array(
				'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
				'sku' => $simpleSku,
				'name' => 'Simple Product',
				'entity_id' => $simpleId,
			)
		);

		// mock out the resource model used to lookup the config to simple
		// product relationships so lookups can be avoided and made reliable
		$this->configTypeResource = $this->getResourceModelMock(
			'catalog/product_type_configurable',
			array('getParentIdsByChild')
		);
		// script out lookup behavior - when called with the simple product's id,
		// return array containing config product's id and an empty array otherwise
		$this->configTypeResource->expects($this->any())
			->method('getParentIdsByChild')
			->will($this->returnCallback(function ($childId) use ($configId, $simpleId) {
				return $childId === $simpleId ? array($configId) : array();
			}));

		$this->colorOption = Mage::getModel('eav/entity_attribute_option');
		$this->colorOption->setData($this->colorData);

		$this->colorOptionCollection = $this->getResourceModelMockBuilder('eav/entity_attribute_option_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemById'))
			->getMock();
		$this->colorOptionCollection->expects($this->any())
			->method('getItemById')
			->will($this->returnValueMap(array(
				array($this->colorData['id'], $this->colorOption),
			)));

		$this->doc = new EbayEnterprise_Dom_Document();

		$this->coreConfig = $this->buildCoreConfigRegistry(array(
			'catalogId' => $this->catalogId
		));

		$this->coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$this->coreHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->coreConfig));

		$this->itemmasterHelper = $this->getHelperMock('ebayenterprise_catalog/itemmaster', array('_getColorAttributeOptionsCollection'));
		$this->itemmasterHelper->expects($this->any())
			->method('_getColorAttributeOptionsCollection')
			->will($this->returnValue($this->colorOptionCollection));
	}

	/**
	 * Test getting the color code - color option default value - for a product
	 * with a color. Should return a DOMText object with the value as the text.
	 */
	public function testPassColorCode()
	{
		// product's color data should be the id of the color option
		$this->product->addData(array('color' => $this->colorData['id'], 'store_id' => '1'));
		$this->assertSame(
			$this->colorData['default_value'],
			$this->itemmasterHelper->passColorCode(null, '_color_code', $this->product, $this->doc)->wholeText
		);
	}
	/**
	 * Test getting color code for a product without a color - shoulr return null
	 */
	public function testPassColorCodeNoColor()
	{
		// product's color data should be the id of the color option
		$this->product->addData(array('color' => null, 'store_id' => '1'));
		$this->assertSame(
			null,
			$this->itemmasterHelper->passColorCode(null, '_color_code', $this->product, $this->doc)
		);
	}
	/**
	 * Test getting the color description - color option value - for a product
	 * with a color. Should return a DOMText object with the color option value
	 * as the text
	 */
	public function testPassColorDescription()
	{
		// product's color data should be the id of the color option
		$this->product->addData(array('color' => $this->colorData['id'], 'store_id' => '1'));
		$this->assertSame(
			$this->colorData['value'],
			$this->itemmasterHelper->passColorDescription(null, '_color_code', $this->product, $this->doc)->wholeText
		);
	}
	/**
	 * Test getting the color description for a product without a color - should
	 * return null
	 */
	public function testPassColorDescriptionNoColor()
	{
		// product's color data should be the id of the color option
		$this->product->addData(array('color' => null, 'store_id' => '1'));
		$this->assertSame(
			null,
			$this->itemmasterHelper->passColorDescription(null, '_color_code', $this->product, $this->doc)
		);
	}
	/**
	 * Provide various values for the product cost attribute that should not
	 * be considered valid to include in the feed. Value must be numeric to
	 * be valid.
	 * @return array Args array
	 */
	public function provideInvalidCostValue()
	{
		return array(
			array(null), array('foo'), array(''), array(array()), array(true), array(new Varien_Object()),
		);
	}
	/**
	 * When a product does not have a `cost`, the mapping should return null
	 * to prevent the UnitCost node from being added.
	 * @param mixed $costValue Values that should be considered invalid
	 * @dataProvider provideInvalidCostValue
	 */
	public function testUnitCostNoValue($costValue)
	{
		$this->product->setCost($costValue);
		$this->assertSame(
			null,
			$this->itemmasterHelper->passUnitCost($this->product->getCost(), 'cost', $this->product, $this->doc)
		);
	}
	/**
	 * Provide the various Magento product types
	 * @return array Args array with product type id string
	 */
	public function provideProductTypeIds()
	{
		return array(
			array(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
			array(Mage_Catalog_Model_Product_Type::TYPE_BUNDLE),
			array(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE),
			array(Mage_Catalog_Model_Product_Type::TYPE_GROUPED),
			array(Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL),
		);
	}
	/**
	 * Any product that is not used by a configurable product should simply
	 * use its own sku and name as the style information
	 * @param string $productTypeId Magento product type identifier
	 * @dataProvider provideProductTypeIds
	 */
	public function testPassStyleNoParentConfigProduct($productTypeId)
	{
		$this->replaceByMock('helper', 'eb2ccore', $this->coreHelper);
		$this->replaceByMock('resource_singleton', 'catalog/product_type_configurable', $this->configTypeResource);

		$this->product->setData(array(
			'type_id' => $productTypeId,
			'sku' => sprintf('%s-%s', $this->catalogId, $this->styleId),
			'entity_id' => 23,
			'name' => $this->styleName,
		));

		$styleFragment = $this->itemmasterHelper->passStyle(null, '_style', $this->product, $this->doc);
		$idNode = $styleFragment->firstChild;
		$descNode = $idNode->nextSibling;

		$this->assertSame($this->styleId, $idNode->textContent);
		$this->assertSame($this->styleName, $descNode->textContent);
	}
	/**
	 * When building style nodes for a simple product used by a config product,
	 * style values should come from the config product
	 */
	public function testPassStyleSimpleWithParent()
	{
		$this->replaceByMock('helper', 'eb2ccore', $this->coreHelper);
		$this->replaceByMock('resource_singleton', 'catalog/product_type_configurable', $this->configTypeResource);
		// Mock out the loading of the config product - must be called with
		// the config product's id. Simulates loading by swapping the empty
		// product mock with the config product which has the expected data
		// already loaded in it
		$prodLoader = $this->getModelMock('catalog/product', array('load'));
		$prodLoader->expects($this->once())
			->method('load')
			->with($this->identicalTo($this->configProduct->getId()))
			->will($this->returnValue($this->configProduct));
		$this->replaceByMock('model', 'catalog/product', $prodLoader);

		// use the simple product which will be associated with the config product
		$styleFragment = $this->itemmasterHelper->passStyle(null, '_style', $this->simpleProduct, $this->doc);
		$idNode = $styleFragment->firstChild;
		$descNode = $idNode->nextSibling;

		$this->assertSame($this->styleId, $idNode->textContent);
		$this->assertSame($this->styleName, $descNode->textContent);
	}
	/**
	 * If the simple to config lookup returns an item id but that item doesn't
	 * actually exist. Returns null which would result in no style data in the
	 * feed.
	 */
	public function testPassStyleSimpleWithMissingParent()
	{
		$this->replaceByMock('helper', 'eb2ccore', $this->coreHelper);
		$this->replaceByMock('resource_singleton', 'catalog/product_type_configurable', $this->configTypeResource);
		// Mock out product loading to simply return an empty product - will
		// simulate attempting to load a parent product that doesn't exist or
		// otherwise cannot be loaded
		$prodLoader = $this->getModelMock('catalog/product', array('load'));
		$prodLoader->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'catalog/product', $prodLoader);

		$this->assertNull($this->itemmasterHelper->passStyle(null, '_style', $this->simpleProduct, $this->doc));
	}

	/**
	 * When processing a gift card product, a DOM fragment with a gift card name,
	 * gift card tender type and gift card max value should be returned.
	 */
	public function testPassGiftCardWithGiftCardProduct()
	{
		$product = Mage::getModel(
			'catalog/product',
			[
				'type_id' => Enterprise_GiftCard_Model_Catalog_Product_Type_Giftcard::TYPE_GIFTCARD,
				'name' => 'Gift Card Name',
				'gift_card_tender_code' => 'GCTC',
				'open_amount_max' => 499.99,
			]
		);
		$giftCardFragment = $this->itemmasterHelper->passGiftCard(null, '_giftcard', $product, $this->doc);
		$this->assertNotNull($giftCardFragment);
		$element = $giftCardFragment->firstChild;
		$this->assertSame('Gift Card Name', $element->nodeValue);
		$element = $element->nextSibling;
		$this->assertSame('GCTC', $element->nodeValue);
		$element = $element->nextSibling;
		$this->assertSame('499.99', $element->nodeValue);
	}

	/**
	 * When processing a non-gift card product, a DOM fragment with an empty max
	 * gift card amount should be returned.
	 */
	public function testPassGiftCardWithNonGiftCardProduct()
	{
		$giftCardFragment = $this->itemmasterHelper->passGiftCard(null, '_giftcard', $this->simpleProduct, $this->doc);
		$this->assertNotNull($giftCardFragment);
		$element = $giftCardFragment->firstChild;
		$this->assertSame('MaxGCAmount', $element->nodeName);
		$this->assertSame('', $element->nodeValue);
	}
}
