<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_DetailsTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_details;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_details = $this->_getDetails();

		$newHelper = new TrueAction_Eb2cInventory_Helper_Data();

		$detailsReflector = new ReflectionObject($this->_details);
		$helper = $detailsReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_details, $newHelper);
	}

	/**
	 * Get Details instantiated object.
	 *
	 * @return TrueAction_Eb2cInventory_Model_Details
	 */
	protected function _getDetails()
	{
		if (!$this->_details) {
			$this->_details = Mage::getModel('eb2cinventory/details');
		}
		return $this->_details;
	}

	public function buildQuoteMock()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
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

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getQty', 'getId', 'getSku', 'getItemId', 'getQuote')
		);
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

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'save')
		);

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

		return $quoteMock;
	}

	public function providerGetInventoryDetails()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing getting inventory details
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetInventoryDetails
	 */
	public function testGetInventoryDetails($quote)
	{
		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_getDetails()->getInventoryDetails($quote)
		);
	}

	/**
	 * testing when getting inventory details API call throw an exception
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetInventoryDetails
	 */
	public function testGetInventoryDetailsWithApiCallException($quote)
	{
		$apiModelMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Api',
			array('setUri', 'request')
		);
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception));

		$inventoryHelper = Mage::helper('eb2cinventory');
		$inventoryReflector = new ReflectionObject($inventoryHelper);
		$apiModel = $inventoryReflector->getProperty('apiModel');
		$apiModel->setAccessible(true);
		$apiModel->setValue($inventoryHelper, $apiModelMock);

		$detailsReflector = new ReflectionObject($this->_getDetails());
		$helper = $detailsReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_getDetails(), $inventoryHelper);

		$this->assertSame(
			'',
			trim($this->_getDetails()->getInventoryDetails($quote))
		);
	}

	public function providerBuildInventoryDetailsRequestMessage()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing building inventory details request message
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerBuildInventoryDetailsRequestMessage
	 */
	public function testBuildInventoryDetailsRequestMessage($quote)
	{
		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_getDetails()->buildInventoryDetailsRequestMessage($quote)
		);
	}

	public function providerBuildInventoryDetailsRequestMessageWithException()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
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

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getQty', 'getId', 'getSku', 'getItemId')
		);
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
			->will($this->returnValue($this->throwException(new Exception)));
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'save')
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

		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing building inventory details request message
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerBuildInventoryDetailsRequestMessageWithException
	 */
	public function testBuildInventoryDetailsRequestMessageWithException($quote)
	{
		// testing when building the inventory details message throw an exception
		$this->assertNotNull(
			$this->_getDetails()->buildInventoryDetailsRequestMessage($quote)
		);
	}

	public function providerProcessInventoryDetails()
	{
		$inventoryData = array(
			array(
				'lineId' => 1,
				'creationTime' => '2010-11-02T17:47:00',
				'display' => true,
				'deliveryWindow_from' => '2011-11-02T05:01:50Z',
				'deliveryWindow_to' => '2011-11-02T05:01:50Z',
				'shippingWindow_from' => '2011-11-02T05:01:50Z',
				'shippingWindow_to' => '2011-11-02T05:01:50Z',
				'shipFromAddress_line1' => 'Ten Bagshot Row',
				'shipFromAddress_city' => 'Bag End',
				'shipFromAddress_mainDivision' => 'PA',
				'shipFromAddress_countryCode' => 'US',
				'shipFromAddress_postalCode' => '19123'
			)

		);

		return array(
			array($this->buildQuoteMock(), $inventoryData)
		);
	}

	/**
	 * testing processing inventory details data
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerProcessInventoryDetails
	 */
	public function testProcessInventoryDetails($quote, $inventoryData)
	{
		$this->assertNull(
			$this->_getDetails()->processInventoryDetails($quote, $inventoryData)
		);
	}
}
