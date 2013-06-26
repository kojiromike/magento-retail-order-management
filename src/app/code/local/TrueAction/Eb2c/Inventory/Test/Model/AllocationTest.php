<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Model_AllocationTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_allocation;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_allocation = $this->_getAllocation();
	}

	/**
	 * Get Allocation instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Allocation
	 */
	protected function _getAllocation()
	{
		if (!$this->_allocation) {
			$this->_allocation = Mage::getModel('eb2cinventory/allocation');
		}
		return $this->_allocation;
	}

	public function buildQuoteMock()
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
			->will($this->returnValue('1938 Some Street')
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

		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getId', 'getSku', 'getItemId', 'getQuote', 'save'));
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
			->method('getItemId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getAllItems', 'getShippingAddress', 'getItemById', 'save', 'getAllAddresses'));

		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock)
			);

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
		$quoteMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($quoteMock))
			);

		return $quoteMock;
	}

	public function providerAllocateQuoteItems()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing allocating quote items
	 *
	 * @test
	 * @dataProvider providerAllocateQuoteItems
	 */
	public function testAllocateQuoteItems($quote)
	{
		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_getAllocation()->allocateQuoteItems($quote)
		);
	}

	/**
	 * testing when allocating quote item API call throw an exception
	 *
	 * @test
	 * @dataProvider providerAllocateQuoteItems
	 */
	public function testAllocateQuoteItemsWithApiCallException($quote)
	{
		$coreHelperMock = $this->getMock('TrueAction_Eb2c_Core_Helper_Data', array('callApi'));
		$coreHelperMock->expects($this->any())
			->method('callApi')
			->will(
				$this->throwException(new Exception)
			);

		$inventoryHelper = Mage::helper('eb2cinventory');
		$inventoryReflector = new ReflectionObject($inventoryHelper);
		$coreHelper = $inventoryReflector->getProperty('coreHelper');
		$coreHelper->setAccessible(true);
		$coreHelper->setValue($inventoryHelper, $coreHelperMock);

		$allocationReflector = new ReflectionObject($this->_getAllocation());
		$helper = $allocationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_getAllocation(), $inventoryHelper);

		$this->assertSame(
			'',
			trim($this->_getAllocation()->allocateQuoteItems($quote))
		);
	}

	public function providerBuildAllocationRequestMessage()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing building inventory details request message
	 *
	 * @test
	 * @dataProvider providerBuildAllocationRequestMessage
	 */
	public function testBuildAllocationRequestMessage($quote)
	{
		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_getAllocation()->buildAllocationRequestMessage($quote)
		);
	}

	public function providerBuildAllocationRequestMessageWithException()
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
			->will($this->returnValue('1938 Some Street')
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

		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getId', 'getSku', 'getItemId'));
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
			->will($this->returnValue(
					$this->throwException(new Exception)
				)
			);
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getAllItems', 'getShippingAddress', 'getItemById', 'save', 'getAllAddresses'));
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($this->throwException(new Exception))
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($quoteMock))
			);

		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing building allocation request message
	 *
	 * @test
	 * @dataProvider providerBuildAllocationRequestMessageWithException
	 */
	public function testBuildAllocationRequestMessageWithException($quote)
	{
		// testing when building the allocation message throw an exception
		$this->assertNotNull(
			$this->_getAllocation()->buildAllocationRequestMessage($quote)
		);
	}

	public function providerProcessAllocation()
	{
		$allocationData = array(
			array(
				'lineId' => 1,
				'reservation_id' => 'TAN_DEV_CLI-ABC-44',
				'reservation_expires' => '2013-06-20 15:02:20',
				'qty' => 0
			)

		);

		return array(
			array($this->buildQuoteMock(), $allocationData)
		);
	}

	/**
	 * testing processing allocation data
	 *
	 * @test
	 * @dataProvider providerProcessAllocation
	 */
	public function testProcessAllocation($quote, $allocationData)
	{
		$this->assertSame(
			array('Sorry, item "SKU-1234" out of stock.'),
			$this->_getAllocation()->processAllocation($quote, $allocationData)
		);
	}

	public function providerUpdateQuoteWithEb2cAllocation()
	{
		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getId', 'getSku', 'getItemId', 'getQuote', 'save'));
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(2)
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
			->method('getItemId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($this->buildQuoteMock())
			);

		$quoteData = array(
				'lineId' => 1,
				'reservation_id' => 'TAN_DEV_CLI-ABC-44',
				'reservation_expires' => '2013-06-20 15:02:20',
				'qty' => 1
		);

		return array(
			array($itemMock, $quoteData)
		);
	}

	/**
	 * testing _updateQuoteWithEb2cAllocation method
	 *
	 * @test
	 * @dataProvider providerUpdateQuoteWithEb2cAllocation
	 */
	public function testUpdateQuoteWithEb2cAllocation($quoteItem, $quoteData)
	{
		$allocationReflector = new ReflectionObject($this->_getAllocation());
		$updateQuoteWithEb2cAllocation = $allocationReflector->getMethod('_updateQuoteWithEb2cAllocation');
		$updateQuoteWithEb2cAllocation->setAccessible(true);
		$this->assertSame(
			'Sorry, we only have 1 of item "SKU-1234" in stock.',
			$updateQuoteWithEb2cAllocation->invoke($this->_getAllocation(), $quoteItem, $quoteData)
		);
	}

	public function providerRollbackAllocation()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing rollbackAllocation method
	 *
	 * @test
	 * @dataProvider providerRollbackAllocation
	 */
	public function testRollbackAllocation($quote)
	{
		// testing when you can rolling back allocated inventory
		$this->assertNull(
			$this->_getAllocation()->rollbackAllocation($quote)
		);
	}

	/**
	 * testing when rolling back allocation quote item API call throw an exception
	 *
	 * @test
	 * @dataProvider providerRollbackAllocation
	 */
	public function testRollbackAllocationWithApiCallException($quote)
	{
		$coreHelperMock = $this->getMock('TrueAction_Eb2c_Core_Helper_Data', array('callApi'));
		$coreHelperMock->expects($this->any())
			->method('callApi')
			->will(
				$this->throwException(new Exception)
			);

		$inventoryHelper = Mage::helper('eb2cinventory');
		$inventoryReflector = new ReflectionObject($inventoryHelper);
		$coreHelper = $inventoryReflector->getProperty('coreHelper');
		$coreHelper->setAccessible(true);
		$coreHelper->setValue($inventoryHelper, $coreHelperMock);

		$allocationReflector = new ReflectionObject($this->_getAllocation());
		$helper = $allocationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_getAllocation(), $inventoryHelper);

		$this->assertSame(
			'',
			trim($this->_getAllocation()->rollbackAllocation($quote))
		);
	}

	public function providerHasAllocation()
	{
		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getEb2cReservationId'));
		$itemMock->expects($this->any())
			->method('getEb2cReservationId')
			->will($this->returnValue('FAKE-RESERVATION-ID')
			);
		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getAllItems'));
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing when allocation data is found in quote items
	 *
	 * @test
	 * @dataProvider providerHasAllocation
	 */
	public function testHasAllocation($quote)
	{
		// testing when building the allocation message throw an exception
		$this->assertSame(
			true,
			$this->_getAllocation()->hasAllocation($quote)
		);
	}
}
