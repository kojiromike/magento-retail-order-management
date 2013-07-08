<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Model_ResponseTest extends EcomDev_PHPUnit_Test_Case
{
	public static $respXml = '';
	public static $cls;
	public static $reqXml;
	public $itemResults;
	public $request;

	public static function setUpBeforeClass()
	{
		self::$cls = new ReflectionClass(
			'TrueAction_Eb2c_Tax_Model_Response'
		);
		$path = dirname(__FILE__) . '/ResponseTest/fixtures/response.xml';
		$file = fopen($path, 'r');
		self::$respXml = fread($file, filesize($path));
		fclose($file);
		$path = dirname(__FILE__) . '/ResponseTest/fixtures/request.xml';
		$file = fopen($path, 'r');
		self::$reqXml = fread($file, filesize($path));
	}

	public function setUp()
	{
		parent::setUp();
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$this->request = Mage::getModel('eb2ctax/request', array(
			'quote' => $quote
		));

		$docObject = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docObject->loadXML(self::$reqXml);
		$requestReflector = new ReflectionObject($this->request);
		$doc = $requestReflector->getProperty('_doc');
		$doc->setAccessible(true);
		$doc->setValue($this->request, $docObject);
	}

	/**
	 * Testing getResponseForItem method
	 *
	 * @test
	 */
	public function testGetResponseForItem()
	{
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$item = $this->getModelMock('sales/quote_item', array('getSku'));
		$item->expects($this->any())
			->method('getSku')
			->will($this->returnValue('gc_virtual1'));
		$responseItem = $response->getResponseForItem($item);
		$this->assertNotNull($responseItem);
		$this->assertSame(3, count($responseItem->getTaxQuotes()));
	}

	/**
	 * Testing getResponseItems method
	 *
	 * @test
	 */
	public function testGetResponseItems()
	{
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$this->assertNotNull(
			$response->getResponseItems()
		);
	}

	/**
	 * Testing isValid method
	 *
	 * @test
	 */
	public function testIsValid()
	{
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$this->assertSame(
			false,
			$response->isValid()
		);
	}

	/**
	 * Testing _construct method - valid request/response match xml
	 *
	 * @test
	 */
	public function testConstructValidRequestResponseMatch()
	{
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$addressMock = $this->getModelMock('sales/quote_address', array('getId'));
		$addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));

		$responseReflector = new ReflectionObject($response);
		$addressProperty = $responseReflector->getProperty('_address');
		$addressProperty->setAccessible(true);
		$addressProperty->setValue($response, $addressMock);

		$constructMethod = $responseReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertNull(
			$constructMethod->invoke($response)
		);
	}

	/**
	 * Testing _construct method - invalid request/response match xml
	 *
	 * @test
	 */
	public function testConstructInvalidRequestResponseMatch()
	{
		$docObject = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docObject->loadXML(file_get_contents(dirname(__FILE__) . '/ResponseTest/fixtures/request-invalid.xml'));
		$requestReflector = new ReflectionObject($this->request);
		$doc = $requestReflector->getProperty('_doc');
		$doc->setAccessible(true);
		$doc->setValue($this->request, $docObject);

		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$responseReflector = new ReflectionObject($response);
		$constructMethod = $responseReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertNull(
			$constructMethod->invoke($response)
		);
	}

	/**
	 * Testing _construct method - invalid request/response match xml because of MailingAddress[@id="2"] element is different
	 *
	 * @test
	 */
	public function testConstructMailingAddressMisMatch()
	{
		$docObject = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docObject->loadXML(file_get_contents(dirname(__FILE__) . '/ResponseTest/fixtures/request-invalid-2.xml'));
		$requestReflector = new ReflectionObject($this->request);
		$doc = $requestReflector->getProperty('_doc');
		$doc->setAccessible(true);
		$doc->setValue($this->request, $docObject);

		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$responseReflector = new ReflectionObject($response);
		$constructMethod = $responseReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertNull(
			$constructMethod->invoke($response)
		);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBilling.yaml
	 */
	public function testItemSplitAccrossShipgroups()
	{
		$xmlPath = dirname(__FILE__) . '/ResponseTest/fixtures/responseSplitAcrossShipGroups.xml';
		$docObject = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docObject->loadXML();
		$response = Mage::getModel(
			'eb2ctax/response',
			array(
				'xml' => file_get_contents($xmlPath)
			)
		);

		$addressMock1 = $this->getModelMock('sales/quote_address');
		$addressMock1->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$addressMock1->expects($this->any())
			->method('getShippingRate')
			->will($this->returnValue('flatrate_flatrate'));
		$addressMock2 = $this->getModelMock('sales/quote_address');
		$addressMock2->expects($this->any())
			->method('getId')
			->will($this->returnValue(2));
		$addressMock2->expects($this->any())
			->method('getShippingRate')
			->will($this->returnValue('flatrate_flatrate'));

		$itemMock = $this->getModelMock('sales/quote_item');
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('gc_virtual1'));

		$itemResponse = $response->getResponseForItem($itemMock, $addressMock1);
		$this->assertSame(1, $itemResponse->getQantity());
		$itemResponse = $response->getResponseForItem($itemMock, $addressMock2);
		$this->assertSame(2, $itemResponse->getQantity());
	}
}
