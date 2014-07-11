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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_History extends Mage_Sales_Block_Order_History
{
	/**
	 * Template for this block doesn't get set by Magento in layout XML. In
	 * the overridden block, the default template is set in the constructor.
	 * Setting this property does the same thing but doesn't require the call in
	 * the constructor.
	 * @var string template path
	 */
	protected $_template = 'sales/order/history.phtml';
	/**
	 * Set the orders to collection of orders that both the OMS and Magento
	 * know about. Avoid a `parent` call to prevent an additional orders
	 * collection from being instantiated and immediately replaced.
	 */
	public function __construct()
	{
		// use the eb2corder/data helper to get orders in OMS and Magento
		$this->setOrders(Mage::helper('eb2corder')->getCurCustomerOrders());
	}
}
