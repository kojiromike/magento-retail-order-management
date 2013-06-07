<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Model_QuantityTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_quantity;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_quantity = $this->_getQuantity();
		Mage::app()->getConfig()->reinit(); // re-initialize config to get fresh loaded data
	}

	/**
	 * Get Quantity instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Quantity
	 */
	protected function _getQuantity()
	{
		if (!$this->_quantity) {
			$this->_quantity = Mage::getModel('eb2c_inventory/quantity');
		}
		return $this->_quantity;
	}

	public function providerBuildQuantityRequestMessage()
	{
		return array(
			array(
				array(
					array('id' => 1, 'sku' => 'SKU_TEST_1'),
					array('id' => 2, 'sku' => 'SKU_TEST_2'),
					array('id' => 3, 'sku' => 'SKU_TEST_3')
				)
			)
		);
	}

	public function expectedBuildQuantityRequestMessage()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
			preg_replace('/[\t\n\r]/', '', '<QuantityRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
				<QuantityRequest lineId="1" itemId="SKU_TEST_1"/><QuantityRequest lineId="2" itemId="SKU_TEST_2"/>
				<QuantityRequest lineId="3" itemId="SKU_TEST_3"/></QuantityRequestMessage>'
			);
	}

	/**
	 * testing check
	 *
	 * @test
	 * @dataProvider providerBuildQuantityRequestMessage
	 */
	public function testBuildQuantityRequestMessage($items)
	{
		$this->assertSame(
			trim($this->expectedBuildQuantityRequestMessage()),
			trim($this->_getQuantity()->buildQuantityRequestMessage($items)->saveXML())
		);
	}
}
