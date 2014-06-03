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
 * Override and disable the model that calculates shipping tax.
 */
class EbayEnterprise_Eb2cTax_Overrides_Model_Sales_Total_Quote_Shipping extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->setCode('shipping');
		$this->_calculator = Mage::getSingleton('tax/calculation');
		$this->_config     = Mage::getSingleton('tax/config');
	}

	/**
	 * Collect totals process.
	 *
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return Mage_Sales_Model_Quote_Address_Total_Abstract
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		return $this;
	}
}
