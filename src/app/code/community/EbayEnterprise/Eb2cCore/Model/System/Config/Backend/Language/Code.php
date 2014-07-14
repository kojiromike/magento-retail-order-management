<?php
/**
 * The purpose of the backend model is to take its value, strip whitespace, and convert to lowercase
 */
class EbayEnterprise_Eb2cCore_Model_System_Config_Backend_Language_Code
	extends Mage_Core_Model_Config_Data
{
	/**
	 * Clean the language code before saving
	 *
	 * @return Mage_Core_Model_Abstract
	 */
	public function _beforeSave()
	{
		parent::_beforeSave();
		if ($this->isValueChanged()) {
			$this->setValue(strtolower(trim($this->getValue())));
		}
		return $this;
	}
}
