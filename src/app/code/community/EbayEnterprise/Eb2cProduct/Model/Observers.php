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

class EbayEnterprise_Eb2cProduct_Model_Observers
{
	/**
	 * This observer locks attributes we've configured as read-only
	 * @return void
	 */
	public function lockReadOnlyAttributes(Varien_Event_Observer $observer)
	{
		$readOnlyAttributesString = Mage::helper('eb2cproduct')->getConfigModel()->readOnlyAttributes;
		// We use preg_split's PREG_SPLIT_NO_EMPTY so multiple ',' won't populate an array slot
		//  with an empty string. A single string without separators ends up at index 0.
		$readOnlyAttributes = preg_split('/,/', $readOnlyAttributesString, -1, PREG_SPLIT_NO_EMPTY);
		if ($readOnlyAttributes) {
			$product = $observer->getEvent()->getProduct();
			foreach ($readOnlyAttributes as $readOnlyAttribute) {
				$product->lockAttribute($readOnlyAttribute);
			}
		}
	}
}
