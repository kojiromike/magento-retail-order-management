<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Observer
{
	protected $_quantity;

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

	public function checkEb2cInventoryQuantity($observer)
	{
		Mage::log(
			'class = ' . get_class($observer), // log content
			Zend_Log::ERR // Log level
		);
		echo '<br />This is a test<br />INSIDE = checkEb2cInventoryQuantity(), method';
		exit(-1);
	}
}
