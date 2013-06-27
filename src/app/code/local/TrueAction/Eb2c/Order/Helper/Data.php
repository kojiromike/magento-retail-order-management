<?php
/**
 * @package Eb2c
 */
class TrueAction_Eb2c_Order_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $coreHelper;
	public $constHelper;

	public function __construct()
	{
		$this->coreHelper = $this->getCoreHelper();
		$this->constHelper = $this->getConstHelper();
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

	/**
	 * Get Constants helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Constants
	 */
	public function getConstHelper()
	{
		if (!$this->constHelper) {
			$this->constHelper = Mage::helper('eb2corder/constants');
		}
		return $this->constHelper;
	}


	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 *
	 * @param string $optIndex, the operation index of the associative array
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($verb)
	{
		$const = $this->getConstantHelper();
		$apiUri = '';
		if (!(bool) $this->getConfigModel()->developer_mode) {
			$apiUri = sprintf(
				$const::URI_FROMAT,
				$const::ENV,
				$const::REGION,
				$const::VERSION,
				$this->getCoreConfigHelper()->store_id,
				$const::SERVICE,
				$verb,
				$const::RETURN_FORMAT
			);
		}
		return $apiUri;
	}
}
