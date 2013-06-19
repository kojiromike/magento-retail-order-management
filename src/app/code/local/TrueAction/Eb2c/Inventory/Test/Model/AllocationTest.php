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
		Mage::app()->getConfig()->reinit(); // re-initialize config to get fresh loaded data
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

	public function providerAllocateQuoteItems()
	{
		$quote = Mage::getModel('sales/quote')->load(42);
		return array(
			array($quote)
		);
	}

	/**
	 * testing AllocateQuoteItems method
	 *
	 * @test
	 * @dataProvider providerAllocateQuoteItems
	 */
	public function testAllocateQuoteItems($quote)
	{
		/*$this->assertNotNull(
			$this->_getAllocation()->allocateQuoteItems($quote)
		);*/
	}
}
