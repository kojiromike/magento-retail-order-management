<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Overrides_Model_CalculationTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @var Mage_Sales_Model_Quote (mock)
	 */
	private $quote       = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	private $shipAddress = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	private $billAddress = null;

	/**
	 * @var ReflectionProperty(TrueAction_Eb2c_Tax_Model_TaxDutyRequest::_xml)
	 */
	private $doc         = null;

	/**
	 * @var ReflectionClass(TrueAction_Eb2c_Tax_Model_TaxDutyRequest)
	 */
	private $cls         = null;

	/**
	 * path to the xsd file to validate against.
	 * @var string
	 */
	private static $xsdFile = '';

	private $tdRequest    = null;
	private $destinations = null;
	private $shipGroups   = null;

	public static function setUpBeforeClass()
	{
		self::$xsdFile = dirname(__FILE__) .
			'/CalculationTest/fixtures/TaxDutyFee-QuoteRequest-1.0.xsd';
	}

	public function setUp()
	{
        parent::setUp();
        $_SESSION = array();
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->cls = new ReflectionClass(
			'TrueAction_Eb2c_Tax_Model_TaxDutyRequest'
		);
		$this->doc = $this->cls->getProperty('_doc');
		$this->doc->setAccessible(true);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture testGetRateRequest.yaml
	 */
	public function testGetRateRequest()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$shipAddress = $quote->getShippingAddress();
		$billaddress = $quote->getBillingAddress();

		$calc = new TrueAction_Eb2c_Tax_Overrides_Model_Calculation();
		$request = $calc->getRateRequest($shipAddress, $billaddress, 'someclass', null);
		$doc = $request->getDocument();
		$xpath = new DOMXPath($doc);
		$node = $xpath->query('/TaxDutyQuoteRequest/Currency')->item(0);
		$this->assertSame('USD', $node->textContent);

		$node = $xpath->query('/TaxDutyQuoteRequest/BillingInformation')->item(0);
		$this->assertSame('4', $node->getAttribute('ref'));
		$parent = $xpath->query('/TaxDutyQuoteRequest/Shipping/Destinations/MailingAddress')->item(0);
		$this->assertSame('4', $parent->getAttribute('id'));

		// check the PersonName
		$node = $xpath->query('PersonName/LastName', $parent)->item(0);
		$this->assertSame('Guy', $node->textContent);
		$node = $xpath->query('PersonName/FirstName', $parent)->item(0);
		$this->assertSame('Test', $node->textContent);
		$node = $xpath->query('PersonName/Honorific', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('PersonName/MiddleName', $parent)->item(0);
		$this->assertNull($node);

		// verify the AddressNode
		$node = $xpath->query('Address/Line1', $parent)->item(0);
		$this->assertSame('1 RoseDale st', $node->textContent);
		$node = $xpath->query('Address/Line2', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('Address/Line3', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('Address/Line4', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('Address/City', $parent)->item(0);
		$this->assertSame('BaltImore', $node->textContent);
		$node = $xpath->query('Address/MainDivision', $parent)->item(0);
		$this->assertSame('MD', $node->textContent);
		$node = $xpath->query('Address/CountryCode', $parent)->item(0);
		$this->assertSame('US', $node->textContent);
		$node = $xpath->query('Address/PostalCode', $parent)->item(0);
		$this->assertSame('21229', $node->textContent);

		// verify the email address
		$parent = $xpath->query('/TaxDutyQuoteRequest/Shipping/Destinations')->item(0);
		$node = $xpath->query('Email', $parent)->item(0);
		$this->assertSame('foo@example.com', $node->getAttribute('id'));

		$node = $xpath->query('Email/EmailAddress', $parent)->item(0);
		$this->assertSame('foo@example.com', $node->textContent);
		print $doc->saveXML();
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture testGetRateRequest.yaml
	 */
	public function testGetRateRequestXsd()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$shipAddress = $quote->getShippingAddress();
		$billaddress = $quote->getBillingAddress();

		$calc = new TrueAction_Eb2c_Tax_Overrides_Model_Calculation();
		$request = $calc->getRateRequest($shipAddress, $billaddress, 'someclass', null);
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}
}
