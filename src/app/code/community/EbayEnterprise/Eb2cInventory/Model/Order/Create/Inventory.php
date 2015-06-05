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
class EbayEnterprise_Eb2cInventory_Model_Order_Create_Inventory
{
    /** @var EbayEnterprise_Eb2cInventory_Helper_Data */
    protected $_helper;

    /**
     * inject dependencies
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        list($this->_helper) =
            $this->_checkTypes(
                $this->_nullCoalesce('helper', $args, Mage::helper('eb2cinventory'))
            );
    }

    /**
     * ensure correct types
     * @param  EbayEnterprise_Eb2cInventory_Helper_Data $helper
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Eb2cInventory_Helper_Data $helper
    ) {
        return [$helper];
    }

    /**
     * add data from the inventory service to the order item
     * @param  IOrderItem                  $itemPayload
     * @param  Mage_Sales_Model_Order_Item $item
     * @return self
     */
    public function injectShippingEstimates(IOrderItem $itemPayload, Mage_Sales_Model_Order_Item $item)
    {
        $config = $this->_helper->getConfigModel();
        $itemPayload
            ->setEstimatedDeliveryMode(IEstimatedDeliveryDate::MODE_LEGACY)
            ->setEstimatedDeliveryMessageType(IEstimatedDeliveryDate::MESSAGE_TYPE_DELIVERYDATE)
            ->setEstimatedDeliveryTemplate($config->estimatedDeliveryTemplate)
            ->setReservationId($item->getEb2cReservationId());

        return $this->_handleDateFields($itemPayload, $item);
    }

    /**
     * selectively set date fields only if they have data
     *
     * @param  IOrderItem                  $payload
     * @param  Mage_Sales_Model_Order_Item $item
     * @return self
     */
    protected function _handleDateFields(IOrderItem $payload, Mage_Sales_Model_Order_Item $item)
    {
        $setters = array_filter([
            'setEstimatedDeliveryWindowFrom' => $this->_getAsDateTime($item->getEb2cDeliveryWindowFrom()),
            'setEstimatedDeliveryWindowTo' => $this->_getAsDateTime($item->getEb2cDeliveryWindowTo()),
            'setEstimatedShippingWindowFrom' => $this->_getAsDateTime($item->getEb2cShippingWindowFrom()),
            'setEstimatedShippingWindowTo' => $this->_getAsDateTime($item->getEb2cShippingWindowTo()),
        ]);
        foreach ($setters as $method => $value) {
            $payload->$method($value);
        }
        return $this;
    }

    /**
     * return a DateTime for the provided string.
     * return null if the string is invalid or
     * @param  string   $dateString
     * @return DateTime
     */
    protected function _getAsDateTime($dateString)
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        return $d ?: null;
    }

    /**
     * return $ar[$key] if it exists otherwise return $default
     * @param  string $key
     * @param  array  $ar
     * @param  mixed  $default
     * @return mixed
     */
    protected function _nullCoalesce($key, array $ar, $default)
    {
        return isset($ar[$key]) ? $ar[$key] : $default;
    }
}
