<?php
class TrueAction_Eb2c_Tax_Test_Model_Sales_Total_Quote_SubtotalTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
        parent::setUp();
        $_SESSION = array();
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);

        $this->subtotal = Mage::getModel('tax/sales_total_quote_subtotal');
        $this->applyTaxes = new ReflectionMethod($this->subtotal, '_applyTaxes');
        $this->applyTaxes->setAccessible(true);
		$taxQuote  = $this->getModelMock('eb2ctax/response_quote',
			array('getEffectiveRate', 'getCalculatedTax'));
		$taxQuote->expects($this->any())
			->method('getEffectiveRate')
			->will($this->returnValue(1.5));
		$taxQuote->expects($this->any())
			->method('getCalculatedTax')
			->will($this->returnValue(0.38));
		$taxQuotes = array($taxQuote);
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
		Mage::helper('tax')->getCalculator()
			->setTaxResponse($response);
	}
	/**
	 * @test
	 */
	public function testApplyTaxes()
	{
		$this->markTestIncomplete('need to update numbers to match data change');
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$items = $quote->getShippingAddress()->getAllVisibleItems();
		$item = $items[0];
		$this->applyTaxes->invoke($this->subtotal, $item);

		$this->assertEquals(0.0, $item->getTaxPercent());
		$this->assertSame(25.38, $item->getPriceInclTax());
		$this->assertSame(25.38, $item->rowTotalInclTax());
		$this->assertSame(25.00, $item->getTaxableAmount());
		$this->assertSame(0, $item->getIsPriceInclTax());
	}

	/**
	 * @test
	 */
	public function testCollect()
	{
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$address = $quote->getShippingAddress();
		$this->subtotal->collect($address);
	}
}
