<?php
class TrueAction_Eb2c_Tax_Test_Model_Overrides_Sales_Total_Quote_TaxTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
        parent::setUp();
        $_SESSION = array();
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);

        $this->tax = Mage::getModel('tax/sales_total_quote_tax');
        $this->calcRowTaxAmount = new ReflectionMethod($this->tax, '_calcRowTaxAmount');
        $this->calcRowTaxAmount->setAccessible(true);
		$taxQuote  = $this->getModelMock('eb2ctax/response_quote',
			array('getEffectiveRate', 'getCalculatedTax'));
		$taxQuote->expects($this->any())
			->method('getEffectiveRate')
			->will($this->returnValue(1.5));
		$taxQuote->expects($this->any())
			->method('getCalculatedTax')
			->will($this->returnValue(0.38));
		$taxQuotes = array($taxQuote);
		$orderItem = $this->getModelMock('eb2ctax/response_orderitem', array('getTaxQuotes'));
		$orderItem->expects($this->any())
			->method('getTaxQuotes')
			->will($this->returnValue($taxQuotes));
		$orderItems = array($orderItem);
		$response = $this->getModelMock('eb2ctax/response', array('getResponseForItem'));
		$response->expects($this->any())
			->method('getResponseForItem')
			->will($this->returnValue($orderItem));
		Mage::helper('tax')->getCalculator()->setTaxResponse($response);
	}

	/**
	 * @test
	 */
	public function testCalcRowTaxAmount()
	{
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$items = $quote->getShippingAddress()->getAllVisibleItems();
		$item = $items[0];
		$this->calcRowTaxAmount->invoke($this->tax, $item);
		$this->assertEquals(0.0, $item->getTaxPercent());
	}
}
