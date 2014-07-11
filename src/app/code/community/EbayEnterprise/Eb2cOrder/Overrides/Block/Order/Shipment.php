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
	 * override the default to ensure the order_id and shipment_id are always included
	 * in the url.
	 * @param EbayEnterprise_Eb2cOrder_Model_Detail_Shipment $shipment
	 * @return string
	 */
	public function getPrintShipmentUrl($shipment)
	{
		return Mage::getUrl('*/*/printOrderShipment', array(
			'order_id' => $this->getOrder()->getId(),
			'shipment_id' => $shipment->getId()
		));
	}
}
