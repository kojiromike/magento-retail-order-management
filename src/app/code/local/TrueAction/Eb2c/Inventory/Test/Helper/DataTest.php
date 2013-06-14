<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = $this->_getHelper();
		Mage::app()->getConfig()->reinit(); // re-initialize config to get fresh loaded data
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2cinventory');
		}
		return $this->_helper;
	}

	/**
	 * testing getXmlNs method
	 *
	 * @test
	 */
	public function getXmlNs()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			$this->_getHelper()->getXmlNs()
		);
	}

	/**
	 * testing getQuantityUri method
	 *
	 * @test
	 */
	public function getQuantityUri()
	{
		$this->assertSame(
			'http://eb2c.rgabriel.mage.tandev.net/eb2c/api/request/QuantityResponseMessage.xml',
			$this->_getHelper()->getQuantityUri()
		);
	}
}
