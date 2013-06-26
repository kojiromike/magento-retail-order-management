<?php
/**
* sdfsdf
*/
class TrueAction_Eb2c_Tax_Test_Overrides_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
	public $className = 'TrueAction_Eb2c_Tax_Overrides_Model_Observer';
	public $quoteItem = null;
	public $observer  = null;

	public function setUp()
	{
        parent::setUp();
        $_SESSION = array();
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);
        $response = $this->getModelMock('eb2ctax/response');
        $this->responseMock = $response;
		$helper = $this->getHelperMock('tax/data', array('sendRequest'));
		$this->replaceByMock('helper', 'tax', $helper);
		$helper->expects($this->any())
			->method('sendRequest')
			->will($this->returnValue($response));
		$quoteItem = $this->getMock('Varien_Object');
		$this->quoteItem = $quoteItem;
		$listeners = array(
			'salesEventItemAdded',
			'cartEventProductUpdated',
			'salesEventItemRemoved',
			'salesEventItemQtyUpdated',
			'salesEventDiscountItem',
			'quoteCollectTotalsBefore'
		);
		$this->observerMock = $this->getMock(
			'TrueAction_Eb2c_Tax_Overrides_Model_Observer',
			$listeners
		);
		$this->replaceByMock('model', 'tax/observer', $this->observerMock);
		$this->observer = new TrueAction_Eb2c_Tax_Overrides_Model_Observer();
		$this->fetchTaxDutyInfo = new ReflectionMethod($this->observer, '_fetchTaxDutyInfo');
		$this->fetchTaxDutyInfo->setAccessible(true);
	}

	/**
	 * @test
	 */
	public function testSalesEventItemAdded()
	{
		$this->observerMock->expects($this->once())
			->method('salesEventItemAdded')
			->will($this->returnSelf());
		Mage::dispatchEvent('sales_quote_add_item', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function testCartEventProductUpdated()
	{
		$this->observerMock->expects($this->once())
			->method('cartEventProductUpdated')
			->will($this->returnSelf());
		Mage::dispatchEvent('checkout_cart_product_update_after', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function testSalesEventItemRemoved()
	{
		$this->observerMock->expects($this->once())
			->method('salesEventItemRemoved')
			->will($this->returnSelf());
		Mage::dispatchEvent('sales_quote_remove_item', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function testSalesEventItemQtyUpdated()
	{
		$this->observerMock->expects($this->once())
			->method('salesEventItemQtyUpdated')
			->will($this->returnSelf());
		Mage::dispatchEvent('sales_quote_item_qty_set_after', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function testSalesEventDiscountItem()
	{
		$this->observerMock->expects($this->once())
			->method('salesEventDiscountItem')
			->will($this->returnSelf());
		Mage::dispatchEvent('sales_quote_address_discount_item', array('quote_item' => $this->quoteItem));
	}
	/**
	 * @test
	 */
	public function testQuoteCollectTotalsBefore()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$this->observerMock->expects($this->once())
			->method('quoteCollectTotalsBefore')
			->will($this->returnSelf());
		Mage::dispatchEvent('sales_quote_collect_totals_before', array('quote' => $quote));
	}
	/**
	 * @test
	 */
	public function testFetchTaxDutyInfo()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$this->fetchTaxDutyInfo->invoke($this->observer, $quote);
		$response = Mage::helper('tax')->getCalculator()->getTaxResponse();
		$this->assertSame($this->responseMock, $response);
	}
	/**
	 * @test
	 */
	public function testFetchTaxDutyInfoEmptyQuote()
	{
		$quote = $this->getModelMock('sales/quote', array('getId'));
		$quote->expects($this->any())
			->method('getId')
			->will($this->returnValue(null));
		$this->fetchTaxDutyInfo->invoke($this->observer, $quote);
		$response = Mage::helper('tax')->getCalculator()->getTaxResponse();
	}
	/**
	 * @test
	 */
	public function testFetchTaxDutyInfoRaiseException()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$helper = $this->getHelperMock('tax/data', array('sendRequest'));
		$helper->expects($this->any())
			->method('sendRequest')
			->will(
				$this->throwException(
					new Zend_Http_Client_Exception(
						'i am death from above... below and all sides :-D'
					)
				)
			);
		$this->replaceByMock('helper', 'tax', $helper);
		$this->fetchTaxDutyInfo->invoke($this->observer, $quote);
		$response = Mage::helper('tax')->getCalculator()->getTaxResponse();
	}
}