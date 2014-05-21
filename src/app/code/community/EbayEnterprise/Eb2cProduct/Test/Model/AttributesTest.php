<?php
class EbayEnterprise_Eb2cProduct_Test_Model_AttributesTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	private $_configArray;

	public function setUp()
	{
		parent::setUp();
		$this->_configArray = array(
			'base_data' => array('initial_data' => 'some stuff'),
			'default' => array(
				'tax_code' => array(
					'scope' => 'Store',
					'label' => 'Tax Code2',
					'group' => 'Prices',
					'input_type' => 'boolean',
					'unique' => 'Y',
					'product_types' => 'simple,configurable,virtual,bundle,downloadable',
					'default' => 'N',
				)
			)
		);
	}

	/**
	 * ensure the tax code is readable
	 * @loadFixture
	 * @large
	 * NOTE: ticket EB2C-14
	 * NOTE: marked large because this is an integration test that invokes several
	 * queries to/from the database.
	 */
	public function testReadingAttributeValue()
	{
		$taxCode = 'thecode';
		$product = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->addFieldToFilter('entity_id', array('eq' => 1))
			->getFirstItem();
		$this->assertNotNull($product->getId());
		$this->assertArrayHasKey('tax_code', $product->getData());
		$this->assertSame($taxCode, $product->getTaxCode());
		$data = array('tax_code' => 'thecode2');
		Mage::getSingleton('catalog/product_action')
			->updateAttributes(array($product->getId()), $data, 0);
		unset($product);
		$product = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->addFieldToFilter('entity_id', array('eq' => 1))
			->getFirstItem();
		$this->assertSame('thecode2', $product->getTaxCode());
	}

	/**
	 * verify the _getMappedFieldValue function throws an exception when
	 * the function mapped to the fieldname does not exist.
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldValueException($funcName, $message)
	{
		$exceptionName = 'EbayEnterprise_Eb2cProduct_Model_Attributes_Exception';
		$this->setExpectedException($exceptionName, $message);
		$attributeField = array('scope' => 'Website');
		$model = Mage::getModel('eb2cproduct/attributes');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$model,
			'_valueFunctionMap',
			array('is_global' => $funcName)
		);
		EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$model,
			'_getMappedFieldValue',
			array('is_global', $attributeField)
		);
	}

	/**
	 * verify the _formatScope function throws an exception when
	 * an invalid valid is passed in.
	 * @dataProvider dataProvider
	 */
	public function testFormatScopeException($value, $message)
	{
		$exceptionName = 'EbayEnterprise_Eb2cProduct_Model_Attributes_Exception';
		$this->setExpectedException($exceptionName, $message);
		$model = Mage::getModel('eb2cproduct/attributes');
		$fn = $this->_reflectMethod($model, '_formatScope');
		$fn->invoke($model, $value);
	}

	/**
	 * the return value is an array.
	 * the function loops through all attributes in the default config
	 */
	public function testGetAttributesData()
	{
		$model = $this->getModelMock('eb2cproduct/attributes', array(
			'_loadDefaultAttributesConfig',
			'_makeAttributeRecord'
		));
		$model->expects($this->once())
			->method('_loadDefaultAttributesConfig')
			->will($this->returnValue($this->_configArray));
		$model->expects($this->once())
			->method('_makeAttributeRecord')
			->with($this->identicalTo($this->_configArray['default']['tax_code']))
			->will($this->returnValue(array()));
		$result = $model->getAttributesData();
		$this->assertEquals(array('tax_code' => array()), $result);
	}

	/**
	 * the function shouldn't die if an exception occurs.
	 */
	public function testGetAttributesDataException()
	{
		$model = $this->getModelMock('eb2cproduct/attributes', array(
			'_loadDefaultAttributesConfig',
			'_makeAttributeRecord'
		));
		$model->expects($this->once())
			->method('_loadDefaultAttributesConfig')
			->will($this->returnValue($this->_configArray));
		$model->expects($this->once())
			->method('_makeAttributeRecord')
			->with($this->identicalTo($this->_configArray['default']['tax_code']))
			->will($this->throwException(new EbayEnterprise_Eb2cProduct_Model_Attributes_Exception()));
		$result = $model->getAttributesData();
		$this->assertEquals(array(), $result);
	}

	/**
	 * verify we get an array with the entity type id for products.
	 * @loadExpectation
	 */
	public function testGetTargetEntityTypeIds()
	{
		$e          = $this->expected('product_only');
		$entityType = 'catalog/product';
		$entity     = $this->getModelMock($entityType);
		$this->assertInstanceOf('Mage_Catalog_Model_Product', $entity);
		$entity->expects($this->once())
			->method('getResource')
			->will($this->returnSelf());
		$entity->expects($this->once())
			->method('getTypeId')
			->will($this->returnValue($e->getEntityTypeId()));
		$this->replaceByMock('model', $entityType, $entity);
		$model = Mage::getModel('eb2cproduct/attributes');
		$ids   = $model->getTargetEntityTypeIds();
		$this->assertEquals($e->getIds(), $ids);
	}

	/**
	 * verify the function returns true if the attribute set's entity id
	 * is a valid entity id.
	 * @param  int $eid
	 * @param  bool $expect
	 * @dataProvider dataProvider
	 */
	public function testIsValidEntityType($eid, $expect)
	{
		$model = $this->getModelMock('eb2cproduct/attributes', array('_getTargetEntityTypeIds'));
		$model->expects($this->any())
			->method('_getTargetEntityTypeIds')
			->will($this->returnValue(array(10)));
		$val = EcomDev_Utils_Reflection::invokeRestrictedMethod($model, '_isValidEntityType', array($eid));
		$this->assertSame($expect, $val);
	}

	/**
	 * verify a the model field name is returned when it is defined in the map
	 * and the input field name is returned if not in the map.
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldName($fieldName, $expected)
	{
		$map = array('field_in_map' => 'model_field_name');
		$model = Mage::getModel('eb2cproduct/attributes');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($model, '_fieldNameMap', $map);
		$modelFieldName = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$model,
			'_getMappedFieldName',
			array($fieldName)
		);
		$this->assertSame($expected, $modelFieldName);
	}

	/**
	 * verify a the function returns a value in the correct format for the field as
	 * per the mapping
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldValue($fieldName, $data, $expected)
	{
		$model = Mage::getModel('eb2cproduct/attributes');
		$value = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$model,
			'_getMappedFieldValue',
			array($fieldName, $data)
		);
		$this->assertSame($expected, $value);
	}

	/**
	 * verify a the correct column name for the frontend type is returned.
	 * @dataProvider dataProvider
	 */
	public function testGetDefaultValueFieldName($frontendType, $expected)
	{
		$model = Mage::getModel('eb2cproduct/attributes');
		$value = $this->_reflectMethod($model, '_getDefaultValueFieldName')
			->invoke($model, $frontendType);
		$this->assertSame($expected, $value);
	}

	/**
	 * verify an attribute data record is returned with correct data
	 * @loadExpectation
	 */
	public function testMakeAttributeRecord()
	{
		$model = Mage::getModel('eb2cproduct/attributes');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$model,
			'_defaultAttributesConfig',
			$this->_configArray
		);
		$attrData = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$model,
			'_makeAttributeRecord',
			array($this->_configArray['default']['tax_code'])
		);
		$this->assertNotEmpty($attrData);
		$e = $this->expected('tax_code');
		$this->assertEquals($e->getData(), $attrData);
	}

	/**
	 * return cached data on consecutive calls
	 */
	public function testGetAttributesDataCache()
	{
		$model = $this->getModelMock('eb2cproduct/attributes', array(
			'_loadDefaultAttributesConfig',
			'_makeAttributeRecord'
		));
		$model->expects($this->any())
			->method('_loadDefaultAttributesConfig')
			->will($this->returnValue($this->_configArray));
		$model->expects($this->once())
			->method('_makeAttributeRecord')
			->will($this->onConsecutiveCalls(array('the data'), array('should never get this')));

		$result = $model->getAttributesData();
		$this->assertEquals(array('tax_code' => array('the data')), $result);
		// should get the same thing the next time
		$result = $model->getAttributesData();
		$this->assertEquals(array('tax_code' => array('the data')), $result);
	}
}
