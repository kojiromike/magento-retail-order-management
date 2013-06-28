<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Model_RequestTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @var Mage_Sales_Model_Quote (mock)
	 */
	public $quote          = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $shipAddress    = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $billAddress    = null;

	/**
	 * @var ReflectionProperty(TrueAction_Eb2c_Tax_Model_Request::_xml)
	 */
	public $doc            = null;

	/**
	 * @var ReflectionClass(TrueAction_Eb2c_Tax_Model_Request)
	 */
	public static $cls     = null;

	/**
	 * path to the xsd file to validate against.
	 * @var string
	 */
	public static $xsdFile = '';

	public $tdRequest      = null;
	public $destinations   = null;
	public $shipGroups     = null;

	public static function setUpBeforeClass()
	{
		self::$xsdFile = dirname(__FILE__) .
			'/RequestTest/fixtures/TaxDutyFee-QuoteRequest-1.0.xsd';
		self::$cls = new ReflectionClass(
			'TrueAction_Eb2c_Tax_Model_Request'
		);
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
	 */
	public function testIsValid()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$req   = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($req->isValid());
		$req   = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$req->unsBillingAddress();
		$this->assertFalse($req->isValid());
		$req   = Mage::getModel('eb2ctax/request');
		$this->assertFalse($req->isValid());
	}

	/**
	 * @test
	 */
	public function testValidateWithXsd()
	{
		$this->markTestIncomplete('need to fix namespaces in nodes');
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}
}
