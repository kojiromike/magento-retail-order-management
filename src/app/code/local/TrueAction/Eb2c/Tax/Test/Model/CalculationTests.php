<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Model_CalculationTests extends EcomDev_PHPUnit_Test_Case
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


	private $tdRequest    = null;
	private $destinations = null;
	private $shipGroups   = null;

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

		$calc = new TrueAction_Eb2c_Tax_Model_Calculation();
		$request = $calc->getRateRequest($shipAddress, $billaddress, 'someclass', null);
		$doc = $this->doc->getValue($request);
		$xpath = new DOMXPath($doc);
		$node = $xpath->query('//TaxDutyRequest/Currency')->item(0);
		$this->assertSame('USD', $node->textContent);

		$node = $xpath->query('//TaxDutyRequest/BillingInformation')->item(0);
		$this->assertSame('3', $node->getAttribute('ref'));

	}
}
