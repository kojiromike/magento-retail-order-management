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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_Shipment extends Mage_Sales_Block_Order_Shipment
{
	/**
	 * @see parent::getPrintShipmentUrl()
	 * overriding this method because shipment will not
	 * exists in magento therefore we should simply use the
	 * print all shipment url which uses the order id, which
	 * should both exists in Magento and OMS.
	 * @return string
	 */
	public function getPrintShipmentUrl($shipment)
	{
		Mage::log(sprintf('[%s] increment id: %s', __METHOD__, $this->getOrder()->getId()), Zend_Log::DEBUG);
		return $this->getPrintAllShipmentsUrl($this->getOrder());
	}
}
