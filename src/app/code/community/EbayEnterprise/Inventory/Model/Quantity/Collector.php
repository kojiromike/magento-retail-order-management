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
use Psr\Log\LoggerInterface;

class EbayEnterprise_Inventory_Model_Quantity_Collector
{
    /** @var EbayEnterprise_Inventory_Helper_Quantity_Sdk */
    protected $quantitySdkHelper;
    /** @var EbayEnterprise_Inventory_Helper_Quantity */
    protected $quantityHelper;
    /** @var EbayEnterprise_Inventory_Helper_Item_Selection */
    protected $itemSelection;
    /** @var EbayEnterprise_Inventory_Model_Quantity_Results_IStorage */
    protected $inventorySession;
    /** @var LoggerInterface */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    /**
     * $param array $args May contain:
     *                    - sdk_helper => EbayEnterprise_Inventory_Helper_Quantity_Sdk
     *                    - quantity_helper => EbayEnterprise_Inventory_Helper_Quantity
     *                    - item_selection => EbayEnterprise_Inventory_Helper_Item_Selection
     *                    - inventory_session => EbayEnterprise_Inventory_Model_Quantity_Results_IStorage
     *                    - logger => LoggerInterface
     *                    - log_context => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $args = [])
    {
        list(
            $this->quantitySdkHelper,
            $this->quantityHelper,
            $this->itemSelection,
            $this->logger,
            $this->logContext,
            $this->inventorySession
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'quantity_sdk_helper', Mage::helper('ebayenterprise_inventory/quantity_sdk')),
            $this->nullCoalesce($args, 'quantity_helper', Mage::helper('ebayenterprise_inventory/quantity')),
            $this->nullCoalesce($args, 'item_selection', Mage::helper('ebayenterprise_inventory/item_selection')),
            $this->nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($args, 'inventory_session', null)
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Helper_Quantity_Sdk
     * @param EbayEnterprise_Inventory_Helper_Quantity
     * @param EbayEnterprise_Inventory_Helper_Item_Selection
     * @param LoggerInterface $logger
     * @param EbayEnterprise_MageLog_Helper_Context $logContext
     * @param EbayEnterprise_Inventory_Model_Quantity_Results_IStorage|null
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Quantity_Sdk $quantitySdkHelper,
        EbayEnterprise_Inventory_Helper_Quantity $quantityHelper,
        EbayEnterprise_Inventory_Helper_Item_Selection $itemSelection,
        LoggerInterface $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext,
        EbayEnterprise_Inventory_Model_Quantity_Results_IStorage $inventorySession = null
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
     * Get quantity results for a quote.
     *
     * @param Mage_Sales_Model_Quote
     * @return EbayEnterprise_Inventory_Model_Quantity_Results
     */
    public function getQuantityResultsForQuote(Mage_Sales_Model_Quote $quote)
    {
        $items = $this->itemSelection->selectFrom($quote->getAllItems());
        return $this->getSessionResults($items) ?: $this->requestNewResults($items);
    }

    /**
     * Remove any stored inventory quantity results from storage.
     *
     * @return self
     */
    public function clearResults()
    {
        $this->getInventorySession()->setQuantityResults(null);
        return $this;
    }

    /**
     * Get existing quantity results from the session. Will only return results
     * if they are valid and apply to the current state of the provided items.
     *
     * @param Mage_Sales_Model_Quote_Item[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Restults|null
     */
    protected function getSessionResults(array $items)
    {
        $results = $this->getInventorySession()->getQuantityResults();
        $skuQuantityData = $this->quantityHelper->calculateTotalQuantitiesBySku($items);
        return $results && $results->checkResultsApplyToItems($skuQuantityData)
            ? $this->getInventorySession()->getQuantityResults()
            : null;
    }

    /**
     * Request new quantity results.
     *
     * @param Mage_Sales_Model_Quote_Item[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Restults
     */
    protected function requestNewResults($items)
    {
        $results = $this->quantitySdkHelper->requestQuantityForItems($items);
        $this->getInventorySession()->setQuantityResults($results);
        return $results;
    }

    /**
     * Get the inventory session model.
     *
     * @return EbayEnterprise_Inventory_Model_Quantity_Results_IStorage
     */
    protected function getInventorySession()
    {
        if (is_null($this->inventorySession)) {
            $this->inventorySession = Mage::getSingleton('ebayenterprise_inventory/session');
        }
        return $this->inventorySession;
    }
}
