<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Overrides_Model_CalculationTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
        parent::setUp();
        $_SESSION = array();
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);

		$taxQuote  = $this->getModelMock('eb2ctax/response_quote',
			array('getEffectiveRate', 'getCalculatedTax'));
		$taxQuote->expects($this->any())
			->method('getEffectiveRate')
			->will($this->returnValue(.5));
		$taxQuote->expects($this->any())
			->method('getCalculatedTax')
			->will($this->returnValue(0.38));
		$taxQuote2  = $this->getModelMock('eb2ctax/response_quote',
			array('getEffectiveRate', 'getCalculatedTax'));
		$taxQuote2->expects($this->any())
			->method('getEffectiveRate')
			->will($this->returnValue(0.01));
		$taxQuote2->expects($this->any())
			->method('getCalculatedTax')
			->will($this->returnValue(10.60));
		$taxQuotes = array($taxQuote, $taxQuote2);
		$orderItem = $this->getModelMock(
			'eb2ctax/response_orderitem',
			array('getTaxQuotes')
		);
		$orderItem->expects($this->any())
			->method('getTaxQuotes')
			->will($this->returnValue($taxQuotes));
		$orderItems = array($orderItem);
		$response = $this->getModelMock(
			'eb2ctax/response',
			array('getResponseForItem')
		);
		$response->expects($this->any())
			->method('getResponseForItem')
			->will($this->returnValue($orderItem));
		$this->response = $response;
		$item = $this->getModelMock('sales/quote_item', array('getSku'));
		$item->expects($this->any())
			->method('getSku')
			->will($this->returnValue('somesku'));
		$this->item = $item;
	}

	/**
	 * @test
	 */
	public function testCalcTaxForItem()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->response);
		$value = $calc->getTaxForItem($this->item);
		$this->assertSame(10.98, $value);
	}

	/**
	 * @test
	 */
	public function testCalcTaxForItemAmount()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->response);
		$value = $calc->getTaxForItemAmount(1, $this->item);
		$this->assertSame(0.51, $value);
		$value = $calc->getTaxForItemAmount(1.51, $this->item, true);
		$this->assertSame(0.51, $value);
		$value = $calc->getTaxForItemAmount(1.1, $this->item, false, false);
		$this->assertSame(0.561, $value);
	}

	/**
	 * @test
	 */
	public function testSessionStore()
	{
		$calc = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($this->response);
		$calc2 = Mage::getModel('tax/calculation');
		$this->assertNotNull($calc2->getTaxResponse());
		$this->assertSame($calc->getTaxResponse(), $calc2->getTaxResponse());
	}

	public function testGetTaxRequest()
	{
		$calc = Mage::getModel('tax/calculation');
		$request = $calc->getTaxRequest();
		$this->assertNotNull($request);
	}
}
