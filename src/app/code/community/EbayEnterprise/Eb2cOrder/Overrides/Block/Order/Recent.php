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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_Recent extends Mage_Sales_Block_Order_Recent
{
	// "default" number of orders to be shown by this block
	// carried over from the parent class where it is a hard-coded value
	const ORDERS_TO_SHOW = 5;
	/**
	 * Set the orders to collection of orders that both the OMS and Magento
	 * know about. Avoid a `parent` call to prevent an additional orders
	 * collection from being instantiated and immediately replaced.
	 */
	public function __construct()
	{
		$this->setOrders(
			Mage::helper('eb2corder')->getCurCustomerOrders()
				// This is the created_at in Magento, not OMS. As the sort is done
				// via the SQL when the collection is loaded, it needs to be sorted by
				// something Magento knows about.
				->addAttributeToSort('created_at', 'desc')
				->setPageSize(self::ORDERS_TO_SHOW)
		);
	}
}
