<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Model_ResponseTest extends EcomDev_PHPUnit_Test_Case
{
	public static $_xml = '';
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
		self::$_xml = fread($file, filesize($path));
		fclose($file);
		$path = dirname(__FILE__) . '/ResponseTest/fixtures/request.xml';
		$file = fopen($path, 'r');
		self::$reqXml = fread($file, filesize($path));
	}

	public function setUp()
	{
		$this->itemResults = self::$cls->getProperty('_itemResults');
		$this->itemResults->setAccessible(true);
		$reqDoc = new TrueAction_Dom_Document('1.0', 'utf8');
		$reqDoc->loadXML($requestXml);
		$request = $this->getModelMock(
			'TrueAction_Eb2c_Tax_Model_TaxDutyRequest',
			array('getDocument')
		);
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue($reqDoc));
		$this->request = $request;
	}

	/**
	 * @test
	 */
	public function testConstruction()
	{
		$response = new TrueAction_Eb2c_Tax_Model_Response(array(
			'xml' => self::$_xml,
			'request' => $this->request
		));
		$this->assertSame(2, count($this->itemResults->getValue($response)));
	}
}
