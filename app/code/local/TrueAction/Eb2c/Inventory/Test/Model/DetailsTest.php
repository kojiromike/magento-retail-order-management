<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Model_DetailsTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_details;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_details = $this->_getDetails();
		Mage::app()->getConfig()->reinit(); // re-initialize config to get fresh loaded data
	}

	/**
	 * Get Details instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Details
	 */
	protected function _getDetails()
	{
		if (!$this->_details) {
			$this->_details = Mage::getModel('eb2c_inventory/details');
		}
		return $this->_details;
	}

	/**
	 * testing check
	 *
	 * @test
	 */
	public function testDetails()
	{
		$this->assertSame(
			'TrueAction_Eb2c_Inventory_Model_Details',
			get_class($this->_getDetails())
		);
	}
}
