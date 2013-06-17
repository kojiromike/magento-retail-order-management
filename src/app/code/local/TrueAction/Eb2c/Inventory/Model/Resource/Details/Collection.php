<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Resource_Details_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	/**
	 * Resource initialization
	 *
	 */
	protected function _construct()
	{
		$this->_init('eb2cinventory/details');
	}
}
