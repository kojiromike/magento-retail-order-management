<?php
/**
 * @package Eb2c
 */
class TrueAction_Eb2c_Order_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $coreHelper;

	public function __construct()
	{
		$this->coreHelper = $this->getCoreHelper();
	}

	/**
	 * Instantiate core helper
	 *
	 * @return TrueAction_Eb2c_Core_Helper_Data
	 */
	public function getCoreHelper()
	{
		if (!$this->coreHelper) {
			$this->coreHelper = Mage::helper('eb2ccore');
		}
		return $this->coreHelper;
	}
}
