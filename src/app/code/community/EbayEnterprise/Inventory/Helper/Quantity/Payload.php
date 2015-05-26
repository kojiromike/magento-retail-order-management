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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IRequestQuantityItem;

class EbayEnterprise_Inventory_Helper_Quantity_Payload
{
	/**
	 * Transfer data from a quote item to a quantity
	 * request item payload.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @param IRequestQuantityItem
	 * @return IRequestQuantityItem
	 */
	public function itemToRequestQuantityItem(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		IRequestQuantityItem $itemPayload
	) {
		return $this->itemToQuantityItem($item, $itemPayload)
			->setFulfillmentLocationId($item->getFulfillmentLocationId())
			->setFulfillmentLocationType($item->getFulfillmentLocationType());
	}

	/**
	 * Transfer data from a quote item to a quantity
	 * request item payload.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @param IQuantityItem
	 * @return IQuantityItem
	 */
	public function itemToQuantityItem(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		IQuantityItem $itemPayload
	) {
		return $itemPayload->setItemId($item->getSku())
			->setLineId($item->getId());
	}
}
