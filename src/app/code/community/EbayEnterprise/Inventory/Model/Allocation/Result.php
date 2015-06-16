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
 * container for item allocation results
 */
class EbayEnterprise_Inventory_Model_Allocation_Result
{
    /** @var EbayEnterprise_Inventory_Model_Allocation[] */
    protected $allocations = [];
    /** @var EbayEnterprise_Inventory_Model_Allocation[] */
    protected $resultsById = [];
    /** @var EbayEnterprise_Inventory_Model_Allocation_Reservation */
    protected $reservation;

    public function __construct(array $init = [])
    {
        list($this->reservation, $allocations) =
            $this->checkTypes(
                $init['reservation'],
                $this->nullCoalesce($init, 'allocations', [])
            );
        $this->allocations = $this->convertToModels($allocations);
    }

    /**
     * ensure dependency types
     *
     * @param array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Model_Allocation_Reservation $reservation,
        array $allocations
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
     * get the item allocation information for the specified
     * item id.
     * null is returned if not found
     *
     * @param int
     * @return EbayEnterprise_Inventory_Allocation
     */
    public function lookupAllocationByItemId($itemId)
    {
        if (!$this->resultsById) {
            $this->resultsById = $this->mapResultsByItemId();
        }
        return $this->nullCoalesce($this->resultsById, $itemId, null);
    }

    /**
     * get all allocations
     *
     * @return array
     */
    public function getAllocations()
    {
        return $this->allocations;
    }

    /**
     * get the reservation the results are for
     *
     * @return EbayEnterprise_Inventory_Allocation_Reservation
     */
    public function getReservation()
    {
        return $this->reservation;
    }

    /**
     * convert data extracted from the response to models
     *
     * @param array
     * @return EbayEnterprise_Inventory_Model_Allocation[]
     */
    protected function convertToModels(array $extractedData)
    {
        return array_map($this->getAllocationFactoryCallback(), $extractedData);
    }

    /**
     * the callback used to create an allocation object.
     *
     * @return callable
     */
    protected function getAllocationFactoryCallback()
    {
        return function (array $allocationData) {
            return $this->createAllocation($allocationData);
        };
    }

    /**
     * factory method to build an allocation model
     *
     * @param array
     * @return EbayEnterprise_Inventory_Model_Allocation
     */
    protected function createAllocation(array $allocationData)
    {
        return Mage::getModel('ebayenterprise_inventory/allocation', $allocationData);
    }

    /**
     * get the results as a single list of detail objects mapped to
     * the quote item id.
     */
    protected function mapResultsByItemId()
    {
        $resultRecords = [];
        foreach ($this->getAllocations() as $allocation) {
            $resultRecords[$allocation->getItemId()] = $allocation;
        }
        return $resultRecords;
    }
}
