<?php
class TrueAction_Eb2cTax_Test_Model_Overrides_Sales_Total_Quote_TaxTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
		parent::setUp();
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);

		$this->tax = Mage::getModel('tax/sales_total_quote_tax');
		// assertType is undefined.
		$this->assertSame('TrueAction_Eb2cTax_Overrides_Model_Sales_Total_Quote_Tax', get_class($this->tax));
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
		$this->markTestIncomplete('broke due to changes.');
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


	/**
	 * @loadFixture base.yaml
	 * @loadFixture singleShipNotBillVirt.yaml
	 * @loadFixture calcTaxBefore.yaml
	 * @loadExpectation taxtest.yaml
	 */
	public function testCalcTaxForAddress()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(4);
		$responseXml = file_get_contents(substr(__FILE__, 0, -4) . '/fixtures/singleShipNotBillVirtRes.xml');
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$response = Mage::getModel('eb2ctax/response', array('xml' => $responseXml, 'request' => $request));
		Mage::helper('tax')->getCalculator()->setTaxResponse($response);
		$address = $quote->getShippingAddress();
		// set the total_abstract::_address member or else will get a
		// 'Address model is not defined.' exception thrown.
		$this->address->setValue($this->tax, $address);
		$this->calcTaxForAddress->invoke($this->tax, $address);
	}

	public function testIsFlatShipping()
	{
		$addressMock = $this->getModelMock('sales/quote_address', array('getShippingMethod'));
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('flatrate_flatrate'));
		$this->assertTrue($this->isFlatShipping->invoke($this->tax, $addressMock));
	}

	public function testIsFlatShippingIsFalse()
	{
		$addressMock = $this->getModelMock('sales/quote_address', array('getShippingMethod'));
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('someother_shippingrate'));
		$this->assertFalse($this->isFlatShipping->invoke($this->tax, $addressMock));
	}

	public function testCollectChildItem()
	{
		$this->markTestIncomplete('broke due to changes');
		$this->_mockCalculator();
		$productMock = $this->getModelMock('catalog/product', array('isVirtual'));
		$productMock->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));

		$addressMock = $this->getModelMock('sales/quote_address', array('getQuote', 'getAllNonNominalItems', 'getId'));
		$addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$itemMock = $this->_mockParentItem();
		$addressMock->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue(array($itemMock, $this->_mockParentItem())));

		$quoteMock = $this->getModelMock('sales/quote', array('getStore', 'getId', 'getItemsCount', 'getBillingAddress', 'getAllVisibleItems'));
		$quoteMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));

		$addressMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));
		$calc = Mage::helper('tax')->getCalculator();
		/// make sure the mock is setup
		$this->assertSame(10, $calc->getTax(new Varien_Object(array('item' => $itemMock, 'address' => $addressMock))));
		$tax = Mage::getModel('tax/sales_total_quote_tax');
		$tax->collect($addressMock);
		// assert the item->getTaxAmount is as expected.
	}

	public function testCollectNoItems()
	{
		// $this->markTestIncomplete('need to add some assertions');
		$addressMock = $this->getModelMock('sales/quote_address', array('getQuote', 'getAllNonNominalItems', 'getId'));
		$addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$addressMock->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue(array()));

		$quoteMock = $this->getModelMock('sales/quote', array('getStore', 'getId', 'getItemsCount', 'getBillingAddress', 'getAllVisibleItems'));
		$quoteMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));

		$addressMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$tax = Mage::getModel('tax/sales_total_quote_tax');
		$tax->collect($addressMock);
	}

	protected function _mockCalculator()
	{
		$calcMock = $this->getModelMock('tax/calculation', array('getAppliedRates', 'getTax', 'getTaxForAmount'));
		$calcMock->expects($this->any())
			->method('getTax')->will($this->returnValue(8.25));
		$calcMock->expects($this->any())
			->method('getTaxForAmount')->will($this->returnValue(8.25));
		$calcMock->expects($this->any())
			->method('getAppliedRates')->will($this->returnValue(array(
				'jurisdiction-imposition-rate' => array(
					'percent'     => 8.25,
					'id'          => 'jurisdiction-imposition-rate',
					'process'     => 0,
					'amount'      => 8.25,
					'base_amount' => 8.25,
					'rates' => array(
						0 => array(
							'code'     => 'jurisdiction-imposition',
							'title'    => 'jurisdiction-imposition',
							'position' => '1',
							'priority' => '1',
						),
					),
				),
			))
		);
		$this->replaceByMock('singleton', 'tax/calculation', $calcMock);
		$this->replaceByMock('model', 'tax/calculation', $calcMock);
	}


	protected function _mockParentItem()
	{
		$productMock = $this->getModelMock('catalog/product', array('isVirtual'));

		$methods = array('getParentItem', 'getQty', 'getCalculationPriceOriginal', 'getStore', 'getId', 'getProduct', 'getHasChildren', 'isChildrenCalculated', 'getChildren');
		$itemMock = $this->getModelMock('sales/quote_item', $methods);
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
		return $itemMock;
	}

	protected function _mockItem()
	{
		$productMock = $this->getModelMock('catalog/product', array('isVirtual'));

		$methods = array('getParentItem', 'getQty', 'getCalculationPriceOriginal', 'getStore', 'getId', 'getProduct', 'getHasChildren', 'isChildrenCalculated', 'getChildren');
		$itemMock = $this->getModelMock('sales/quote_item', $methods);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));
		$itemMock->expects($this->any())
			->method('getHasChildren')
			->will($this->returnValue(false));
		$itemMock->expects($this->any())
			->method('getCalculationPriceOriginal')
			->will($this->returnValue(100));
		$itemMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1));
		return $itemMock;
	}

	protected function _mockChildItem()
	{
		$productMock = $this->getModelMock('catalog/product', array('isVirtual'));

		$methods = array('getParentItem', 'getQty', 'getCalculationPriceOriginal', 'getStore', 'getId', 'getProduct', 'getHasChildren', 'isChildrenCalculated', 'getChildren');
		$itemMock = $this->getModelMock('sales/quote_item', $methods);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$itemMock->expects($this->any())
			->method('getParentItem')
			->will($this->returnSelf());
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));
		$itemMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));
		return $itemMock;
	}
}
