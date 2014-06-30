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

class EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail_Order_Shipment_Adapter
	extends Mage_Sales_Model_Order_Shipment
{
	/**
	 * get all shipment tracking collection through magic
	 * @return Varien_Data_Collection
	 */
	public function getTracksCollection()
	{
		return $this->getData('tracks_collection');
	}
	/**
	 * getting shipped items through magic
	 * @return Varien_Data_Collection
	 */
	public function getAllItems()
	{
		return $this->getData('all_items');
	}
	/**
	 * getting shipping address data
	 * @return Mage_Sales_Model_Order_Address
	 */
	public function getShippingAddress()
	{
		return $this->getData('shipping_address');
	}
	/**
	 * getting a collection of shipped items
	 * @return Varien_Data_Collection
	 */
	public function getItemsCollection()
	{
		return $this->getAllItems();
	}
}
