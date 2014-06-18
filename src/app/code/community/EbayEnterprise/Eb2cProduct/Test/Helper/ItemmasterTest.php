<?php

class EbayEnterprise_Eb2cProduct_Test_Helper_ItemmasterTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	// @var Mage_Catalog_Model_Product emtpy product object
	public $product;
	// @var Mage_Eav_Model_Attribute_Option instanse set up with color data
	public $colorOption;
	/**
	 * Mock collection scripted to return the color option when given the id in color data
	 * @var Mock_Mage_Eav_Model_Resource_Attribute_Option_Collection
	 */
	public $colorOptionCollection;
	// @var array key/value pairs of color option data
	public $colorData = array('value' => 'Red', 'default_value' => '12', 'id' => '1');
	// @var EbayEnterprise_Dom_Document instance to pass to map methods
	public $doc;
	/**
	 * Mock eb2cproduct/itemmaster helper. Scripted to return the color options
	 * collection when calling _getColorAttributeOptionsCollection
	 * @var Mock_EbayEnterprise_Eb2cProduct_Helper_Map_Itemmaster test object
	 */
	public $itemmasterHelper;
	/**
	 * Set up a product, DOM document, color option and color option collection
	 * for tests
	 */
	public function setUp()
	{
		parent::setUp();
		$this->product = Mage::getModel('catalog/product');
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
		$this->itemmasterHelper = $this->getHelperMock('eb2cproduct/itemmaster', array('_getColorAttributeOptionsCollection'));
		$this->itemmasterHelper->expects($this->any())
			->method('_getColorAttributeOptionsCollection')
			->will($this->returnValue($this->colorOptionCollection));
	}

	/**
	 * Test getting the color code - color option default value - for a product
	 * with a color. Should return a DOMText object with the value as the text.
	 * @test
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
	 * @test
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
	 * @test
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
	 * @test
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
}
