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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInStorePickUpItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IShippingItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsRequest;

abstract class EbayEnterprise_Inventory_Model_Details_Request_Builder_Abstract
{
    /** @var EbayEnterprise_Inventory_Helper_Details_Item */
    protected $itemHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var IInventoryDetailsRequest */
    protected $request;

    /**
     * Fill in default values.
     *
     * @param  string
     * @param  array
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce($key, array $arr, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    protected function addItemPayload(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $itemPayload = $this->getIspuItem($item, $address);
        if (!$itemPayload) {
            $itemPayload = $this->getShippingItem($item, $address);
        }
        $this->request->getItems()->attach($itemPayload);
    }

    /**
     * Attempt to build an ispu item payload for the item. null is returned
     * if unsuccessful.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @param  Mage_Customer_Model_Address_Abstract
     * @return IInStorePickUpItem|null
     */
    protected function getIspuItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $itemIterable = $this->request->getItems();
        $ispuItem = $itemIterable->getEmptyInStorePickUpItem();
        $this->delegateInStorePickUpItem($ispuItem, $item, $address);
        return trim($ispuItem->getAddressLines()) ? $ispuItem : null;
    }

    /**
     * fire an event to allow populating an in store pickup payload
     * from an external source.
     *
     * @param IInStorePickUpItem
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @param Mage_Customer_Model_Address_Abstract
     */
    protected function delegateInStorePickUpItem(
        IInStorePickUpItem $ispuItem,
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $this->logger->debug(
            'preparing in-store-pickup item payload',
            $this->logContext->getMetaData(__CLASS__)
        );
        Mage::dispatchEvent('ebayenterprise_inventory_instorepickup_item', [
            'itemPayload' => $ispuItem,
            'item' => $item,
            'address' => $address,
        ]);
    }

    /**
     * build a shipping item payload for the request
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @param  Mage_Customer_Model_Address_Abstract
     * @return IPayload
     */
    protected function getShippingItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $payload = $this->request->getItems()->getEmptyShippingItem();
        $this->itemHelper->fillOutShippingItem($payload, $item, $address);
        return $payload;
    }
}
