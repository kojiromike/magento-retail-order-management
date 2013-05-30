<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
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

	public function providerObserver()
	{
		$eventMock = $this->getMock('Varien_Event_Observer',
			array('getParams')
		);
		return array(array($eventMock));
	}

	/**
	 * testing check
	 *
	 * @test
	 * @dataProvider providerObserver
	 */
	public function testCheckEb2cInventoryQuantity($observer)
	{
		$this->assertNotEmpty(
			$this->_getObserver()->checkEb2cInventoryQuantity($observer)
		);
	}
}
