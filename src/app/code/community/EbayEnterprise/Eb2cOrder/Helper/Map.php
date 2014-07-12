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

class EbayEnterprise_Eb2cOrder_Helper_Map
{
	/**
	 * extract the data value from an pass in object that is inherited an
	 * Varien_Object class
	 * @param  Varien_Object $item
	 * @param  string $attributeCode
	 * @return string
	 */
	public function getAttributeValue(Varien_Object $item, $attributeCode)
	{
		return Mage::helper('core')->escapeHtml($item->getDataUsingMethod($attributeCode));
	}
}
