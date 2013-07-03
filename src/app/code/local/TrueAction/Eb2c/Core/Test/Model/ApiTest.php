<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Core_Test_Model_ApiTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = $this->_getHelper();
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Core_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2ccore');
		}
		return $this->_helper;
	}

	/**
	 * Get Dom instantiated object.
	 * TODO: Why isn't this just a generice core helper? Seems to make sense acutally.
	 * @return TrueAction_Dom_Document
	 */
	public function getDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}


	public function providerApiCall()
	{
		$domDocument = $this->getDomDocument();
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
	 * testing request() method
	 *
	 * @test
	 * @dataProvider providerApiCall
	 */
	public function testRequest($request, $apiUri)
	{
		$api = Mage::getModel('eb2ccore/api');
		$this->assertNotEmpty(
			$api->setUri($apiUri)->request($request)
		);
	}

	/**
	 * testing the setApiTimeout method standalone here
	 * @test
	 */
	public function testSetTimeout()
	{
		$api = Mage::getModel('eb2ccore/api');
		$testTimeout = 16;

		$api->setTimeout($testTimeout);
		$this->assertSame($api->getTimeout(), $testTimeout );
	}

	/**
	 * testing request() method
	 *
	 * @test
	 * @dataProvider providerApiCall
	 */
	public function testApiRequestWithSetTimeout($request, $apiUri)
	{
		$api = Mage::getModel('eb2ccore/api');
		$testTimeout = 8;
		$result = $api->setUri($apiUri)->setTimeout($testTimeout)->request($request);
		$this->assertNotEmpty($result);
		$this->assertSame($api->getTimeout(), $testTimeout);
		$this->assertSame($api->getUri(), $apiUri);
	}

	/**
	 * Test setting http client to something valid
	 */
	public function testValidSetHttpClient()
	{
		$status = true;
		$zendHttpClient = new Zend_Http_Client();
		$api = Mage::getModel('eb2ccore/api');
		try {
			$api->setHttpClient($zendHttpClient);
		}
		catch( Exception $e ) {
			$status = false; // Shouldn't get here with a valid http client class
		}
		$this->assertInstanceOf('Zend_Http_Client', $api->getHttpClient());
		$this->assertSame($status, true);

		$this->assertSame($api->getAdapter(), $api::DEFAULT_ADAPTER);
	}

	/**
	 * Test setting http client to something silly
	 */
	public function testInvalidSetHttpClientThrowsException()
	{
		$status = false;
		$api = Mage::getModel('eb2ccore/api');
		try {
			$api->setHttpClient($api);
		}
		catch( Exception $e ) {
			$status = true; // True: you *should* throw an exception!
		}
		$this->assertSame($status, true);
	}

	/**
	 * Mock out the config helper.
	 */
	protected function _mockConfig()
	{
		$mock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get'))
			->getMock();
		$mockConfig = array(
			array('apiEnvironment', 'prod'),
			array('apiRegion', 'eu'),
			array('apiMajorVersion', '1'),
			array('apiMinorVersion', '10'),
			array('storeId', 'store-123'),
		);
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($mockConfig));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
	}
}
