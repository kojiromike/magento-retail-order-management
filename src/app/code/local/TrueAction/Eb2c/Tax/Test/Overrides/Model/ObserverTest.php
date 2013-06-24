<?php
/**
* sdfsdf
*/
class TrueAction_Eb2c_Tax_Test_Overrides_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
	public $className = 'TrueAction_Eb2c_Tax_Overrides_Model_Observer';
	public $quoteItem = null;

	public function setUp()
	{
		// $request = $this->getModelMock('eb2ctax/request', null, null, array(), null, false, false);
		// $calc = $this->getModelMock('tax/calculation', array('getRateRequest'));
		// $calc->expects($this->once())
		// 	->method('getRateRequest')
		// 	->will($this->returnValue($request));
		// $helper = $this->getHelperMock('tax/data', array('getCalculator', 'sendRequest'));
		// $this->replaceByMock('helper', 'tax', $helper);
		// $helper->expects($this->any())
		// 	->method('getCalculator')
		// 	->will($this->returnValue($calc));
		// $helper->expects($this->once())
		// 	->method('sendRequest')
		// 	->will($this->returnValue('foo'));
		$quoteItem = $this->getMock(
			'Varien_Object',
			array('getQuoteItem', 'getQuote', 'getBillingAddress', 'getShippingAddress')
		);
		$quoteItem->expects($this->any())
			->method('getQuoteItem')
			->will($this->returnSelf());
		$quoteItem->expects($this->any())
			->method('getQuote')
			->will($this->returnSelf());
		$quoteItem->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnSelf());
		$quoteItem->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnSelf());
		$this->quoteItem = $quoteItem;

		$listeners = array(
			'salesEventItemAdded',
			'cartEventProductUpdated',
			'salesEventItemRemoved',
			'salesEventItemQtyUpdated',
			'salesEventDiscoutItem',
			'quoteCollectTotalsBefore'
		);
		$observer = $this->getMock(
			'TrueAction_Eb2c_Tax_Overrides_Model_Observer',
			$listeners
		);
		foreach ($listeners as $listener) {
			$observer->expects($this->any())
				->method($listener)
				->will($this->returnSelf());
		}
		$this->replaceByMock('model', 'tax/observer', $observer);
	}

	/**
	 * @test
	 */
	public function testSalesEventItemAdded()
	{
		Mage::dispatchEvent('sales_quote_add_item', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function cartEventProductUpdated()
	{
		Mage::dispatchEvent('checkout_cart_product_update_after', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function salesEventItemRemoved()
	{
		Mage::dispatchEvent('sales_quote_remove_item', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function salesEventItemQtyUpdated()
	{
		Mage::dispatchEvent('sales_quote_item_qty_set_after', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function salesEventDiscoutItem()
	{
		Mage::dispatchEvent('sales_quote_address_discount_item', array('quote_item' => $this->quoteItem));
	}
}