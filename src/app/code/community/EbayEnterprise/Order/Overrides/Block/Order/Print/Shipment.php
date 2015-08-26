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

class EbayEnterprise_Order_Overrides_Block_Order_Print_Shipment extends Mage_Sales_Block_Order_Print_Shipment
{
    /** @var EbayEnterprise_Order_Helper_Detail_Item */
    protected $itemHelper;

    /**
     * @param array $initParams May include:
     *                          - 'item_helper' => EbayEnterprise_Order_Helper_Detail_Item
     */
    public function __construct(array $initParams=[])
    {
        list($this->itemHelper) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'item_helper', Mage::helper('ebayenterprise_order/detail_item'))
        );
        unset($initParams['item_helper']);
        parent::__construct($initParams);
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Order_Helper_Detail_Item
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Order_Helper_Detail_Item $itemHelper
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Retrieve current order model instance
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('rom_order');
    }

    /**
     * Getter for billing address of order by format.
     * Will filter out any hidden gift items by default.
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param bool
     * @return array
     */
    public function getShipmentItems($shipment, $includeHidden = false)
    {
        // parent::getShipmentItems will include hidden and non-hidden items by default.
        // When hidden items are to be included, the whole array can simply be
        // returned. When hidden items should not be included, they need to
        // be filtered out of the list.
        $items = parent::getShipmentItems($shipment);
        return $includeHidden ? $items : $this->itemHelper->filterHiddenGiftItems($items);
    }
}
