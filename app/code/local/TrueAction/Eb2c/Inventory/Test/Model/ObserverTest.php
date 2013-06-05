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
		$product = Mage::getModel('catalog/product')->load(1);
		$eventMock = $this->getMock('Varien_Event', array('getRequest'));
		$eventMock->expects($this->any())
			->method('getRequest')
			->will($this->returnValue($order)
			);
		$observerMock = $this->getMock('TrueAction_Eb2c_Inventory_Model_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing check
	 *
	 * @test
	 * @dataProvider providerObserver
	 */
	public function testCheckEb2cInventoryQuantity($observer)
	{
		/*$this->assertNotEmpty(
			$this->_getObserver()->checkEb2cInventoryQuantity($observer)
		);*/
	}
}
