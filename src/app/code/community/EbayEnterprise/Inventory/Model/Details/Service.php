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

class EbayEnterprise_Inventory_Model_Details_Service
{
    const ITEM_UNAVAILABLE =
        'Ebayenterprise_Inventory_Details_Item_Unavailable';

    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Inventory_Model_Item_Details[] */
    protected $resultRecords;
    /** @var EbayEnterprise_Inventory_Model_Session */
    protected $inventorySession;
    /** @var EbayEnterprise_Inventory_Helper_Details_Factory */
    protected $factory;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $invHelper;
    /** @var array */
    protected $quoteItemDetails = [];
    /** @var EbayEnterprise_Inventory_Model_Quantity_Service */
    protected $quantityService;

    public function __construct($init = [])
    {
        list($this->invHelper, $this->logger, $this->logContext, $this->factory, $this->quantityService) =
            $this->checkTypes(
                $this->nullCoalesce($init, 'inv_helper', Mage::helper('ebayenterprise_inventory')),
                $this->nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
                $this->nullCoalesce($init, 'log_context', Mage::helper('ebayenterprise_magelog/context')),
                $this->nullCoalesce($init, 'factory', Mage::helper('ebayenterprise_inventory/details_factory')),
                $this->nullCoalesce($init, 'quantity_service', Mage::getModel('ebayenterprise_inventory/quantity_service'))
            );
    }

    /**
     * enforce types
     *
     * @param  EbayEnterprise_Inventory_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @param  EbayEnterprise_Inventory_Helper_Details_Factory
     * @param  EbayEnterprise_Inventory_Model_Quantity_Service
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Data $invHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext,
        EbayEnterprise_Inventory_Helper_Details_Factory $factory,
        EbayEnterprise_Inventory_Model_Quantity_Service $quantityService
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param string
     * @param array
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Get item details for the given quote item.
     * returns null if the item was excluded from the request or if
     * there was an error in the sdk
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return EbayEnterprise_Inventory_Model_Details_Item|null
     */
    public function getDetailsForItem(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if ($this->isItemAllowInventoryDetail($item)) {
            try {
                $result = $this->factory->createDetailsModel()
                    ->fetch($item->getQuote());
                return !is_null($result)
                    ? $result->lookupDetailsByItemId($this->getQuoteItemId($item)) : $result;
            } catch (EbayEnterprise_Inventory_Exception_Details_Operation_Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Determine if an item is possible to send inventory detail request.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    protected function isItemAllowInventoryDetail(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if ($item->getHasChildren()) {
            foreach ($item->getChildren() as $childItem) {
                if ($this->quantityService->canSendInventoryDetail($childItem)) {
                    return true;
                }
            }
            return false;
        }
        return $this->quantityService->canSendInventoryDetail($item);
    }

    /**
     * Get quote item id from a passed in sales/quote_item instance. Ensure
     * that if the passed in quote item has child product that it's the child quote
     * item id that get returned.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return int
     */
    protected function getQuoteItemId(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $children = $item->getChildren() ?:  [];
        foreach ($children as $childItem) {
            return $childItem->getId();
        }
        return $item->getId();
    }

    /**
     * Get item details for the given order item.
     * returns null if the item was excluded from the request or if
     * there was an error in the sdk
     *
     * @param  Mage_Sales_Model_Order_Item
     * @return EbayEnterprise_Inventory_Model_Details_Item|null
     */
    public function getDetailsForOrderItem(Mage_Sales_Model_Order_Item $item)
    {
        try {
            $result = $this->factory->createDetailsModel()
                ->fetch($item->getOrder()->getQuote());
            return !is_null($result)
                ? $result->lookupDetailsByItemId($item->getQuoteItemId()) : $result;
        } catch (EbayEnterprise_Inventory_Exception_Details_Operation_Exception $e) {
            return null;
        }
    }

    /**
     * fetch inventory details for the quote
     *
     * @param Mage_Sales_Model_Quote
     * @return self
     */
    public function updateDetailsForQuote(Mage_Sales_Model_Quote $quote)
    {
        try {
            $this->factory->createDetailsModel()
                ->fetch($quote);
            $this->handleUnavailableItems();
        } catch (EbayEnterprise_Inventory_Exception_Details_Operation_Exception $e) {
            // nothing to do here
        }
        return $this;
    }

    protected function handleUnavailableItems(Mage_Sales_Model_Quote $quote)
    {
        foreach ($quote->getAllItems() as $item) {
            if ($item->getHasChildren()) {
                continue;
            }
            $detail = $this->getDetailsForItem($item);
            if ($detail && !$detail->isAvailable() && !$this->hasQuantityError($item)) {
                // an item came back unavailable for an item that
                // succeeded in the last quantity request.
                $this->handleDesynchedFromQuantity();
            }
        }
    }

    /**
     * look through the error info on the item and return
     * true if the an entry was added by the quantity service.
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return boolean
     */
    protected function hasQuantityError(Mage_Sales_Model_Quote_Item $item)
    {
        foreach ($item->getErrorInfos()->getItems() as $errorInfo) {
            if (isset($errorInfo['origin'])
                && $errorInfo['origin'] ===
                    EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE
            ) {
                return true;
            }
        }
        return false;
    }

    protected function handleDesynchedFromQuantity()
    {
        throw Mage::exception(
            'EbayEnterprise_Inventory_Exception_Details_Unavailable',
            $this->invHelper->__(static::ITEM_UNAVAILABLE)
        );
    }
}
