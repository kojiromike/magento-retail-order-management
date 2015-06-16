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
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAllocationRequest;

class EbayEnterprise_Inventory_Model_Allocation_Creator_Request_Builder extends
 EbayEnterprise_Inventory_Model_Details_Request_Builder_Abstract
{
    /** @var IAllocationRequest */
    protected $request;
    /** @var EbayEnterprise_Inventory_Helper_Details_Item */
    protected $itemHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Item_Selection */
    protected $selectedItems;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Reservation */
    protected $reservation;

    public function __construct(array $init = [])
    {
        list(
            $this->request,
            $this->selectedItems,
            $this->reservation,
            $this->itemHelper,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $init['request'],
            $init['selected_items'],
            $init['reservation'],
            $this->nullCoalesce('item_helper', $init, Mage::helper('ebayenterprise_inventory/details_item')),
            $this->nullCoalesce('logger', $init, Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce('log_context', $init, Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    protected function checkTypes(
        IAllocationRequest $request,
        EbayEnterprise_Inventory_Model_Allocation_Item_Selector $selectedItems,
        EbayEnterprise_Inventory_Model_Allocation_Reservation $reservation,
        EbayEnterprise_Inventory_Helper_Details_Item $itemHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $loggerContext
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param  string
     * @param  array
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce($key, array $arr, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    public function buildOutRequest()
    {
        $this->request->setReservationId($this->reservation->getId())
            ->setRequestId($this->reservation->getRequestId());

        foreach ($this->selectedItems->getSelectionIterator() as $data) {
            list($address, $item) = $data;
            $this->addItemPayload($item, $address);
        }
    }
}
