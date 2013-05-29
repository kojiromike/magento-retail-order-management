<?php
class TrueAction_Eb2c_Tax_Test_Model_CalculationTests extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
		$this->quote       = $this->getModelMock('sales/quote', array('getCurrencyCode'))
			->expects($this->any())
			->method('getCurrencyCode')
			->will($this->returnValue('USD'));
		$this->shipAddress = $this->getModelMock('sales/quote_address', array('getQuote'))
			->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($this->quote));
		$this->billAddress = $this->getModelMock('sales/quote_address', array('getId'))
			->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$this->cls = ReflectionClass('TrueAction_Eb2c_Tax_Model_TaxDutyRequest');
		$this->xml = $this->cls->getProperty('_xml');
		$this->xml->setAccessible(true);
	}

	/**
	 * @test
	 * */
	public function testGetRateRequest()
	{
		print 'this test is being run';
		$calc = new TrueAction_Eb2c_Tax_Model_Calculation();
		$request = $calc->getRateRequest(
			$this->shipAddress,
			$this->billAddress,
			'someclass',
			null
		);
		$xml = $this->xml->getValue($request);
		$this->assertTrue(isset($xml->BillingInformation));
		$this->assertTrue(isset($xml->Shipping));
		$this->assertTrue(isset($xml->Shipping->ShipGroups));
	}
}
