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
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IEstimatedDeliveryDate;

/**
 * apply estimated shipping data to the order create request
 */
class EbayEnterprise_Inventory_Model_Order_Create_Item_Details
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;
    /** @var EbayEnterprise_Inventory_Model_Details_Service */
    protected $detailService;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Inventory_Model_Edd */
    protected $edd;

    /**
     * @param array $args May contain:
     *                    - config => EbayEnterprise_Eb2cCore_Model_Config_Registry
     *                    - details_service => EbayEnterprise_Inventory_Model_Details_Service
     *                    - edd => EbayEnterprise_Inventory_Model_Edd
     */
    public function __construct(array $args = [])
    {
        list(
            $this->helper,
            $this->detailService,
            $this->edd
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'helper', Mage::helper('ebayenterprise_inventory')),
            $this->nullCoalesce($args, 'detail_service', Mage::getModel('ebayenterprise_inventory/details_service')),
            $this->nullCoalesce($args, 'edd', Mage::getModel('ebayenterprise_inventory/edd'))
        );
        list($this->config) = $this->checkConfigTypes($this->nullCoalesce($args, 'config', $this->helper->getConfigModel()));
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Helper_Data
     * @param EbayEnterprise_Inventory_Model_Details_Service
     * @param EbayEnterprise_Inventory_Model_Edd
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Data $helper,
        EbayEnterprise_Inventory_Model_Details_Service $detailService,
        EbayEnterprise_Inventory_Model_Edd $edd
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
     * Enforce type checks for the config key in the constructors parameter array $args.
     *
     * @param EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function checkConfigTypes(EbayEnterprise_Eb2cCore_Model_Config_Registry $config)
    {
        return [$config];
    }

    /**
     * add data from the inventory service to the order item
     * @param  IOrderItem
     * @param  Mage_Sales_Model_Order_Item
     * @return self
     */
    public function injectShippingEstimates(IOrderItem $itemPayload, Mage_Sales_Model_Order_Item $item)
    {
        $detail = $this->detailService->getDetailsForOrderItem($item)
            ?: $this->helper->getOcrBackorderableEddData($item);
        if ($detail && $detail->isAvailable()) {
            $itemPayload
                ->setEstimatedDeliveryMode(IEstimatedDeliveryDate::MODE_ENABLED)
                ->setEstimatedDeliveryMessageType(IEstimatedDeliveryDate::MESSAGE_TYPE_DELIVERYDATE)
                ->setEstimatedDeliveryTemplate($this->edd->getEddTemplate());
            $this->handleDateFields($itemPayload, $detail);
        }
        return $this;
    }

    /**
     * selectively set date fields only if they have data
     *
     * @param  IOrderItem
     * @param  Mage_Sales_Model_Order_Item
     * @return self
     */
    protected function handleDateFields(IOrderItem $payload, EbayEnterprise_Inventory_Model_Details_Item $detail)
    {
        $setters = array_filter([
            'setEstimatedDeliveryWindowFrom' => $detail->getDeliveryWindowFromDate(),
            'setEstimatedDeliveryWindowTo' => $detail->getDeliveryWindowToDate(),
            'setEstimatedShippingWindowFrom' => $detail->getShippingWindowFromDate(),
            'setEstimatedShippingWindowTo' => $detail->getShippingWindowToDate(),
        ]);
        foreach ($setters as $method => $value) {
            $payload->$method($value);
        }
        return $this;
    }
}
