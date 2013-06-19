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
        parent::setUp();
        $_SESSION = array();
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture testGetRateRequest.yaml
	 */
	public function testConstruction()
	{
		$this->markTestSkipped('not done yet');
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$this->request = new TrueAction_Eb2c_Tax_Model_Request(array(
			'quote' => $quote
		));
		$response = new TrueAction_Eb2c_Tax_Model_Response(array(
			'xml' => self::$_xml,
			'request' => $this->request
		));
		$this->assertSame(2, count($this->itemResults->getValue($response)));
	}
}
