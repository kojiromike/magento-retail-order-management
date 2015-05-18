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

class EbayEnterprise_Inventory_Model_Details_Result
{
    /** @var EbayEnterprise_Inventory_Model_Details_Item[] */
    protected $unavailableItems = [];
    /** @var EbayEnterprise_Inventory_Model_Details_Item[] */
    protected $detailedItems = [];
    /** @var EbayEnterprise_Inventory_Model_Details_Item[] */
    protected $resultsById = [];
    /** @var EbayEnterprise_Inventory_Helper_Details_Factory */
    protected $factory;

    public function __construct(array $init = [])
    {
        list($unavailableItems, $detailedItems, $this->factory) =
            $this->checkTypes(
                $this->nullCoalesce($init, 'unavailable_items', []),
                $this->nullCoalesce($init, 'detail_items', []),
                $this->nullCoalesce($init, 'factory', Mage::helper('ebayenterprise_inventory/details_factory'))
            );
        $this->unavailableItems = $this->convertToItems($unavailableItems);
        $this->detailedItems = $this->convertToItems($detailedItems);
    }

    /**
     * ensure dependency types
     *
     * @param array
     * @param array
     * @param EbayEnterprise_Inventory_Helper_Details_Factory
     * @return array
     */
    protected function checkTypes(
        array $unavailableItems,
        array $detailedItems,
        EbayEnterprise_Inventory_Helper_Details_Factory $factory
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
     * get the item detail for the specified item id.
     * a null result means the item was never sent in
     * the request.
     *
     * @param int
     * @return EbayEnterprise_Inventory_Details_Item
     */
    public function lookupDetailsByItemId($itemId)
    {
        if (!$this->resultsById) {
            $this->resultsById = $this->mapResultsByItemId();
        }
        return $this->nullCoalesce($this->resultsById, $itemId, null);
    }

    public function getUnavailableItems()
    {
        return $this->unavailableItems;
    }

    public function getDetailedItems()
    {
        return $this->detailedItems;
    }

    protected function convertToItems(array $itemsData)
    {
        $items = [];
        foreach ($itemsData as $itemData) {
            $items[] = $this->buildItemModel($itemData);
        }
        return $items;
    }

    protected function buildItemModel(array $itemData)
    {
        return $this->factory->createItemDetails($itemData);
    }

    /**
     * get the results as a single list of detail objects mapped to
     * the quote item id.
     */
    protected function mapResultsByItemId()
    {
        $resultRecords = [];
        foreach ($this->getDetailedItems() as $detail) {
            $resultRecords[$detail->getItemId()] = $detail;
        }
        foreach ($this->getUnavailableItems() as $detail) {
            $resultRecords[$detail->getItemId()] = $detail;
        }
        return $resultRecords;
    }
}
