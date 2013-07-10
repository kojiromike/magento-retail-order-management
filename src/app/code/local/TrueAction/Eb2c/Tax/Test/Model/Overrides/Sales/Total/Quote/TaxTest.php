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

	public function testCollect()
	{
		$this->markTestIncomplete('need to add some assertions');
		$productMock = $this->getModelMock('catalog/product', array('isVirtual'));
		$productMock->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));

		$itemMock = $this->getModelMock('sales/quote_item', array('getQty', 'getCalculationPriceOriginal', 'getStore', 'getId', 'getProduct', 'getHasChildren', 'isChildrenCalculated', 'getChildren'));
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));
		$itemMock->expects($this->any())
			->method('getHasChildren')
			->will($this->returnValue(true));
		$itemMock->expects($this->any())
			->method('getCalculationPriceOriginal')
			->will($this->returnValue(100));
		$itemMock->expects($this->any())
			->method('isChildrenCalculated')
			->will($this->returnValue(true));
		$itemMock->expects($this->any())
			->method('getChildren')
			->will($this->returnValue(array($itemMock)));
		$itemMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1));

		$addressMock = $this->getModelMock('sales/quote_address', array('getQuote', 'getAllNonNominalItems', 'getId'));
		$addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$addressMock->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue(array($itemMock)));

		$quoteMock = $this->getModelMock('sales/quote', array('getStore', 'getId', 'getItemsCount', 'getBillingAddress', 'getAllVisibleItems'));
		$quoteMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$quoteMock->expects($this->any())
			->method('getItemsCount')
			->will($this->returnValue(1));
		$quoteMock->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue($addressMock));
		$quoteMock->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($itemMock)));
		$quoteMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));

		$addressMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$tax = Mage::getModel('tax/sales_total_quote_tax');
		$tax->collect($addressMock);
		// assert the item->getTaxAmount is as expected.
	}

	public function testCalcRowTaxAmount()
	{
		$this->markTestIncomplete('Missing fixture – items array is empty.');
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$items = $quote->getShippingAddress()->getAllVisibleItems();
		$item = $items[0];
		$this->calcRowTaxAmount->invoke($this->tax, $item);
		$this->assertEquals(0.0, $item->getTaxPercent());
	}

	public function testCollectWithDiscounts()
	{
		// need to create coupon code scenario
		// assert tax amount is altered by discount amount in case where discount calc'd after tax.
		$this->markTestIncomplete();
	}
}
