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

class EbayEnterprise_Eb2cPayment_Overrides_Model_Cc extends EbayEnterprise_Pbridge_Model_Cc
{
	/**
	 * @see Enterprise_Pbridge_Block_Adminhtml_Sales_Order_Create_Abstract::is3dSecureEnabled
	 * in order to resolved issue in pbridge/cc model class where creating order in admin
	 * is calling a non-existing method in the pbridge class
	 * @return bool false because we hardcoded to always return false
	 * @codeCoverageIgnore
	 */
	public function is3dSecureEnabled()
	{
		return false;
	}
}
