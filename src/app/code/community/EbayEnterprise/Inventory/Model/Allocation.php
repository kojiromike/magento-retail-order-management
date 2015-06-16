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
 * information related to an item's allocation
 */
class EbayEnterprise_Inventory_Model_Allocation
{
    protected $sku;
    protected $itemId;
    protected $quantityAllocated;
    protected $reservation;

    public function __construct(array $init = [])
    {
        list($this->sku, $this->itemId, $this->quantityAllocated, $this->reservation)
            = $this->checkTypes(
                $init['sku'],
                $init['item_id'],
                $init['quantity_allocated'],
                $init['reservation']
            );
    }

    protected function checkTypes(
        $sku,
        $itemId,
        $quantityAllocated,
        EbayEnterprise_Inventory_Model_Allocation_Reservation $reservation
    ) {
        return func_get_args();
    }

    /**
     * Get the quote item id for the allocated item.
     *
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * get the SKU for the item
     *
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * get the quantity of items allocated
     *
     * @return int
     */
    public function getQuantityAllocated()
    {
        return $this->quantityAllocated;
    }

    /**
     * get the id for the item's reservation
     *
     * @return string
     */
    public function getReservationId()
    {
        return $this->reservation->getId();
    }
}
