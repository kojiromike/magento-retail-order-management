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

class EbayEnterprise_Order_Overrides_Block_Order_Shipment_Items
	extends Mage_Sales_Block_Order_Shipment_Items
{
	/**
	 * @see parent::getPrintShipmentUrl()
	 * override the default to ensure the order_id and shipment_id are always included
	 * in the URL.
	 *
	 * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
	 * @return string
	 */
	public function getPrintShipmentUrl($shipment)
	{
		return $this->getUrl('*/*/printOrderShipment', [
			'order_id' => $this->getOrder()->getRealOrderId(),
			'shipment_id' => $shipment->getIncrementId()
		]);
	}

	/**
	 * @see parent::getPrintAllShipmentsUrl()
	 * override the default to ensure the order_id are always included in the URL.
	 *
	 * @param  EbayEnterprise_Order_Model_Detail_Process_IResponse
	 * @return string
	 */
	public function getPrintAllShipmentsUrl($order)
	{
		return $this->getUrl('*/*/printShipment', [
			'order_id' => $order->getRealOrderId()
		]);
	}

	/**
	 * Retrieve current order model instance
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		return Mage::registry('rom_order');
	}
}
