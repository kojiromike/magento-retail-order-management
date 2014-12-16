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
	const ORDERS_TO_SHOW = 5;	// carried over from the parent class where it is a hard-coded value

	public function __construct()
	{
		$this->setOrders(
			Mage::helper('eb2corder')->getCurCustomerOrders()
		);
	}
	/**
	 * Return the default number of orders to show
	 * @return int number of orders
	 */
	public function getMaxOrdersToShow()
	{
		return self::ORDERS_TO_SHOW;
	}
	/**
	 * Returns URL for view a specific order id.
	 * @param string $orderId
	 * @return string
	 */
	public function getViewUrl($orderId)
	{
		return $this->getUrl('sales/order/romview', array('order_id' => $orderId));
	}
	/**
	 * Returns Helper
	 * @param helper type (default eb2corder)
	 * @return EbayEnterprise_Eb2cOrder_Helper_Data
	 */
	public function getHelper($type='eb2corder')
    {
		return Mage::helper($type);
	}
	/**
	 * Given an amount format according Sale/ Order formatting rules
	 * @param string amount
	 * @return string formatted amount
	 */
	public function formatPrice($amount)
	{
		return Mage::getModel('sales/order')->formatPrice($amount);
	}
}

