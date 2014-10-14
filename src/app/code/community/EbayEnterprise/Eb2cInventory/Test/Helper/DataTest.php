<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class EbayEnterprise_Eb2cInventory_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->clearStoreConfigCache();
		// FYI: instantiating using regular Mage::getHelper method create
		// a singleton oject which mess with load fixtures for the config
		$this->_helper = new EbayEnterprise_Eb2cInventory_Helper_Data();
	}

	/**
	 * testing getXmlNs method
	 *
	 */
	public function testGetXmlNs()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			$this->_helper->getXmlNs()
		);
	}

	/**
	 * testing getOperationUri method
	 *
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/quantity/get.xml',
			$this->_helper->getOperationUri('check_quantity')
		);

		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/details/get.xml',
			$this->_helper->getOperationUri('get_inventory_details')
		);

		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/allocations/create.xml',
			$this->_helper->getOperationUri('allocate_inventory')
		);

		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/allocations/delete.xml',
			$this->_helper->getOperationUri('rollback_allocation')
		);
	}

	/**
	 * testing getOperationUri method with a store other than the default
	 *
	 * @loadFixture
	 */
	public function testGetOperationUriNonDefaultStore()
	{
		$this->assertSame('store_id2', Mage::getStoreConfig('eb2ccore/general/store_id', 'canada'), 'storeid for canada not retrieved');
		$this->setCurrentStore('canada');
		// check to make sure that if the current store has another value for store id,
		// the store level value is chosen over the default.
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id2/inventory/allocations/delete.xml',
			$this->_helper->getOperationUri('rollback_allocation')
		);
	}

	public function providerGetReservationId()
	{
		return array(
			array(43)
		);
	}
	/**
	 * Test filtering a list of quote items down to only those that are inventoried items
	 * @return [type] [description]
	 */
	public function testGetInventoriedItemsFromQuote()
	{
		$items = array(
			Mage::getModel('sales/quote_item'),
			Mage::getModel('sales/quote_item'),
		);
		$inventoryItems = array($items[0]);
		$helper = $this->getHelperMock('eb2ccore/quote_item', array('isItemInventoried'));
		$this->replaceByMock('helper', 'eb2ccore/quote_item', $helper);
		$helper
			->expects($this->exactly(2))
			->method('isItemInventoried')
			->with($this->isInstanceOf('Mage_Sales_Model_Quote_Item'))
			->will($this->onConsecutiveCalls(array(true, false)));
		$this->assertSame(
			$inventoryItems,
			Mage::helper('eb2cinventory')->getInventoriedItems($items)
		);
	}
	/**
	 * Test EbayEnterprise_Eb2cInventory_Helper_Data::hasRequiredShippingDetail method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cInventory_Helper_Data::hasRequiredShippingDetail
	 *                twice given Mage_Sales_Model_Quote_Address object load with data from the provider, first time the
	 *                test run the provider data will be an array with key value map to test data and the testCase parameter
	 *                will be the key passing to the expected method of this test which will have the expected result
	 *                the second time the test run the provider data parameter will be an array keys map to empty null
	 *                values and the testcase provider prameter will get an expectation result
	 * @dataProvider dataProvider
	 * @loadExpectation
	 */
	public function testHasRequiredShippingDetail($data, $testCase)
	{
		$result = $this->expected($testCase)->getResult();
		$address = Mage::getModel('sales/quote_address')->addData($data);
		$this->assertSame($result, Mage::helper('eb2cinventory')->hasRequiredShippingDetail($address));
	}
}
