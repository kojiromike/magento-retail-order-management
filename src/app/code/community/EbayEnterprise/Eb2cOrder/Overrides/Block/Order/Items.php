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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_Items extends Mage_Core_Block_Template
{
	protected $_template = 'eb2corder/sales/order/ebayenterprise_items.phtml';
	/**
	 * Retrieve current rom_order model instance
	 *
	 * @return EbayEnterprise_Eb2cOrder_Model_Detail
	 */
	public function getOrder()
	{
		return Mage::registry('rom_order');
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
	 * Calculate the grand order total.
	 *
	 * @return float
	 */
	public function getOrderTotal()
	{
		$order = $this->getOrder();
		return $order->getSubtotal() + $order->getShippingAmount() + $order->getDiscountAmount() + $order->getTaxAmount();
	}
}
