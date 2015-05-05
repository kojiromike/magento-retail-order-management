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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_History extends Mage_Core_Block_Template
{
	/**
	 * Template for this block doesn't get set by Magento in layout XML. In
	 * the overridden block, the default template is set in the constructor.
	 * Setting this property does the same thing but doesn't require the call in
	 * the constructor.
	 * @var string template path
	 */
	protected $_template = 'eb2corder/sales/order/ebayenterprise_history.phtml';
	/**
	 * Set the orders to collection of orders that both the OMS and Magento
	 * know about. Avoid a `parent` call to prevent an additional orders
	 * collection from being instantiated and immediately replaced.
	 */
	public function __construct()
	{
		$this->setOrders(
			Mage::helper('eb2corder')->getCurCustomerOrders()
		);
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
	 * Returns URL to cancel an order.
	 * @param string
	 * @return string
	 */
	public function getCancelUrl($orderId)
	{
		return $this->getUrl('sales/order/romcancel', array('order_id' => $orderId));
	}
}
