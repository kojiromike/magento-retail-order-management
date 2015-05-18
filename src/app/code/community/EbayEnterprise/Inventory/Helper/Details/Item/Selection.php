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

class EbayEnterprise_Inventory_Helper_Details_Item_Selection implements EbayEnterprise_Inventory_Model_Item_Selection_Interface
{
    /** @var EbayEnterprise_Inventory_Model_Quantity_Service */
    protected $qtyService;
    /** @var EbayEnterprise_Inventory_Helper_Item_Selection */
    protected $selector;

    public function __construct()
    {
        list($this->qtyService, $this->selector) = $this->checkTypes(
            Mage::getModel('ebayenterprise_inventory/quantity_service'),
            Mage::helper('ebayenterprise_inventory/item_selection')
        );
    }

    /**
     * ensure dependency type
     *
     * @param EbayEnterprise_Inventory_Model_Quantity_Service
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Model_Quantity_Service $qtyService,
        EbayEnterprise_Inventory_Helper_Item_Selection $selector
    ) {
        return func_get_args();
    }

    /**
     * Select items to be sent in the request from the given array
     * based on product type.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @return Mage_Sales_Model_Quote_Item_Abstract[]
     */
    public function selectFrom(array $items)
    {
        return array_filter(
            $this->selector->selectFrom($items),
            [$this->qtyService, 'isItemAvailable']
        );
    }
}
