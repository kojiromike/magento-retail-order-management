<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Model_ResponseTest extends EcomDev_PHPUnit_Test_Case
{
	public static $_xml = '';
	public static $cls;
	public $itemResults;

	public static function setUpBeforeClass()
	{
		self::$cls = new ReflectionClass(
			'TrueAction_Eb2c_Tax_Model_Response'
		);
		$responseFile = dirname(__FILE__) .
			'/ResponseTest/fixtures/response.xml';
		$file = fopen($responseFile, 'r');
		self::$_xml = fread($file, filesize($responseFile));
		fclose($file);
	}

	public function setUp()
	{
		$this->itemResults = self::$cls->getProperty('_itemResults');
		$this->itemResults->setAccessible(true);
	}

	/**
	 * @test
	 */
	public function runTests()
	{
		$response = new TrueAction_Eb2c_Tax_Model_Response(array(
			'xml' => self::$_xml
		));
		$this->assertSame(2, count($this->itemResults->getValue($response)));
	}
}
