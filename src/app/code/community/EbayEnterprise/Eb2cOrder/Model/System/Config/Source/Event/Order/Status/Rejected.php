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

class EbayEnterprise_Eb2cOrder_Model_System_Config_Source_Event_Order_Status_Rejected
{
	/**
	 * build option array of all order statuses with a state 'canceled'.
	 * @return array
	 */
	public function toOptionArray()
	{
		return Mage::helper('eb2corder')->getOrderStatusOptionArrayByState(
			Mage_Sales_Model_Order::STATE_CANCELED
		);
	}
}
