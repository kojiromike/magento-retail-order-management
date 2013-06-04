<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Details extends Mage_Core_Model_Abstract
{
	protected $_helper;

	public function __construct()
	{
		$this->_helper = $this->_getHelper();
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2c_inventory');
		}
		return $this->_helper;
	}

	/**
	 * take a quote, make inventoryDetail request to eb2c for each quote items
	 *
	 * @param $quote
	 * @return void
	 */
	public function makeRequestDetails($quote)
	{
		if ($quote) { // we have a valid quote object
			foreach($quote->getAllItems() as $item){
				// get quote item
				// build orderItem request, using some method in the helper

			}
		}
	}
}
