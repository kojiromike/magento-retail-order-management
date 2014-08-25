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

class EbayEnterprise_Eb2cCore_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_helper;
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = Mage::helper('eb2ccore');
	}

	/**
	 * Test getNewDomDocument - although providerApiCall calls it, does not get noted as covered.
	 */
	public function testGetNewDomDocument()
	{
		$this->assertInstanceOf('EbayEnterprise_Dom_Document', Mage::helper('eb2ccore')->getNewDomDocument());
	}

	public function providerApiCall()
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;
		$quantityRequestMessage->createChild(
			'QuantityRequest',
			null,
			array('lineId' => 1, 'itemId' => 'SKU-1234')
		);
		$quantityRequestMessage->createChild(
			'QuantityRequest',
			null,
			array('lineId' => 2, 'itemId' => 'SKU-4321')
		);
		return array(
			array(
				$domDocument, 'http://eb2c.edge.mage.tandev.net/GSI%20eb2c%20Web%20Service%20Schemas%20v1.0/Inventory-Service-Quantity-1.0.xsd'
			)
		);
	}

	/**
	 * Mock out the config helper.
	 *
	 * @param  array $mockConfig Config data as nested arrays to be used for a returnValueMap
	 */
	protected function _mockConfig($mockConfig)
	{
		$mock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get'))
			->getMock();
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($mockConfig));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
	}

	/**
	 * test generating the API URIs
	 */
	public function testApiUriCreation()
	{
		$this->_mockConfig(array(
			array('apiHostname', 'api.example.com'),
			array('apiMajorVersion', 'M'),
			array('apiMinorVersion', 'm'),
			array('storeId', 'store_id'),
		));
		$helper = Mage::helper('eb2ccore');
		// simplest case - just a service and operation
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/address/validate.xml',
			$helper->getApiUri('address', 'validate')
		);
		// service, operation and params
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/payments/creditcard/auth/VC.xml',
			$helper->getApiUri('payments', 'creditcard', array('auth', 'VC'))
		);
		// service, operation, params and type
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/allocations/delete.json',
			$helper->getApiUri('inventory', 'allocations', array('delete'), 'json')
		);
	}

	/**
	 * test generating the API URIs with a non default store.
	 * @loadFixture
	 */
	public function testApiUriCreationNonDefaultStore()
	{
		$this->setCurrentStore('canada');
		$helper = Mage::helper('eb2ccore');
		// service, operation, params and type
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id2/inventory/allocations/delete.json',
			$helper->getApiUri('inventory', 'allocations', array('delete'), 'json')
		);
	}

	/**
	 * Test checking validity of sftp settings
	 *
	 * @param  string $username SFTP username config setting
	 * @param  string $host     SFTP host/location config setting
	 * @param  string $authType SFTP auth type - password or pub_key config setting
	 * @param  string $password SFTP password config setting
	 * @param  string $privKey  SFTP private key config setting
	 *
	 * @dataProvider dataProvider
	 */
	public function testIsValidFtpSettingsWithSftpData($username, $host, $authType, $password, $privKey)
	{
		$this->_mockConfig(array(
			array('sftpUsername', $username),
			array('sftpLocation', $host),
			array('sftpAuthType', $authType),
			array('sftpPassword', $password),
			array('sftpPrivateKey', $privKey),
		));
		$this->assertSame(
			$this->expected('set-%s-%s-%s-%s-%s', $username, $host, $authType, $password, $privKey)->getIsValid(),
			Mage::helper('eb2ccore')->isValidFtpSettings()
		);
	}

	public function providerXmlToMageLangFrmt()
	{
		return array(
			array('en-US'),
		);
	}

	public function providerExtractNodeVal()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<Product><Item><sku>1234</sku></Item></Product>');
		$xpath = new DOMXPath($doc);
		$items = $xpath->query('//Item');
		$skuNode = null;
		foreach($items as $item) {
			$skuNode = $xpath->query('sku/text()', $item);
		}
		return array(array($skuNode));
	}

	/**
	 * verify the language code string is converted to EB2C's format.
	 */
	public function testMageToXmlLangFrmt()
	{
		$this->assertSame('en-US', Mage::helper('eb2ccore')->mageToXmlLangFrmt('en_US'));
	}

	/**
	 * Test extractNodeVal method
	 *
	 * @param DOMNodeList $nodeList
	 *
	 * @dataProvider providerExtractNodeVal
	 */
	public function testExtractNodeVal(DOMNodeList $nodeList)
	{
		$this->assertSame('1234', Mage::helper('eb2ccore')->extractNodeVal($nodeList));
	}

	public function providerExtractNodeAttributeVal()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<Product><Item><sku gsi_client_id="TAN-CLI">1234</sku></Item></Product>');
		$xpath = new DOMXPath($doc);
		$items = $xpath->query('//Item');
		$skuNode = null;
		foreach($items as $item) {
			$skuNode = $xpath->query('sku', $item);
		}
		return array(array($skuNode, 'gsi_client_id'));
	}

	/**
	 * Test extractNodeAttributeVal method
	 * @param DOMNodeList $nodeList
	 * @param string $attributeName
	 *
	 * @dataProvider providerExtractNodeAttributeVal
	 */
	public function testExtractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
	{
		$this->assertSame('TAN-CLI', Mage::helper('eb2ccore')->extractNodeAttributeVal($nodeList, $attributeName));
	}

	/**
	 * Test that we can transform a Magento shipping method into an eb2c shipping method.
	 * @loadFixture
	 * @dataProvider dataProvider
	 */
	public function testConvertShipMethod($mageShipMethod)
	{
		$this->assertSame(
			$this->expected($mageShipMethod)->getEb2cShipMethod(),
			Mage::helper('eb2ccore')->lookupShipMethod($mageShipMethod)
		);
	}

	/**
	 * Test normalizing a product style id to match formatting for skus
	 * @param  string $style   The product style id
	 * @param  string $catalog The product catalog id
	 * @dataProvider dataProvider
	 */
	public function testNormalizeSku($styleId, $catalogId)
	{
		$normalized = Mage::helper('eb2ccore')->normalizeSku($styleId, $catalogId);
		$this->assertSame($this->expected('style-%s-%s', $styleId, $catalogId)->getStyleId(), $normalized);
	}

	/**
	 * Test EbayEnterprise_Eb2cCore_Helper_Data::denormalizeSku method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cCore_Helper_Data::denormalizeSku given a sku
	 *                with sku and a catalog id to denormalize the sku
	 */
	public function testDenormalizeSku()
	{
		$catalogId = '54';
		$testData = array(
			array(
				'sku' => '54-49392002',
				'expect' => '49392002'
			),
			array(
				'sku' => '9484884',
				'expect' => '9484884'
			)
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], Mage::helper('eb2ccore')->denormalizeSku($data['sku'], $catalogId));
		}
	}

	/**
	 * Testing the extractQueryNodeValue method
	 * @loadFixture
	 */
	public function testExtractQueryNodeValue()
	{
		$vfs           = $this->getFixture()->getVfs();
		$coreHelper    = Mage::helper('eb2ccore');
		$mySampleQuery = '//MessageHeader/MessageData/MessageId';

		// Good returns the value of the MessageId string:
		$goodDoc = $coreHelper->getNewDomDocument();
		$goodDoc->load($vfs->url('sample/good.xml'));
		$this->assertSame(
			'7',
			$coreHelper->extractQueryNodeValue(new DOMXpath($goodDoc), $mySampleQuery)
		);

		// 'Bad' in this case node not found - returns null
		$badDoc = $coreHelper->getNewDomDocument();
		$badDoc->load($vfs->url('sample/bad.xml'));
		$this->assertSame(
			null,
			$coreHelper->extractQueryNodeValue(new DOMXpath($badDoc), $mySampleQuery)
		);

		// An empty node should return an empty string ''
		$emptyDoc = $coreHelper->getNewDomDocument();
		$emptyDoc->load($vfs->url('sample/empty.xml'));
		$this->assertSame(
			'',
			$coreHelper->extractQueryNodeValue(new DOMXpath($emptyDoc), $mySampleQuery)
		);
	}
	/**
	 * verify the core feed helper's invokeCallback method is called correctly.
	 */
	public function testInvokeCallback()
	{
		$argArray = array('the args');
		$feedHelper = $this->getHelperMock('eb2ccore/feed', array('invokeCallback'));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);
		$feedHelper->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($argArray))
			->will($this->returnValue('foo'));
		$helper = Mage::helper('eb2ccore');
		$this->assertSame('foo', $helper->invokeCallback($argArray));
	}

	/**
	 * Test getProductHtsCodeByCountry method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cCore_Helper_Data::getProductHtsCodeByCountry will be invoked by this test
	 *                given a mock Mage_Catalog_Model_Product object and known country code, then the test expect
	 *                the method Mage_Catalog_Model_Product::getHtsCodes to be called once and return a know serialize
	 *                string in which will be unzerialized and loop through to return the match htscode data match match
	 *                the given country code
	 */
	public function testGetProductHtsCodeByCountry()
	{
		$countryCode = 'US';
		$data = array(array('destination_country' => $countryCode, 'hts_code' => '73739.33'),);
		$htscodes = serialize($data);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getHtsCodes'))
			->getMock();
		$productMock->expects($this->once())
			->method('getHtsCodes')
			->will($this->returnValue($htscodes));

		$this->assertSame($data[0]['hts_code'], Mage::helper('eb2ccore')->getProductHtsCodeByCountry(
			$productMock, $countryCode
		));
	}

	/**
	 * @see self::testGetProductHtsCodeByCountry, however this test is expecting no hts_code data in which
	 *      the return value for the method EbayEnterprise_Eb2cCore_Helper_Data::getProductHtsCodeByCountry to return null
	 */
	public function testGetProductHtsCodeByCountryNoHtsCodeFound()
	{
		$countryCode = 'US';
		$htscodes = serialize(array());

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getHtsCodes'))
			->getMock();
		$productMock->expects($this->once())
			->method('getHtsCodes')
			->will($this->returnValue($htscodes));

		$this->assertSame(null, Mage::helper('eb2ccore')->getProductHtsCodeByCountry(
			$productMock, $countryCode
		));
	}
	/**
	 * data provider for self::testParseBool method
	 * @return array
	 */
	public function parseBoolDataProvider()
	{
		return array(
			array(true, true),
			array(false, false),
			array(false, array()),
			array(true, array(range(1, 4))),
			array(true, '1'),
			array(true, 'on'),
			array(true, 't'),
			array(true, 'true'),
			array(true, 'y'),
			array(true, 'yes'),
			array(false, 'false'),
			array(false, 'off'),
			array(false, 'f'),
			array(false, 'n'),
		);
	}
	/**
	 * Test parseBool the feed
	 * @param bool $expect
	 * @param mixed $s
	 * @dataProvider parseBoolDataProvider
	 */
	public function testParseBool($expect, $s)
	{
		$this->assertSame($expect, Mage::helper('eb2ccore')->parseBool($s));
	}
	/**
	 * Testing that the method EbayEnterprise_Eb2cCore_Helper_Data::extractXmlToArray
	 * when called passed in a DOMNode, an array of callback mapping and a DOMXPath
	 * it will extract the data using callbacks and return an array of extracted data.
	 */
	public function testExtractXmlToArray()
	{
		$apiXmlNs = 'http://api.gsicommerce.com/schema/checkout/1.0';
		$sku = '45-HTCT60';
		$qty = 4;
		$xml = '
			<root xmlns="' . $apiXmlNs . '">
				<Order>
					<OrderItems>
						<OrderItem id="12112">
							<ItemId>' . $sku . '</ItemId>
							<Quantity>' . $qty . '</Quantity>
							<Name>Will not be exracted out</Name>
						</OrderItem>
					</OrderItems>
				</Order>
			</root>
		';
		$helper = Mage::helper('eb2ccore');
		$doc = $helper->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = $helper->getNewDomXPath($doc);
		$xpath->registerNamespace('a', $apiXmlNs);
		$nodes = $doc->documentElement;
		$mapping = array(
			'sku' => array(
				'class' => 'eb2ccore/map',
				'type' => 'helper',
				'method' => 'extractStringValue',
				'xpath' => 'a:Order/a:OrderItems/a:OrderItem/a:ItemId',
			),
			'qty_ordered' => array(
				'class' => 'eb2ccore/map',
				'type' => 'helper',
				'method' => 'extractIntValue',
				'xpath' => 'a:Order/a:OrderItems/a:OrderItem/a:Quantity',
			),
			// Proving that any map field with a 'disabled' type will not be extracted
			'name' => array(
				'class' => 'eb2ccore/map',
				'type' => 'disabled',
				'method' => 'extractStringValue',
				'xpath' => 'a:Order/a:OrderItems/a:OrderItem/a:Name',
			),
		);

		$expectData = array('sku' => $sku, 'qty_ordered' => $qty);

		$this->assertSame($expectData, Mage::helper('eb2ccore')->extractXmlToArray(
			$nodes, $mapping, $xpath
		));
	}
}
