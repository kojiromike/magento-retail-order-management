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

/**
 * Interface for quantity service results for a single item.
 */
interface EbayEnterprise_Inventory_Model_Quantity_IResults
{
    /**
     * Get a quantity record by sku.
     *
     * @param string
     * @return EbayEnterprise_Inventory_Model_Quantity|null
     */
    public function getQuantityBySku($sku);

    /**
     * Get a quantity record by item id.
     *
     * @param int
     * @return EbayEnterprise_Inventory_Model_Quantity|null
     */
    public function getQuantityByItemId($itemId);

    /**
     * Check if the results have expired. Results that have expired should
     * not be used.
     *
     * @return bool
     */
    public function isExpired();

    /**
     * Check that the sku quantity data the results were created from match
     * the given sku quantity data.
     *
     * Sku quantity data consts of key => value pairs of skus => total requested quantity.
     * This is used to determine if a set of results apply to new, potentially
     * updated quote item data. Results should only apply to a quote if the
     * sku quantity data of the current quote matches the sku quantity data the
     * results were collected for.
     *
     * @see EbayEnterprise_Inventory_Helper_Quantity::calculateTotalQuantitiesBySku for generating the required mapping from an array of items.
     *
     * @param array
     * @return bool
     */
    public function checkResultsApplyToItems(array $skuQuantityData);
}
