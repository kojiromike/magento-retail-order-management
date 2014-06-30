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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_Print_Shipment extends Mage_Sales_Block_Order_Print_Shipment
{
	public function getOrder()
	{
		$order = parent::getOrder();
		if (!$order instanceof EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail_Order_Adapter) {
			$newOrder = Mage::getModel('eb2corder/customer_order_detail_order_adapter');
			$newOrder->loadByIncrementId($order->getRealOrderId());
			Mage::unregister('current_order');
			Mage::register('current_order', $newOrder);
		}
		return $order;
	}
}
