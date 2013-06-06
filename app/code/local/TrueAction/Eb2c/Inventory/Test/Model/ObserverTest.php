<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_observer;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_observer = $this->_getObserver();
		Mage::app()->getConfig()->reinit(); // re-initialize config to get fresh loaded data
	}

	/**
	 * Get Observer instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Observer
	 */
	protected function _getObserver()
	{
		if (!$this->_observer) {
			$this->_observer = Mage::getModel('eb2c_inventory/observer');
		}
		return $this->_observer;
	}

	public function providerCheckEb2cInventoryQuantity()
	{
		$quoteAMock = $this->getMock('Mage_Sales_Model_Quote', array('collectTotals', 'save', 'deleteItem'));
		$quoteAMock->expects($this->any())
			->method('collectTotals')
			->will($this->returnValue(1)
			);
		$quoteAMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteAMock->expects($this->any())
			->method('deleteItem')
			->will($this->returnValue(1)
			);

		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getProductId', 'getSku', 'getQuote'));
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getProductId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteAMock)
			);

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getItem'));
		$quoteMock->expects($this->any())
			->method('getItem')
			->will($this->returnValue($itemMock)
			);

		$observerMock = $this->getMock('TrueAction_Eb2c_Inventory_Model_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($quoteMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing check
	 *
	 * @test
	 * @dataProvider providerCheckEb2cInventoryQuantity
	 */
	public function testCheckEb2cInventoryQuantity($observer)
	{
		$this->assertNull(
			$this->_getObserver()->checkEb2cInventoryQuantity($observer)
		);
	}

	public function providerCheckEb2cInventoryQtyAddNew()
	{
		$productMock = $this->getMock('Mage_Catalog_Model_Product', array('getQty', 'getId', 'getSku'));
		$productMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$productMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);

		$quoteAMock = $this->getMock('Mage_Sales_Model_Quote', array('collectTotals', 'save', 'deleteItem'));
		$quoteAMock->expects($this->any())
			->method('collectTotals')
			->will($this->returnValue(1)
			);
		$quoteAMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteAMock->expects($this->any())
			->method('deleteItem')
			->will($this->returnValue(1)
			);

		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getProductId', 'getSku', 'getQuote', 'getProduct'));
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getProductId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteAMock)
			);
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock)
			);

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getQuoteItem'));
		$quoteMock->expects($this->any())
			->method('getQuoteItem')
			->will($this->returnValue($itemMock)
			);

		$observerMock = $this->getMock('TrueAction_Eb2c_Inventory_Model_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($quoteMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing check
	 *
	 * @test
	 * @dataProvider providerCheckEb2cInventoryQtyAddNew
	 */
	public function testCheckEb2cInventoryQtyAddNew($observer)
	{
		$this->assertNull(
			$this->_getObserver()->checkEb2cInventoryQtyAddNew($observer)
		);
	}

	public function providerProcessInventoryDetails()
	{
		$addressMock = $this->getMock('Mage_Sales_Model_Quote_Address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode')
		);
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('USPS: 3 Day Select')
			);
		$addressMock->expects($this->any())
			->method('getStreet')
			->will($this->returnValue(array('1938 Some Street'))
			);
		$addressMock->expects($this->any())
			->method('getCity')
			->will($this->returnValue('King of Prussia')
			);
		$addressMock->expects($this->any())
			->method('getRegion')
			->will($this->returnValue('Pennsylvania')
			);
		$addressMock->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue('US')
			);
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue('19726')
			);

		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getId', 'getSku'));
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getAllItems', 'getShippingAddress'));
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($addressMock)
			);

		$observerMock = $this->getMock('TrueAction_Eb2c_Inventory_Model_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($quoteMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing check
	 *
	 * @test
	 * @dataProvider providerProcessInventoryDetails
	 */
	public function testProcessInventoryDetails($observer)
	{
		$this->assertNull(
			$this->_getObserver()->processInventoryDetails($observer)
		);
	}
}
