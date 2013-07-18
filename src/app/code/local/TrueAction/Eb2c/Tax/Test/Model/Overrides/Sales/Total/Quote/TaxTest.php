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
		// assertType is undefined.
		$this->assertSame('TrueAction_Eb2c_Tax_Overrides_Model_Sales_Total_Quote_Tax', get_class($this->tax));
		$this->calcTaxForItem = new ReflectionMethod($this->tax, '_calcTaxForItem');
		$this->calcTaxForItem->setAccessible(true);
		$this->calcTaxForAddress = new ReflectionMethod($this->tax, '_calcTaxForAddress');
		$this->calcTaxForAddress->setAccessible(true);
		$this->isFlatShipping = new ReflectionMethod($this->tax, '_isFlatShipping');
		$this->isFlatShipping->setAccessible(true);
		$this->address = new ReflectionProperty($this->tax, '_address');
		$this->address->setAccessible(true);
	}

	/**
	 * @loadFixture base.yaml
	 * @loadFixture singleShipNotBillVirt.yaml
	 * @loadFixture calcTaxBefore.yaml
	 * @loadExpectation taxtest.yaml
	 */
	public function testCalcTaxForItemBeforeDiscount()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(4);
		$responseXml = file_get_contents(substr(__FILE__, 0, -4) . '/fixtures/singleShipNotBillVirtRes.xml');
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$response = Mage::getModel('eb2ctax/response', array('xml' => $responseXml, 'request' => $request));
		Mage::helper('tax')->getCalculator()->setTaxResponse($response);
		$address = $quote->getShippingAddress();
		$items = $address->getAllNonNominalItems();
		$itemSelector = new Varien_Object(array('address' => $address));
		// precondition check
		$this->assertSame(2, count($items), 'number of items (' . count($items) . ') is not 2');
		foreach ($items as $item) {
			$expectationPath = '0-' . $item->getId();
			$e = $this->expected($expectationPath);

			$itemSelector->setItem($item);
			$this->calcTaxForItem->invoke($this->tax, $itemSelector);

			$message = "$expectationPath: tax_amount didn't match expectation";
			$this->assertEquals($e->getTaxAmount(), $item->getTaxAmount(), $message);
			$message = "$expectationPath: base_tax_amount didn't match expectation";
			$this->assertEquals($e->getBaseTaxAmount(), $item->getBaseTaxAmount(), $message);
			$message = "$expectationPath: row_total_incl_tax didn't match expectation";
			$this->assertEquals($e->getRowTotalInclTax(), $item->getRowTotalInclTax(), $message);
			$message = "$expectationPath: base_row_total_incl_tax didn't match expectation";
			$this->assertEquals($e->getBaseRowTotalInclTax(), $item->getBaseRowTotalInclTax(), $message);
		}
	}

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
