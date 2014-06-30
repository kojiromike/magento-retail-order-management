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

class EbayEnterprise_Eb2cOrder_Overrides_Helper_Shipping
	extends Mage_Shipping_Helper_Data
{
	/**
	 * @see parent::getTrackingPopupUrlBySalesModel()
	 * Overriding this helper method because these data
	 * will no long exists in magento since it will be
	 * coming from OMS
	 * @param Mage_Sales_Model_Abstract $model
	 * @return string
	 */
	public function getTrackingPopupUrlBySalesModel($model)
	{
		if (!$model instanceof Mage_Sales_Model_Order) {
			$model = Mage::registry('current_order');
		}
		return $this->_getTrackingUrl('order_id', $model);
	}
}
