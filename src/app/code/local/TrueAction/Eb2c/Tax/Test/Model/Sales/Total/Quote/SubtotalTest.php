<?php
class TrueAction_Eb2c_Tax_Test_Model_Sales_Total_Quote_SubtotalTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
        parent::setUp();
		$cookieMock = $this->getModelMockBuilder('core/cookie')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(array('set')) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$cookieMock->expects($this->any())
			->method('set')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'core/cookie', $cookieMock);

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
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @loadExpectation testApplyTaxes.yaml
	 */
	public function testApplyTaxes()
	{
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$items = $quote->getShippingAddress()->getAllNonNominalItems();
		foreach ($items as $item) {
			$this->applyTaxes->invoke($this->subtotal, $item);
			$exp = $this->expected('1-' . $item->getSku());
			$this->assertSame($exp->getTaxPercent(), $item->getTaxPercent());
			$this->assertSame($exp->getPriceInclTax(), $item->getPriceInclTax());
			$this->assertSame($exp->getRowTotalInclTax(), $item->getRowTotalInclTax());
			$this->assertSame($exp->getTaxableAmount(), $item->getTaxableAmount());
			$this->assertSame($exp->getIsPriceInclTax(), $item->getIsPriceInclTax());
		}
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBillingBundle.yaml
	 */
	public function testApplyTaxesWithBundle()
	{
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$items = $quote->getShippingAddress()->getAllNonNominalItems();
		// foreach ($items as $item) {
		// 	$this->applyTaxes->invoke($this->subtotal, $item);
		// 	$exp = $this->expected('1-' . $item->getSku());
		// 	$this->assertSame($exp->getTaxPercent(), $item->getTaxPercent());
		// 	$this->assertSame($exp->getPriceInclTax(), $item->getPriceInclTax());
		// 	$this->assertSame($exp->getRowTotalInclTax(), $item->getRowTotalInclTax());
		// 	$this->assertSame($exp->getTaxableAmount(), $item->getTaxableAmount());
		// 	$this->assertSame($exp->getIsPriceInclTax(), $item->getIsPriceInclTax());
		// }
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testCollect()
	{
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$address = $quote->getShippingAddress();
		$this->subtotal->collect($address);
	}
}
