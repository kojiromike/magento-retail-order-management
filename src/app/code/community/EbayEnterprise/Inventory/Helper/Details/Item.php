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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IOrderItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IDetailItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAddress;

class EbayEnterprise_Inventory_Helper_Details_Item
{
    const ADDRESS_ALL_STREET_LINES = -1;

    /** @var EbayEnterprise_Inventory_Helper_Details_Item_Shipping */
    protected $_shippingHelper;
    /** @var EbayEnterprise_Inventory_Helper_Quantity */
    protected $_quantityHelper;

    public function __construct()
    {
        $this->_shippingHelper = Mage::helper('ebayenterprise_inventory/details_item_shipping');
        $this->_quantityHelper = Mage::helper('ebayenterprise_inventory/quantity');
    }

    public function extractItemIdentification(IItem $itemPayload)
    {
        return [
            'item_id' => $itemPayload->getLineId(),
            'sku' => $itemPayload->getItemId()
        ];
    }

    public function extractItemDetails(IDetailItem $itemPayload)
    {
        return array_merge($this->extractItemIdentification($itemPayload), [
            'delivery_window_from_date' => $itemPayload->getDeliveryWindowFromDate(),
            'delivery_window_to_date' => $itemPayload->getDeliveryWindowToDate(),
            'shipping_window_from_date' => $itemPayload->getShippingWindowFromDate(),
            'shipping_window_to_date' => $itemPayload->getShippingWindowToDate(),
            'delivery_estimate_creation_time' => $itemPayload->getDeliveryEstimateCreationTime(),
            'delivery_estimate_display_flag' => $itemPayload->getDeliveryEstimateDisplayFlag(),
            'delivery_estimate_message' => $itemPayload->getDeliveryEstimateMessage(),
            'ship_from_lines' => $itemPayload->getAddressLines(),
            'ship_from_city' => $itemPayload->getAddressCity(),
            'ship_from_main_division' => $itemPayload->getAddressMainDivision(),
            'ship_from_country_code' => $itemPayload->getAddressCountryCode(),
            'ship_from_postal_code' => $itemPayload->getAddressPostalCode(),
        ]);
    }

    public function applyQuoteItemData(IOrderItem $itemPayload, Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $itemPayload->setItemId($item->getSku())
            ->setLineId($item->getAddressItemId() ?: $item->getId())
            ->setQuantity($this->_quantityHelper->getRequestedItemQuantity($item))
            // optional
            ->setGiftWrapRequested($this->_isItemGiftWrapped($item));
    }

    public function applyMageAddressData(IAddress $itemPayload, Mage_Customer_Model_Address_Abstract $address)
    {
        $shippingMethod = $this->_shippingHelper->getUsableMethod($address);
        $itemPayload->setAddressLines($address->getStreet(static::ADDRESS_ALL_STREET_LINES))
            ->setAddressCity($address->getCity())
            ->setAddressCountryCode($address->getCountryId())
            ->setShippingMethod($this->_shippingHelper->getMethodSdkId($shippingMethod))
            ->setAddressMainDivision($address->getRegionCode())
            ->setAddressPostalCode($address->getPostcode())
            ->setShippingMethodDisplayText($this->_shippingHelper->getMethodTitle($shippingMethod));
    }

    /**
     * return true if the item is giftwrapped; false otherwise.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    protected function _isItemGiftWrapped(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        return (bool) $item->getGwId()
            || ($item->getAddress() && $item->getAddress()->getGwId());
    }
}
