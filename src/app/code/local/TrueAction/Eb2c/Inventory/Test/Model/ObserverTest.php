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
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_observer = $this->_getObserver();
	}

	/**
	 * Get Observer instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Observer
	 */
	protected function _getObserver()
	{
		if (!$this->_observer) {
			$this->_observer = Mage::getModel('eb2cinventory/observer');
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
	 * testing quantity observer
	 *
	 * @test
	 * @dataProvider providerCheckEb2cInventoryQuantity
	 */
	public function testCheckEb2cInventoryQuantity($observer)
	{
		// Testing when quantity check is all successful
		$this->assertNull(
			$this->_getObserver()->checkEb2cInventoryQuantity($observer)
		);
	}

	/**
	 * testing when eb2c quantity check is out of stock
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 * @dataProvider providerCheckEb2cInventoryQuantity
	 */
	public function testCheckEb2cInventoryQuantityOutOfStock($observer)
	{
		// testing when available stock is less, than what shopper requested.
		$quantityMock = $this->getMock('TrueAction_Eb2c_Inventory_Model_Quantity', array('requestQuantity'));
		$quantityMock->expects($this->any())
			->method('requestQuantity')
			->will($this->returnValue(0)
			);
		$quantityReflector = new ReflectionObject($this->_getObserver());
		$quantity = $quantityReflector->getProperty('_quantity');
		$quantity->setAccessible(true);
		$quantity->setValue($this->_getObserver(), $quantityMock);

		$this->assertNull(
			$this->_getObserver()->checkEb2cInventoryQuantity($observer)
		);
	}

	/**
	 * testing when eb2c quantity check is less than what shopper requested
	 *
	 * @test
	 * @dataProvider providerCheckEb2cInventoryQuantity
	 */
	public function testCheckEb2cInventoryQuantityLessThanRequested($observer)
	{
		// testing when available stock is less, than what shopper requested.
		$quantityMock = $this->getMock('TrueAction_Eb2c_Inventory_Model_Quantity', array('requestQuantity'));
		$quantityMock->expects($this->any())
			->method('requestQuantity')
			->will($this->returnValue(0.5)
			);
		$quantityReflector = new ReflectionObject($this->_getObserver());
		$quantity = $quantityReflector->getProperty('_quantity');
		$quantity->setAccessible(true);
		$quantity->setValue($this->_getObserver(), $quantityMock);

		$this->assertNull(
			$this->_getObserver()->checkEb2cInventoryQuantity($observer)
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

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getAllItems', 'getShippingAddress', 'getItemById'));
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($addressMock)
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$eventMock = $this->getMock('Varien_Event', array('getQuote'));
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock('Varien_Event', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing inventory detail observer
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

	public function providerProcessEb2cAllocation()
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

		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getId', 'getSku', 'save'));
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
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getAllItems', 'getShippingAddress', 'getItemById'));
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($addressMock)
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$eventMock = $this->getMock('Varien_Event', array('getQuote'));
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock('Varien_Event', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing processing allocation observer
	 *
	 * @test
	 * @dataProvider providerProcessEb2cAllocation
	 */
	public function testProcessEb2cAllocation($observer)
	{
		// testing when you can allocated inventory
		$this->assertNull(
			$this->_getObserver()->processEb2cAllocation($observer)
		);
	}

	/**
	 * testing when a quantity can no be allocated for a quote
	 *
	 * @test
	 * @expectedException TrueAction_Eb2c_Inventory_Model_Allocation_Exception
	 * @dataProvider providerProcessEb2cAllocation
	 */
	public function testProcessEb2cAllocationError($observer)
	{
		// testing when allocation error occurred.
		$allocationMock = $this->getMock('TrueAction_Eb2c_Inventory_Model_Allocation', array('processAllocation'));
		$allocationMock->expects($this->any())
			->method('processAllocation')
			->will($this->returnValue(array(array('Sorry, item "2610" out of stock.')))
			);
		$allocationReflector = new ReflectionObject($this->_getObserver());
		$allocation = $allocationReflector->getProperty('_allocation');
		$allocation->setAccessible(true);
		$allocation->setValue($this->_getObserver(), $allocationMock);

		$this->assertNull(
			$this->_getObserver()->processEb2cAllocation($observer)
		);
	}
}
