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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsRequest;

/**
 * Instantiates objects for working with inventory details
 */
class EbayEnterprise_Inventory_Helper_Details_Factory
{
    /**
     * create an object for a single item's details
     *
     * @param array
     * @return EbayEnterprise_Inventory_Model_Details_Item
     */
    public function createItemDetails(array $init)
    {
        return Mage::getModel('ebayenterprise_inventory/details_item', $init);
    }

    /**
     * build a result object
     *
     * @param array
     * @param array
     * @return EbayEnterprise_Inventory_Helper_Details_Result
     */
    public function createResult(array $detailItems = [], array $unavailableItems = [])
    {
        return Mage::getModel('ebayenterprise_inventory/details_result', [
            'unavailable_items' => $unavailableItems,
            'detail_items' => $detailItems
        ]);
    }

    /**
     * create an object to build out a request payload
     *
     * @param IInventoryDetailsRequest
     * @return EbayEnterprise_Inventory_Model_Details_Request_Builder
     */
    public function createRequestBuilder(IInventoryDetailsRequest $request)
    {
        return Mage::getModel(
            'ebayenterprise_inventory/details_request_builder',
            ['request' => $request]
        );
    }

    /**
     * create an object to perform the inventory details operation
     *
     * @return EbayEnterprise_Inventory_Model_Details
     */
    public function createDetailsModel()
    {
        return Mage::getModel('ebayenterprise_inventory/details');
    }
}
