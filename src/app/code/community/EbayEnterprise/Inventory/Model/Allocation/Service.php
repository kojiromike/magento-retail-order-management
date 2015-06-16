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

class EbayEnterprise_Inventory_Model_Allocation_Service
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Inventory_Model_Session */
    protected $inventorySession;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $invHelper;

    public function __construct($init = [])
    {
        list($this->invHelper, $this->logger, $this->logContext) =
            $this->checkTypes(
                $this->nullCoalesce($init, 'inv_helper', Mage::helper('ebayenterprise_inventory')),
                $this->nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
                $this->nullCoalesce($init, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
            );
    }

    /**
     * enforce types
     *
     * @param  EbayEnterprise_Inventory_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @param  EbayEnterprise_Inventory_Helper_Details_Factory
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Data $invHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
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
     * get allocation information for an item
     *
     * @param  Mage_Sales_Model_Order_Item
     * @return EbayEnterprise_Inventory_Model_Allocation|null
     */
    public function getItemAllocationInformation(Mage_Sales_Model_Order_Item $item)
    {
        $result = $this->getInventorySession()->getAllocationResult();
        if ($result) {
            return $result->lookupAllocationByItemId($item->getQuoteItemId());
        }
        $this->logger->debug(
            'Unable to get allocation information for item {sku} id {item_id}',
            $this->logContext->getMetaData(
                __CLASS__,
                ['sku' => $item->getSku(), 'item_id' => $item->getQuoteItemId()]
            )
        );
        return null;
    }

    /**
     * get the inventory session
     *
     * @return EbayEnterprise_Inventory_Model_Session
     */
    protected function getInventorySession()
    {
        if (!$this->inventorySession) {
            $this->inventorySession = Mage::getSingleton('ebayenterprise_inventory/session');
        }
        return $this->inventorySession;
    }

    /**
     * reserve items for the quote
     *
     * @param Mage_Sales_Model_Quote
     */
    public function allocateInventoryForQuote(Mage_Sales_Model_Quote $quote)
    {
        try {
            $this->getInventorySession()->setAllocationResult(
                $this->createAllocator()->reserveItemsForQuote($quote)
            );
        } catch (EbayEnterprise_Inventory_Exception_Allocation_Failure_Exception $e) {
            // clear any remnant of a result
            $this->getInventorySession()->unsAllocationResult();
            $this->logger->warning(
                'Unable to allocate inventory for the quote',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        }
    }

    /**
     * create object to allocate inventory
     *
     * @return EbayEnterprise_Inventory_Model_Allocation_Allocator
     */
    protected function createAllocator()
    {
        return Mage::getModel('ebayenterprise_inventory/allocation_allocator');
    }

    /**
     * undo the the allocation
     *
     * @return self
     */
    public function undoAllocation()
    {
        $result = $this->getInventorySession()->getAllocationResult();
        if ($result) {
            $reservation = $result->getReservation();
            $this->logger->info(
                'Undo item allocation "{reservation_id}"',
                $this->logContext->getMetaData(__CLASS__, ['reservation_id' => $reservation->getId()])
            );
            $this->createDeallocator()->rollback($reservation);
        }
        $this->getInventorySession()->unsAllocationResult();
        return $this;
    }

    /**
     * create object to undo an allocation
     *
     * @return EbayEnterprise_Inventory_Model_Allocation_Deallocator
     */
    protected function createDeallocator()
    {
        return Mage::getModel('ebayenterprise_inventory/allocation_deallocator');
    }
}
