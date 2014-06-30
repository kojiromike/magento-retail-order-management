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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Shipping_Tracking_Popup
	extends Mage_Shipping_Block_Tracking_Popup
{
	/**
	 * @see parent::getTrackingInfo()
	 * overriding this method so that we can insert our
	 * own shipping info array of data with ROM Data.
	 * @return array
	 */
	public function getTrackingInfo()
	{
		$info = Mage::registry('current_shipping_info');
		$newOrder = Mage::getModel('eb2corder/customer_order_detail_order_adapter');
		$newOrder->load($info->getOrderId());
		$newOrder->loadByIncrementId($newOrder->getRealOrderId());
		$info->setTrackingInfo($newOrder->getTrackingInfo());
		return $newOrder->getTrackingInfo();
	}
}
