<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_Overrides_Stock_ItemTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_item;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_item = Mage::getModel('eb2cinventoryoverride/stock_item');
	}

	/**
	 * testing canSubtractQty method
	 *
	 * @large
	 * @test
	 */
	public function testCanSubtractQty()
	{
		$this->assertSame(
			false,
			$this->_item->canSubtractQty()
		);
	}
}
