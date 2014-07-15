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

class EbayEnterprise_Eb2cTax_Overrides_Model_Total_Quote_Tax_Giftwrapping
	extends Enterprise_GiftWrapping_Model_Total_Quote_Tax_Giftwrapping
{
	/**
	 * override the default collect function to do nothing.
	 * @param  Mage_Sales_Model_Quote_Address $address address to collect totals for
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		return $this;
	}
}
