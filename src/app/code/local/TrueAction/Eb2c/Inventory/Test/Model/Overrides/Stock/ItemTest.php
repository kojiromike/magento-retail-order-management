<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Model_Overrides_Stock_ItemTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_item;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_item = $this->_getItem();
		Mage::app()->getConfig()->reinit(); // re-initialize configuration to get fresh loaded data
	}

	/**
	 * Get Item instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Override_Model_Item
	 */
	protected function _getItem()
	{
		if (!$this->_item) {
			$this->_item = Mage::getModel('eb2cinventoryoverride/stock_item');
		}
		return $this->_item;
	}

	/**
	 * testing canSubtractQty method
	 *
	 * @test
	 */
	public function testCanSubtractQty()
	{
		$this->assertSame(
			false,
			$this->_getItem()->canSubtractQty()
		);
	}
}
