<?php
class EbayEnterprise_Eb2cProduct_Test_Helper_PimTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function provideDefaultValue()
	{
		return array(array('some attribute value'), array(null));
	}
	/**
	 * return inner value element contining $attrValue.
	 * @test
	 * @dataProvider provideDefaultValue
	 */
	public function testGetValueAsDefault($attrValue)
	{
		$product = $this->getModelMock('catalog/product');
		$attribute = $this->getModelMock('catalog/entity_attribute');
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$result = Mage::helper('eb2cproduct/pim')
			->getValueAsDefault($attrValue, $attribute, $product, $doc);
		if ($attrValue) {
			$this->assertInstanceOf('DOMNode', $result);
			$doc->appendChild($result);
			$this->assertSame(sprintf('<Value>%s</Value>', $attrValue), $doc->C14N());
		} else {
			$this->assertNull($result);
		}
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passString method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passString will be invoked by this test given
	 *                a string attrValue, a string attribute code, a Mage_Catalog_Model_Product object and a DOMDocument
	 *                object, then we expect the method EbayEnterprise_Eb2cProduct_Helper_Pim::createStringNode given
	 *                an attrValue string that either less or equal to the const EbayEnterprise_Eb2cProduct_Helper_Pim::STRING_LIMIT
	 *                passed as first parameter and the DOMDocument as second parameter which will return a DOMNode object
	 * @test
	 */
	public function testPassString()
	{
		$attrValue = 'simple string value';
		$attribute = $this->getModelMock('catalog/entity_attribute');

		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createStringNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createStringNode')
			->with($this->identicalTo($attrValue), $this->identicalTo($doc))
			->will($this->returnValue($nodeMock));

		$this->assertSame($nodeMock, $pimHelperMock->passString($attrValue, $attribute, $product, $doc));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passSKU method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passSKU will be invoked by this test given
	 *                a string attrValue, a string attribute code, a Mage_Catalog_Model_Product object and a DOMDocument
	 *                object, then we expect the method EbayEnterprise_Eb2cProduct_Helper_Pim::createStringNode given
	 *                an attrValue string that get de-normalized when called the method
	 *                EbayEnterprise_Eb2cCore_Helper_Data::denormalizeSku given the attrValue and the catalog id from
	 *                EbayEnterprise_Eb2cCore_Model_Config_Registry object that get return when call the
	 *                EbayEnterprise_Eb2cProduct_Helper_Data::getConfigModel method, this de-normalize sku
	 *                passed as first parameter and the DOMDocument as second parameter which will return a DOMNode object
	 * @test
	 */
	public function testPassSKU()
	{
		$attrValue = '54-83884884';
		$denormalizeSku = '83884884';
		$catalogId = '54';
		$attribute = 'sku';

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'catalogId' => $catalogId
			))));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('denormalizeSku'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('denormalizeSku')
			->with($this->identicalTo($attrValue), $this->identicalTo($catalogId))
			->will($this->returnValue($denormalizeSku));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createStringNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createStringNode')
			->with($this->identicalTo($denormalizeSku), $this->identicalTo($doc))
			->will($this->returnValue($nodeMock));

		$this->assertSame($nodeMock, $pimHelperMock->passSKU($attrValue, $attribute, $product, $doc));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passPrice method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passPrice will be invoked by this test given
	 *                a string attrValue, a string attribute code, a Mage_Catalog_Model_Product object and a DOMDocument
	 *                object, then we expect the method EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode given
	 *                an attrValue string that get rounded to two decimal point when invoked the method
	 *                Mage_Core_Model_Store::roundPrice given the attrValue this value then get passed as first parameter
	 *                and the DOMDocument as second parameter which will return a DOMNode object
	 * @test
	 */
	public function testPassPrice()
	{
		$attrValue = '10.839';
		$attrValueRound = 10.84;
		$attribute = 'price';

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('roundPrice'))
			->getMock();
		$storeMock->expects($this->once())
			->method('roundPrice')
			->with($this->identicalTo($attrValue))
			->will($this->returnValue($attrValueRound));
		$this->replaceByMock('model', 'core/store', $storeMock);

		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($attrValueRound), $this->identicalTo($doc))
			->will($this->returnValue($nodeMock));

		$this->assertSame($nodeMock, $pimHelperMock->passPrice($attrValue, $attribute, $product, $doc));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passDecimal method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passDecimal will be invoked by this test given
	 *                a string attrValue, a string attribute code, a Mage_Catalog_Model_Product object and a DOMDocument
	 *                object, then we expect the method EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode given
	 *                an attrValue string that return decimal value when invoked the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createDecimal given the attrValue this value then get
	 *                passed as first parameter and the DOMDocument as second parameter which will return a DOMNode object
	 * @test
	 */
	public function testPassDecimal()
	{
		$attrValue = '10.839';
		$attrValueDecimal = 10.839;
		$attribute = 'special_price';

		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode', 'createDecimal'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($attrValueDecimal), $this->identicalTo($doc))
			->will($this->returnValue($nodeMock));
		$pimHelperMock->expects($this->once())
			->method('createDecimal')
			->with($this->identicalTo($attrValue))
			->will($this->returnValue($attrValueDecimal));

		$this->assertSame($nodeMock, $pimHelperMock->passDecimal($attrValue, $attribute, $product, $doc));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passDate method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passDate will be invoked by this test given
	 *                a string attrValue, a string attribute code, a Mage_Catalog_Model_Product object and a DOMDocument
	 *                object, then we expect the method EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode given
	 *                an attrValue string that return proper xml datetime value when invoked the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createDateTime given the attrValue this value then get
	 *                passed as first parameter and the DOMDocument as second parameter which will return a DOMNode object
	 * @test
	 */
	public function testPassDate()
	{
		$attrValue = '04/01/2014 12:23:30:12';
		$attrValueDateTime = '2014-04-01 12:23:30:12';
		$attribute = 'create_at';

		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode', 'createDateTime'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($attrValueDateTime), $this->identicalTo($doc))
			->will($this->returnValue($nodeMock));
		$pimHelperMock->expects($this->once())
			->method('createDateTime')
			->with($this->identicalTo($attrValue))
			->will($this->returnValue($attrValueDateTime));

		$this->assertSame($nodeMock, $pimHelperMock->passDate($attrValue, $attribute, $product, $doc));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passInteger method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passInteger will be invoked by this test given
	 *                a string attrValue, a string attribute code, a Mage_Catalog_Model_Product object and a DOMDocument
	 *                object, then we expect the method EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode given
	 *                an attrValue string that return proper integer value when invoked the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createInteger given the attrValue this value then get
	 *                passed as first parameter and the DOMDocument as second parameter which will return a DOMNode object
	 * @test
	 */
	public function testPassInteger()
	{
		$attrValue = '9';
		$attrValueInt = 9;
		$attribute = 'entity_id';

		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode', 'createInteger'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($attrValueInt), $this->identicalTo($doc))
			->will($this->returnValue($nodeMock));
		$pimHelperMock->expects($this->once())
			->method('createInteger')
			->with($this->identicalTo($attrValue))
			->will($this->returnValue($attrValueInt));

		$this->assertSame($nodeMock, $pimHelperMock->passInteger($attrValue, $attribute, $product, $doc));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passYesNoToBool method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passYesNoToBool will be invoked by this test given
	 *                a string attrValue, a string attribute code, a Mage_Catalog_Model_Product object and a DOMDocument
	 *                object, then we expect the method EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode given
	 *                an attrValue string that return proper string boolean value when invoked the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createBool given the attrValue this value then get
	 *                passed as first parameter and the DOMDocument as second parameter which will return a DOMNode object
	 * @test
	 */
	public function testPassYesNoToBool()
	{
		$attrValue = 'yes';
		$attrValueBool = 'true';
		$attribute = 'enabled';

		$doc = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode', 'createBool'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($attrValueBool), $this->identicalTo($doc))
			->will($this->returnValue($nodeMock));
		$pimHelperMock->expects($this->once())
			->method('createBool')
			->with($this->identicalTo($attrValue))
			->will($this->returnValue($attrValueBool));

		$this->assertSame($nodeMock, $pimHelperMock->passYesNoToBool($attrValue, $attribute, $product, $doc));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passGsiClientId method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passGsiClientId method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                EbayEnterprise_Dom_Document object
	 * Expectation 2: the method EbayEnterprise_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked EbayEnterprise_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object, the method EbayEnterprise_Eb2cCore_Helper_Feed::getClientId will be called
	 *                and return the client id which will be set in the DOMAttr value property
	 */
	public function testPassGsiClientId()
	{
		$attrValue = '';
		$clientId = 'ABCD';
		$attribute = '_gsi_client_id';
		$domAttr = new DOMAttr('gsi_client_id', $clientId);

		$feedHelper = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getClientId'))
			->getMock();
		$feedHelper->expects($this->once())
			->method('getClientId')
			->will($this->returnValue($clientId));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getDomAttr'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('_getDomAttr')
			->with($this->identicalTo($domMock), $this->identicalTo($attribute))
			->will($this->returnValue($domAttr));

		$this->assertSame($domAttr, $pimHelperMock->passGsiClientId($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test getting the operation type for a product based on when the product
	 * was created. If the product was created after the last run of the export,
	 * found via a config value, the operation type should be "Add". Otherwise,
	 * the operation type should be "Change".
	 * @test
	 */
	public function testPassOperationType()
	{
		// As this is a pseudo-product attribute, the value will be empty and the
		// attribute code should will begin with an '_'
		$attributeValue = '';
		$attributeCode = '_operation_type';
		// These time formats match the formats actually being used.
		$addProduct = Mage::getModel('catalog/product', array('created_at' => '2014-02-02 00:00:00'));
		$changeProduct = Mage::getModel('catalog/product', array('created_at' => '2014-01-01 00:00:00'));
		// Any products created after this time should get "Add" operation type,
		// any created before should get "Change" operation type
		$lastRunTime = '2014-01-15T00:00:00+00:00';

		$doc = new EbayEnterprise_Dom_Document();
		$addAttr = $doc->createAttribute('operation_type');
		$addAttr->value = 'Add';
		$changeAttr = $doc->createAttribute('operation_type');
		$changeAttr->value = 'Change';

		// Stub out the config registry and helper that delivers it so a known
		// last run time can be reliably returned.
		$cfg = $this->buildCoreConfigRegistry(array('pimExportFeedCutoffDate' => $lastRunTime));
		$prodHelper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$prodHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($cfg));
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$changeResult = Mage::helper('eb2cproduct/pim')->passOperationType($attributeValue, $attributeCode, $changeProduct, $doc);
		$this->assertSame('Change', $changeResult->value);
		$this->assertSame('operation_type', $changeResult->name);
		$addResult = Mage::helper('eb2cproduct/pim')->passOperationType($attributeValue, $attributeCode, $addProduct, $doc);
		$this->assertSame('Add', $addResult->value);
		$this->assertSame('operation_type', $addResult->name);
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passCatalogId method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passCatalogId method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                EbayEnterprise_Dom_Document object
	 * Expectation 2: the method EbayEnterprise_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked EbayEnterprise_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object, the method EbayEnterprise_Eb2cCore_Helper_Feed::getCatalogId will be called
	 *                and return the client id which will be set in the DOMAttr value property
	 */
	public function testPassCatalogId()
	{
		$attrValue = '';
		$catalogId = '1234';
		$attribute = '_catalog_id';
		$domAttr = new DOMAttr('catalog_id', $catalogId);

		$feedHelper = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getCatalogId'))
			->getMock();
		$feedHelper->expects($this->once())
			->method('getCatalogId')
			->will($this->returnValue($catalogId));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getDomAttr'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('_getDomAttr')
			->with($this->identicalTo($domMock), $this->identicalTo($attribute))
			->will($this->returnValue($domAttr));

		$this->assertSame($domAttr, $pimHelperMock->passCatalogId($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passStoreId method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passStoreId method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                EbayEnterprise_Dom_Document object
	 * Expectation 2: the method EbayEnterprise_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked EbayEnterprise_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object, the method EbayEnterprise_Eb2cCore_Helper_Feed::getStoreId will be called
	 *                and return the client id which will be set in the DOMAttr value property
	 */
	public function testPassStoreId()
	{
		$attrValue = '';
		$storeId = '9383';
		$attribute = '_store_id';
		$domAttr = new DOMAttr('store_id', $storeId);

		$feedHelper = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$feedHelper->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getDomAttr'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('_getDomAttr')
			->with($this->identicalTo($domMock), $this->identicalTo($attribute))
			->will($this->returnValue($domAttr));

		$this->assertSame($domAttr, $pimHelperMock->passStoreId($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::_getDomAttr method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim::_getDomAttr given a mocked
	 *                EbayEnterprise_Dom_Document object and a string of attribute code it will then call the method
	 *                EbayEnterprise_Dom_Document::createAttribute given a normalize attribut code
	 *
	 */
	public function testGetDomAttr()
	{
		$nodeAttribute = '_gsi_client_id';
		$normalizeAttribute = 'gsi_client_id';
		$domAttr = new DOMAttr($normalizeAttribute);

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('createAttribute'))
			->getMock();
		$docMock->expects($this->once())
			->method('createAttribute')
			->with($this->identicalTo($normalizeAttribute))
			->will($this->returnValue($domAttr));

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($domAttr, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimHelperMock, '_getDomAttr', array($docMock, $nodeAttribute)
		));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::createStringNode method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim::createStringNode given a
	 *                string value and a DOMDocument object, the method EbayEnterprise_Dom_Document::createCDataSection is
	 *                expected to be invoked given the value, which will return a DOMNode object
	 */
	public function testCreateStringNode()
	{
		$value = 'some value';

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('createCDataSection'))
			->getMock();
		$docMock->expects($this->once())
			->method('createCDataSection')
			->with($this->identicalTo($value))
			->will($this->returnValue($nodeMock));

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($nodeMock, $pimHelperMock->createStringNode($value, $docMock));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode given a
	 *                string value and a DOMDocument object, the method EbayEnterprise_Dom_Document::createTextNode is
	 *                expected to be invoked given the value, which will return a DOMNode object
	 */
	public function testCreateTextNode()
	{
		$value = 10.88;

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode'))
			->getMock();
		$docMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($value))
			->will($this->returnValue($nodeMock));

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($nodeMock, $pimHelperMock->createTextNode($value, $docMock));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::createDateTime method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim::createDateTime given a
	 *                string value and expect the return value to match a given result value
	 */
	public function testCreateDateTime()
	{
		$value = '2014-03-15 12:33:48';
		$result	= '2014-03-15T12:33:48+00:00';

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, $pimHelperMock->createDateTime($value));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::createInteger method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim::createInteger given a
	 *                string value and expect the return value to be an integer value
	 */
	public function testCreateInteger()
	{
		$value = '12';
		$result	= 12;

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, $pimHelperMock->createInteger($value));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::createDecimal method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim::createDecimal given a
	 *                string value and expect the return value to be an float value
	 */
	public function testCreateDecimal()
	{
		$value = '12.677';
		$result	= 12.677;

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, $pimHelperMock->createDecimal($value));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::createBool method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim::createBool given a
	 *                string value and expect the return value to be string representing a boolean value
	 */
	public function testCreateBool()
	{
		$value = 'yes';
		$result	= 'true';

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, $pimHelperMock->createBool($value));
	}

	/**
	 * Expectations:
	 *  call passSKU on the product if it is configurable.
	 *  call passSKU on the parent of the product if it is not configurable and has a parent.
	 * @param  boolean $isConfigurable
	 * @param  boolean $hasParent
	 * @test
	 * @dataProvider provideTrueFalse
	 */
	public function testPassStyleId($isConfigurable)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');
		$attribute = 'sku';
		$pimHelper = $this->getHelperMock('eb2cproduct/pim', array('passSKU'));
		$product = $this->getModelMock('catalog/product', array('isConfigurable', 'getId'));
		$parentIds = array(1);
		$parentProduct = $this->getModelMock('catalog/product', array('load'));
		$configurableType = $this->getResourceModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('getParentIdsByChild'))
			->getMock();

		$product->expects($this->any())
			->method('isConfigurable')
			->will($this->returnValue($isConfigurable));
		$product->expects($this->any())
			->method('getId')
			->will($this->returnValue(2));

		$configurableType->expects($this->any())
			->method('getParentIdsByChild')
			->will($this->returnValue($parentIds));

		$parentProduct->expects($this->any())
			->method('load')
			->with($parentIds[0])
			->will($this->returnSelf());

		$pimHelper->expects($this->once())
			->method('passSKU')
			->with(
				$this->anything(),
				$this->identicalTo($attribute),
				$this->identicalTo($isConfigurable ? $product : $parentProduct),
				$this->identicalTo($doc)
			)
			->will($this->returnValue('result of passSKU'));

		$this->replaceByMock('model', 'catalog/product', $parentProduct);
		$this->replaceByMock('resource_singleton', 'catalog/product_type_configurable', $configurableType);

		$result = $pimHelper->passStyleId('dontcare', 'dontcare', $product, $doc);
		$this->assertSame('result of passSKU', $result);
	}
	/**
	 * if the product is not a gift card null should be returned.
	 * @test
	 */
	public function testPassGiftCardNotGiftCard()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');
		$pimHelper = $this->getHelperMock('eb2cproduct/pim', array('none'));
		$product = $this->getModelMock('catalog/product', array('none'));
		$product->setType('notgiftcard');

		$result = $pimHelper->passGiftCard('dontcare', 'dontcare', $product, $doc);
		$this->assertNull($result);
	}
	public function provideGiftCardFlags()
	{
		return array(
			array('0', '1', '0', Enterprise_GiftCard_Model_Giftcard::TYPE_VIRTUAL, true),
			array('1', '0', '1', Enterprise_GiftCard_Model_Giftcard::TYPE_PHYSICAL, true),
			array('0', '0', '0', Enterprise_GiftCard_Model_Giftcard::TYPE_PHYSICAL, false),
		);
	}
	/**
	 * return a fragment that contains the giftcard subtree
	 * if the giftcard is virtual, the Digital element will contain 'false'
	 * @test
	 * @dataProvider provideGiftCardFlags
	 */
	public function testPassGiftCard($useConfig, $prodAllowMessage, $configAllowMessage, $isVirtual, $allowMessage)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');
		$name = 'the giftcard name';
		$pimHelper = $this->getHelperMock('eb2cproduct/pim', array('passString'));
		$product = $this->getModelMock('catalog/product', array('none'));

		$configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
		$configRegistryMock->expects($this->any())
			->method('getConfigData')
			->will($this->returnValueMap(array(
				array(Enterprise_GiftCard_Model_Giftcard::XML_PATH_ALLOW_MESSAGE, $configAllowMessage),
				array(Enterprise_GiftCard_Model_Giftcard::XML_PATH_MESSAGE_MAX_LENGTH, 10),
			)));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

		$product->addData(array(
			'use_config_allow_message' => $useConfig,
			'allow_message' => $prodAllowMessage,
			'name' => $name,
			'type_id' => Enterprise_GiftCard_Model_Catalog_Product_Type_Giftcard::TYPE_GIFTCARD,
			'gift_card_type' => $isVirtual,
		));

		$result = $pimHelper->passGiftCard('dontcare', 'dontcare', $product, $doc);
		$doc->documentElement->appendChild($result);
		$messageMaxLen = $allowMessage ? '10' : '0';
		$digitalField = $isVirtual === Enterprise_GiftCard_Model_Giftcard::TYPE_VIRTUAL ? 'true' : 'false';
		$expected = Mage::helper('eb2ccore')->getNewDomDocument();
		$expected->preserveWhiteSpace = false;
		$expected->loadXML(sprintf('
			<root>
				<Digital><![CDATA[%s]]></Digital>
				<MessageMaxLength><![CDATA[%s]]></MessageMaxLength>
				<CardFacingDisplayName><![CDATA[%s]]></CardFacingDisplayName>
			</root>',
			$digitalField,
			$messageMaxLen,
			$name
		));
		$this->assertSame($expected->saveXML(), $doc->saveXML());
	}
	protected function _newDocument($xml=null)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		if ($xml) {
			$doc->loadXML($xml);
		}
		return $doc;
	}
	/**
	 * verify a fragment is returned containing the product links
	 * @param  bool   $allowMessage
	 * @param  bool   $isVirtual
	 * @test
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function testPassProductLinks()
	{
		$doc = $this->_newDocument('<root/>');
		$pimHelper = $this->getHelperMock('eb2cproduct/pim', array('passString', 'passSKU'));
		$product = $this->getModelMock('catalog/product', array('getRelatedProducts', 'getCrossSellProducts', 'getUpSellProducts'));

		$linked1 = $this->getModelMock('catalog/product', array('getSku'));
		$linked2 = $this->getModelMock('catalog/product', array('getSku'));
		$linked3 = $this->getModelMock('catalog/product', array('getSku'));

		$product->expects($this->any())
			->method('getRelatedProducts')
			->will($this->returnValue(array($linked1)));
		$product->expects($this->any())
			->method('getUpSellProducts')
			->will($this->returnValue(array($linked2)));
		$product->expects($this->any())
			->method('getCrossSellProducts')
			->will($this->returnValue(array($linked3)));

		$linked1->expects($this->any())
			->method('getSku')
			->will($this->returnValue('linked1'));
		$linked2->expects($this->any())
			->method('getSku')
			->will($this->returnValue('linked2'));
		$linked3->expects($this->any())
			->method('getSku')
			->will($this->returnValue('linked3'));

		$pimHelper->expects($this->any())
			->method('passSKU')
			->will($this->returnCallback(
				function ($a, $b, $product, $doc) {
					return $doc->createCDataSection($product->getSku());
				}
			));

		$result = $pimHelper->passProductLinks('dontcare', 'dontcare', $product, $doc);
		$doc->documentElement->appendChild($result);

		$expected = $this->_newDocument('
			<root>
				<ProductLink link_type="ES_Accessory" operation_type="Add" position="1">
					<LinkToUniqueID><![CDATA[linked1]]></LinkToUniqueID>
				</ProductLink>
				<ProductLink link_type="ES_UpSelling" operation_type="Add" position="1">
					<LinkToUniqueID><![CDATA[linked2]]></LinkToUniqueID>
				</ProductLink>
				<ProductLink link_type="ES_CrossSelling" operation_type="Add" position="1">
					<LinkToUniqueID><![CDATA[linked3]]></LinkToUniqueID>
				</ProductLink>
			</root>'
		);
		$this->assertSame($expected->saveXML(), $doc->saveXML());
	}
	/**
	 * verify a fragment is returned containing the nodes for the category links
	 * @test
	 */
	public function testPassCategoryLinks()
	{
		$doc = $this->_newDocument('<root/>');
		$pimHelper = $this->getHelperMock('eb2cproduct/pim', array('none'));
		$product = $this->getModelMock('catalog/product', array('getCategoryCollection'));
		$collection = $this->getResourceModelMockBuilder('catalog/category_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemById', 'load', 'addAttributeToSelect'))
			->getMock();
		$category = $this->getModelMock('catalog/category', array('getPath', 'getName', 'getId'));
		$category2 = $this->getModelMock('catalog/category', array('getPath', 'getName', 'getId'));

		$product->expects($this->any())
			->method('getCategoryCollection')
			->will($this->returnValue($collection));
		$category->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$category->expects($this->any())
			->method('getPath')
			->will($this->returnValue('1'));
		$category->expects($this->any())
			->method('getName')
			->will($this->returnValue('parent'));
		$category2->expects($this->any())
			->method('getId')
			->will($this->returnValue(18));
		$category2->expects($this->any())
			->method('getPath')
			->will($this->returnValue('1/18'));
		$category2->expects($this->any())
			->method('getName')
			->will($this->returnValue('child'));
		$collection->expects($this->any())
			->method('getItemById')
			->will($this->returnValueMap(array(
				array(1, $category),
				array(18, $category2),
			)));
		$collection->expects($this->any())
			->method('addAttributeToSelect')
			->will($this->returnSelf());
		$collection->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collection, '_items', array($category, $category2));
		$this->replaceByMock('resource_model', 'catalog/category_collection', $collection);

		$result = $pimHelper->passCategoryLinks('dontcare', 'dontcare', $product, $doc);
		$this->assertNotNull($result);
		$doc->documentElement->appendChild($result);

		$expected = $this->_newDocument('
			<root>
				<CategoryLink import_mode="Replace">
					<Name><![CDATA[parent]]></Name>
				</CategoryLink>
				<CategoryLink import_mode="Replace">
					<Name><![CDATA[parent-child]]></Name>
				</CategoryLink>
			</root>'
		);
		$this->assertSame($expected->saveXML(), $doc->saveXML());
	}
	/**
	 * verify Y/N is returned when the value evaluates to
	 * true/false respectively.
	 * @test
	 */
	public function testPassGiftWrap()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');
		$pimHelper = $this->getHelperMock('eb2cproduct/pim', array('createStringNode'));
		$product = $this->getModelMock('catalog/product', array('getColor'));

		$pimHelper->expects($this->any())
			->method('createStringNode')
			->will($this->returnArgument(0));
		$this->assertSame('Y', $pimHelper->passGiftWrap(true, 'gift_wrap', $product, $doc));
		$this->assertSame('N', $pimHelper->passGiftWrap(false, 'gift_wrap', $product, $doc));
	}
}
