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

class EbayEnterprise_Inventory_Model_Details_Request_Builder
{
    /** @var EbayEnterprise_Inventory_Helper_Details_Item */
    protected $_itemHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_logContext;
    /** @var IInventoryDetailsRequest */
    protected $_request;

    public function __construct(array $init = [])
    {
        list(
            $this->_request,
            $this->_logger,
            $this->_logContext,
            $this->_itemHelper
        ) = $this->_checkTypes(
            $init['request'],
            $this->_nullCoalesce('logger', $init, Mage::helper('ebayenterprise_magelog')),
            $this->_nullCoalesce('log_context', $init, Mage::helper('ebayenterprise_magelog/context')),
            $this->_nullCoalesce('item_helper', $init, Mage::helper('ebayenterprise_inventory/details_item'))
        );
    }

    /**
     * Fill in default values.
     *
     * @param  string
     * @param  array
     * @param  mixed
     * @return mixed
     */
    protected function _nullCoalesce($key, array $arr, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    protected function _checkTypes(
        IInventoryDetailsRequest $request,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $loggerContext,
        EbayEnterprise_Inventory_Helper_Details_Item $itemHelper
    ) {
        return func_get_args();
    }

    public function addItemPayloads(
        array $items,
        Mage_Sales_Model_Quote_Address $address
    ) {
        foreach ($items as $item) {
            $this->_addItemPayload($item, $address);
        }
    }

    protected function _addItemPayload(Mage_Sales_Model_Quote_Item_Abstract $item, Mage_Customer_Model_Address_Abstract $address)
    {
        $itemPayload = $this->_getIspuItem($item, $address);
        if (!$itemPayload) {
            $itemPayload = $this->_getShippingItem($item, $address);
        }
        $this->_request->getItems()->attach($itemPayload);
    }

    /**
     * Attempt to build an ispu item payload for the item. null is returned
     * if unsuccessful.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @param  Mage_Customer_Model_Address_Abstract
     * @return IInStorePickUpItem|null
     */
    protected function _getIspuItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $itemIterable = $this->_request->getItems();
        $ispuItem = $itemIterable->getEmptyInStorePickUpItem();
        $this->_delegateInStorePickUpItem($ispuItem, $item, $address);
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
    protected function _delegateInStorePickUpItem(
        IInStorePickUpItem $ispuItem,
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $this->_logger->debug(
            'preparing in-store-pickup item payload',
            $this->_logContext->getMetaData(__CLASS__)
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
    protected function _getShippingItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $payload = $this->_request->getItems()->getEmptyShippingItem();
        $this->_itemHelper->applyQuoteItemData($payload, $item);
        $this->_itemHelper->applyMageAddressData($payload, $address);
        return $payload;
    }
}
