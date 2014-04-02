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
		$this->assertInstanceOf('DOMNode', $result);
		$doc->appendChild($result);
		$this->assertSame(sprintf('<Value>%s</Value>', $attrValue), $doc->C14N());
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
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim::passOperationType method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Helper_Pim::passOperationType method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                EbayEnterprise_Dom_Document object
	 * Expectation 2: the method EbayEnterprise_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked EbayEnterprise_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object
	 */
	public function testPassOperationType()
	{
		$attrValue = '';
		$operationType = EbayEnterprise_Eb2cProduct_Helper_Pim::DEFAULT_OPERATION_TYPE;
		$attribute = '_operation_type';
		$domAttr = new DOMAttr('operation_type', $operationType);

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

		$this->assertSame($domAttr, $pimHelperMock->passOperationType($attrValue, $attribute, $productMock, $domMock));
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
	public function testcreateBool()
	{
		$value = 'yes';
		$result	= 'true';

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, $pimHelperMock->createBool($value));
	}
}
