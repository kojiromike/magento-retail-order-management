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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IRequestQuantityItem;

class EbayEnterprise_Inventory_Helper_Quantity_Payload
{
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $inventoryHelper;
    /**
     * $param array $args May contain:
     *                    - inventory_helper => EbayEnterprise_Inventory_Helper_Data
     */
    public function __construct(array $args = [])
    {
        list($this->inventoryHelper) = $this->checkTypes(
            $this->nullCoalesce($args, 'inventory_helper', Mage::helper('ebayenterprise_inventory'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Helper_Data
     * @return array
     */
    protected function checkTypes(EbayEnterprise_Inventory_Helper_Data $inventoryHelper)
    {
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
     * Transfer data from a quote item to a quantity
     * request item payload.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @param IRequestQuantityItem
     * @return IRequestQuantityItem
     */
    public function itemToRequestQuantityItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        IRequestQuantityItem $itemPayload
    ) {
        return $this->itemToQuantityItem($item, $itemPayload)
            ->setFulfillmentLocationId($item->getFulfillmentLocationId())
            ->setFulfillmentLocationType($item->getFulfillmentLocationType());
    }

    /**
     * Transfer data from a quote item to a quantity
     * request item payload.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @param IQuantityItem
     * @return IQuantityItem
     */
    public function itemToQuantityItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        IQuantityItem $itemPayload
    ) {
        return $itemPayload->setItemId($this->inventoryHelper->getRomSku($item->getSku()))
            ->setLineId($item->getId());
    }
}
