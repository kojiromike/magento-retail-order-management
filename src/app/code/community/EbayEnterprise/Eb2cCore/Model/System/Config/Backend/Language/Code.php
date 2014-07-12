<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Clean the provided language code value so it can be consistent with BCP47
 */
class EbayEnterprise_Eb2cCore_Model_System_Config_Backend_Language_Code extends Mage_Core_Model_Config_Data
{
	/**
	 * Attempt to canonicalize the provided language code.
	 *
	 * @return self
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
