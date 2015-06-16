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

class EbayEnterprise_Inventory_Model_Observer
{
    /** @var EbayEnterprise_Inventory_Model_Quantity_Service */
    protected $quantityService;
    /** @var EbayEnterprise_Inventory_Model_Details_Service */
    protected $detailsService;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Service */
    protected $allocationService;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    /**
     * @param array $args May contain:
     *                    - quantity_service => EbayEnterprise_Inventory_Model_Quantity_Service
     *                    - details_service => EbayEnterprise_Inventory_Model_Details_Service
     *                    - logger => EbayEnterprise_MageLog_Helper_Data
     *                    - log_context => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $args = [])
    {
        list(
            $this->quantityService,
            $this->detailsService,
            $this->allocationService,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce(
                $args,
                'quantity_service',
                Mage::getModel('ebayenterprise_inventory/quantity_service')
            ),
            $this->nullCoalesce($args, 'details_service', Mage::getModel('ebayenterprise_inventory/details_service')),
            $this->nullCoalesce(
                $args,
                'allocation_service',
                Mage::getModel('ebayenterprise_inventory/allocation_service')
            ),
            $this->nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Model_Quantity_Service
     * @param EbayEnterprise_Inventory_Model_Details_Service
     * @param EbayEnterprise_Inventory_Model_Allocation_Service
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Model_Quantity_Service $quantityService,
        EbayEnterprise_Inventory_Model_Details_Service $detailsService,
        EbayEnterprise_Inventory_Model_Allocation_Service $allocationService,
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
     * Before collecting item totals, check that all items
     * in the quote are available to be fulfilled.
     *
     * @param Varien_Event_Observer
     * @return self
     */
    public function handleBeforeCollectTotals(Varien_Event_Observer $observer)
    {
        try {
            $quote = $observer->getEvent()->getQuote();
            $this->quantityService
                ->checkQuoteInventory($quote);
        } catch (EbayEnterprise_Inventory_Exception_Quantity_Collector_Exception $e) {
            $this->logger->warning($e->getMessage(), $this->logContext->getMetaData(__CLASS__, [], $e));
        }
        return $this;
    }

    /**
     * add estimated shipping information to the item payload
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleEbayEnterpriseOrderCreateItem(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $itemPayload = $event->getItemPayload();
        $item = $event->getItem();
        Mage::getModel('ebayenterprise_inventory/order_create_item_details')
            ->injectShippingEstimates($itemPayload, $item);
        Mage::getModel('ebayenterprise_inventory/order_create_item_allocation')
            ->injectReservationInfo($itemPayload, $item);
        return $this;
    }

    /**
     * Inject the ship from address into the tax item payload
     *
     * @param Varien_Event_Observer
     * @return self
     */
    public function handleEbayEnterpriseTaxItemShipOrigin(Varien_Event_Observer $observer)
    {
        $item = $observer->getEvent()->getItem();
        $address = $observer->getEvent()->getAddress();
        Mage::getModel('ebayenterprise_inventory/tax_shipping_origin')
            ->injectShippingOriginForItem($item, $address);
        return $this;
    }

    /**
     * trigger inventory allocation for the quote
     *
     * @param Varien_Event_Observer
     * @return self
     */
    public function handleSalesOrderPlaceBefore(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $order->getQuote();
        $this->allocationService->allocateInventoryForQuote($quote);
        return $this;
    }

    /**
     * trigger a rollback if the order fails
     *
     * @return self
     */
    public function handleSalesModelServiceQuoteSubmitFailure()
    {
        $this->allocationService->undoAllocation();
        return $this;
    }
}
