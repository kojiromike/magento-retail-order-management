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

class EbayEnterprise_Inventory_Model_Quantity_Collector
{
    /** @var EbayEnterprise_Inventory_Helper_Quantity_Sdk */
    protected $_quantitySdkHelper;
    /** @var EbayEnterprise_Inventory_Helper_Quantity */
    protected $_quantityHelper;
    /** @var EbayEnterprise_Inventory_Helper_Item_Selection */
    protected $_itemSelection;
    /** @var EbayEnterprise_Inventory_Model_Session */
    protected $_inventorySession;

    /**
     * $param array $args May contain:
     *                    - sdk_helper => EbayEnterprise_Inventory_Helper_Quantity_Sdk
     *                    - quantity_helper => EbayEnterprise_Inventory_Helper_Quantity
     *                    - item_selection => EbayEnterprise_Inventory_Helper_Item_Selection
     *                    - inventory_session => EbayEnterprise_Inventory_Model_Session
     */
    public function __construct(array $args = [])
    {
        list(
            $this->_quantitySdkHelper,
            $this->_quantityHelper,
            $this->_itemSelection,
            $this->_inventorySession
        ) = $this->_checkTypes(
            $this->_nullCoalesce($args, 'quantity_sdk_helper', Mage::helper('ebayenterprise_inventory/quantity_sdk')),
            $this->_nullCoalesce($args, 'quantity_helper', Mage::helper('ebayenterprise_inventory/quantity')),
            $this->_nullCoalesce($args, 'item_selection', Mage::helper('ebayenterprise_inventory/item_selection')),
            $this->_nullCoalesce($args, 'inventory_session', null)
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Helper_Quantity_Sdk
     * @param EbayEnterprise_Inventory_Helper_Quantity
     * @param EbayEnterprise_Inventory_Helper_Item_Selection
     * @param EbayEnterprise_Inventory_Model_Session
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Inventory_Helper_Quantity_Sdk $quantitySdkHelper,
        EbayEnterprise_Inventory_Helper_Quantity $quantityHelper,
        EbayEnterprise_Inventory_Helper_Item_Selection $itemSelection,
        EbayEnterprise_Inventory_Model_Session $inventorySession = null
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
    protected function _nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Get quantity results for a quote.
     *
     * @param Mage_Sales_Model_Quote
     * @return EbayEnterprise_Inventory_Model_Quantity_Results
     */
    public function getQuantityResultsForQuote(Mage_Sales_Model_Quote $quote)
    {
        $items = $this->_itemSelection->selectFrom($quote->getAllItems());
        return $this->_getSessionResults($items) ?: $this->_requestNewResults($items);
    }

    /**
     * Remove any stored inventory quantity results from storage.
     *
     * @return self
     */
    public function clearResults()
    {
        $this->_getInventorySession()->setQuantityResults(null);
        return $this;
    }

    /**
     * Get existing quantity results from the session. Will only return results
     * if they are valid and apply to the current state of the provided items.
     *
     * @param Mage_Sales_Model_Quote_Item[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Restults|null
     */
    protected function _getSessionResults(array $items)
    {
        $results = $this->_getInventorySession()->getQuantityResults();
        $skuQuantityData = $this->_quantityHelper->calculateTotalQuantitiesBySku($items);
        return $results && $results->checkResultsApplyToItems($skuQuantityData)
            ? $this->_getInventorySession()->getQuantityResults()
            : null;
    }

    /**
     * Request new quantity results.
     *
     * @param Mage_Sales_Model_Quote_Item[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Restults
     */
    protected function _requestNewResults($items)
    {
        $results = $this->_quantitySdkHelper->requestQuantityForItems($items);
        $this->_getInventorySession()->setQuantityResults($results);
        return $results;
    }

    /**
     * Get the inventory session model.
     *
     * @return EbayEnterprise_Inventory_Model_Session
     */
    protected function _getInventorySession()
    {
        if (is_null($this->_inventorySession)) {
            $this->_inventorySession = Mage::getSingleton('ebayenterprise_inventory/session');
        }
        return $this->_inventorySession;
    }
}
