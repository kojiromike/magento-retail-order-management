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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;

/**
 * apply estimated shipping data to the order create request
 */
class EbayEnterprise_Inventory_Model_Order_Create_Item_Allocation
{
    // the quantity of items allocated is insufficient to complete the order.
    const INSUFFICIENT_STOCK_MESSAGE
        = 'EbayEnterprise_Inventory_Quote_Insufficient_Stock_Message';
    // the item is completely out of stock.
    const OUT_OF_STOCK_MESSAGE
        = 'EbayEnterprise_Inventory_Quote_Out_Of_Stock_Message';

    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $invHelper;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Service */
    protected $allocationService;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    /**
     * @param array $args May contain:
     *                    - helper => EbayEnterprise_Inventory_Helper_Data
     *                    - allocation_service => EbayEnterprise_Inventory_Model_Allocation_Service
     *                    - logger => EbayEnterprise_MageLog_Helper_Data
     *                    - log_context => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $init = [])
    {
        list(
            $this->invHelper,
            $this->allocationService,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce($init, 'helper', Mage::helper('ebayenterprise_inventory')),
            $this->nullCoalesce(
                $init,
                'allocation_service',
                Mage::getModel('ebayenterprise_inventory/allocation_service')
            ),
            $this->nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($init, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Helper_Data
     * @param EbayEnterprise_Inventory_Model_Allocation_Service
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Data $invHelper,
        EbayEnterprise_Inventory_Model_Allocation_Service $allocationService,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param array
     * @param string
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * add data from the inventory service to the order item
     *
     * @param  IOrderItem
     * @param  Mage_Sales_Model_Order_Item
     * @return self
     */
    public function injectReservationInfo(IOrderItem $itemPayload, Mage_Sales_Model_Order_Item $item)
    {
        $allocation = $this->getAllocationForItem($item);
        if ($allocation) {
            $itemPayload->setReservationId($allocation->getReservationId());
        }
        return $this;
    }

    /**
     * get the alloction for the item and verify
     * that the amount ordered was allocated
     *
     * @param Mage_Sales_Model_Order_Item
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Insufficient_Exception
     *         if the amount allocated is less than the amount desired.
     */
    protected function getAllocationForItem($item)
    {
        $allocation = $this->allocationService->getItemAllocationInformation($item);
        if ($allocation) {
            if ($allocation->getQuantityAllocated() === 0) {
                $this->handleOutOfStock($item);
            } elseif ($allocation->getQuantityAllocated() < $item->getQtyOrdered()) {
                $this->handleInsufficientStock($item);
            }
        }
        return $allocation;
    }

    /**
     * handle the case where an item is out of stock
     *
     * @param EbayEnterprise_Inventory_Model_Allocation
     * @param Mage_Sales_Model_Order_Item
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Availability_Exception
     */
    protected function handleOutOfStock(Mage_Sales_Model_Order_Item $item)
    {
        $this->logger->debug(
            'Item {sku} is out of stock',
            $this->logContext->getMetaData(__CLASS__, ['sku' => $item->getSku()])
        );
        throw Mage::exception(
            'EbayEnterprise_Inventory_Exception_Allocation_Availability',
            $this->invHelper->__(static::OUT_OF_STOCK_MESSAGE)
        );
    }

    /**
     * handle the case where there is not enough stock to allocate the
     * full amount requested
     *
     * @param EbayEnterprise_Inventory_Model_Allocation
     * @param Mage_Sales_Model_Order_Item
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Availability_Exception
     */
    protected function handleInsufficientStock(Mage_Sales_Model_Order_Item $item)
    {
        $this->logger->debug(
            'Unable to reserve desired quantity for item {sku}',
            $this->logContext->getMetaData(__CLASS__, ['sku' => $item->getSku()])
        );
        throw Mage::exception(
            'EbayEnterprise_Inventory_Exception_Allocation_Availability',
            $this->invHelper->__(static::INSUFFICIENT_STOCK_MESSAGE)
        );
    }
}
