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
    protected $shippingHelper;
    /** @var EbayEnterprise_Inventory_Helper_Quantity */
    protected $quantityHelper;

    public function __construct(array $init = [])
    {
        list($this->shippingHelper, $this->quantityHelper) = $this->checkTypes(
            $this->nullCoalesce(
                $init,
                'shipping_helper',
                Mage::helper('ebayenterprise_inventory/details_item_shipping')
            ),
            $this->nullCoalesce($init, 'quantity_helper', Mage::helper('ebayenterprise_inventory/quantity'))
        );
    }

    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Details_Item_Shipping $shippingHelper,
        EbayEnterprise_Inventory_Helper_Quantity $quantityHelper
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param  array
     * @param  string
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
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

    public function fillOutShippingItem(
        IOrderItem $itemPayload,
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $shippingMethod = $this->shippingHelper->getUsableMethod($address);
        $itemPayload->setItemId($item->getSku())
            ->setLineId($item->getAddressItemId() ?: $item->getId())
            ->setQuantity($this->quantityHelper->getRequestedItemQuantity($item))
            ->setGiftWrapRequested($this->isItemGiftWrapped($item))
            ->setAddressLines($address->getStreet(static::ADDRESS_ALL_STREET_LINES))
            ->setAddressCity($address->getCity())
            ->setAddressCountryCode($address->getCountryId())
            ->setShippingMethod($this->shippingHelper->getMethodSdkId($shippingMethod))
            ->setAddressMainDivision($address->getRegionCode())
            ->setAddressPostalCode($address->getPostcode())
            ->setShippingMethodDisplayText($this->shippingHelper->getMethodTitle($shippingMethod));
    }

    /**
     * return true if the item is giftwrapped; false otherwise.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    protected function isItemGiftWrapped(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        return (bool) $item->getGwId()
            || ($item->getAddress() && $item->getAddress()->getGwId());
    }
}
