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

use eBayEnterprise\RetailOrderManagement\Payload\IPayload;

class EbayEnterprise_Order_Helper_Detail_Map extends EbayEnterprise_Order_Helper_Map_Abstract
{
    /**
     * Get a boolean value.
     *
     * @param  IPayload
     * @param  string
     * @return bool
     */
    public function getBooleanValue(IPayload $payload, $getter)
    {
        return $this->_coreHelper->parseBool($this->_getValue($payload, $getter));
    }

    /**
     * Recursively get the value from top payloads to sub-payloads.
     *
     * @param  IPayload
     * @param  string
     * @return mixed
     */
    protected function _getValue(IPayload $payload, $getter)
    {
        $getters = array_filter(explode('/', $getter));
        $size = count($getters);
        $method = array_shift($getters);
        return $size > 1
            ? $this->_getValue($payload->{$method}(), implode('/', $getters))
            : $payload->$method();
    }

    /**
     * Get a string value.
     *
     * @param  IPayload
     * @param  string
     * @return string
     */
    public function getStringValue(IPayload $payload, $getter)
    {
        return (string) $this->_getValue($payload, $getter);
    }

    /**
     * Get a value if value is not an array cast it to an array otherwise simply return the array value.
     *
     * @param  IPayload
     * @param  string
     * @return array
     */
    public function getArrayValue(IPayload $payload, $getter)
    {
        $value = $this->_getValue($payload, $getter);
        return is_array($value) ? $value : (array) $value;
    }

    /**
     * Get a value if value and cast it to a float value.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    public function getFloatValue(IPayload $payload, $getter)
    {
        return (float) $this->_getValue($payload, $getter);
    }

    /**
     * Get a DateTime value.
     *
     * @param  IPayload
     * @param  string
     * @return string | null
     */
    public function getDateTimeValue(IPayload $payload, $getter)
    {
        $value = $this->_getValue($payload, $getter);
        return $value instanceof DateTime ? $value->format('c') : null;
    }

    /**
     * Assuming the passing getter string parameter is pipe delimited, simply explode it into an array
     * of methods in which to loop through and get the value into an array of values.
     *
     * @param  IPayload
     * @param  string
     * @return array
     */
    public function getListAsArray(IPayload $payload, $getter)
    {
        $values = [];
        $getters = array_filter(explode('|', $getter));
        foreach ($getters as $methods) {
            $value = $this->_getValue($payload, $methods);
            if ($value) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * Sum up merchandise tax prices and shipping tax prices.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    public function getOrderTotalTax(IPayload $payload, $getter)
    {
        /** @var float */
        $total = 0;
        /** @var IOrderResponse $order */
        $order = $payload->getOrder();
        /** @var IOrderDetailItemIterable $items */
        $items = $order->getOrderDetailItems();
        /** @var OrderDetailItem $item */
        foreach ($items as $item) {
            $total += $this->getItemOrderTaxAmount($item, $getter);
        }
        return $total;
    }

    /**
     * Sum up merchandise tax prices and shipping tax prices.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    public function getItemOrderTaxAmount(IPayload $payload, $getter)
    {
        /** @var ITaxIterable $merchandiseTaxes */
        $merchandiseTaxes = $payload->getMerchandisePricing()->getTaxes();
        /** @var ITaxIterable $shippingTaxes */
        $shippingTaxes = $payload->getShippingPricing()->getTaxes();
        return $this->_sumTotals($merchandiseTaxes, $getter)
            + $this->_sumTotals($shippingTaxes, $getter);
    }

    /**
     * Sum up taxes.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    protected function _sumTotals(IPayload $payload, $getter)
    {
        /** @var float */
        $sum = 0;
        /** @var IPayload $sub */
        foreach ($payload as $sub) {
            $sum += $this->getFloatValue($sub, $getter);
        }
        return $sum;
    }

    /**
     * Sum up merchandise amounts.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    public function getOrderSubTotal(IPayload $payload, $getter)
    {
        /** @var float */
        $total = 0;
        /** @var IOrderResponse $order */
        $order = $payload->getOrder();
        /** @var IOrderDetailItemIterable $items */
        $items = $order->getOrderDetailItems();
        /** @var OrderDetailItem $item */
        foreach ($items as $item) {
            /** @var IPriceGroup $merchandise */
            $merchandise = $item->getMerchandisePricing();
            $total += $this->getFloatValue($merchandise, $getter);
        }
        return $total;
    }

    /**
     * Sum up shipping amounts.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    public function getShippingTotal(IPayload $payload, $getter)
    {
        /** @var float */
        $total = 0;
        /** @var IOrderResponse $order */
        $order = $payload->getOrder();
        /** @var IOrderDetailItemIterable $items */
        $items = $order->getOrderDetailItems();
        /** @var OrderDetailItem $item */
        foreach ($items as $item) {
            /** @var IPriceGroup $shipping */
            $shipping = $item->getShippingPricing();
            $total += $this->getFloatValue($shipping, $getter);
        }
        return $total;
    }

    /**
     * Sum up merchandise discount amount and shipping discount amounts.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    public function getDiscountTotal(IPayload $payload, $getter)
    {
        /** @var float */
        $total = 0;
        /** @var IOrderResponse $order */
        $order = $payload->getOrder();
        /** @var IOrderDetailItemIterable $items */
        $items = $order->getOrderDetailItems();
        /** @var OrderDetailItem $item */
        foreach ($items as $item) {
            $total += $this->getItemDiscountAmount($item, $getter);
        }
        return $total;
    }

    /**
     * Returns the sum of an item merchandise discount amount and shipping discount amounts.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    public function getItemDiscountAmount(IPayload $payload, $getter)
    {
        /** @var IDiscountIterable $merchandiseDiscounts */
        $merchandiseDiscounts = $payload->getMerchandisePricing()->getDiscounts();
        /** @var IDiscountIterable $shippingDiscount */
        $shippingDiscount = $payload->getShippingPricing()->getDiscounts();
        return $this->_sumTotals($merchandiseDiscounts, $getter)
            + $this->_sumTotals($shippingDiscount, $getter);
    }

    /**
     * Get shipment data.
     *
     * @param  IPayload
     * @param  string
     * @return array
     */
    public function getShipmentTracks(IPayload $payload, $getter)
    {
        /** @var array */
        $data = [];
        /** @var IShippedItemIterable $shippedItems */
        $shippedItems = $payload->getShippedItems();
        /** @var IShippedItem $shippedItem */
        foreach ($shippedItems as $shippedItem) {
            /** @var IOrderDetailTrackingNumberIterable $trackingNumbers */
            $trackingNumbers = $shippedItem->getOrderDetailTrackingNumbers();
            array_merge($data, $this->_getIterableValuesAsArray($trackingNumbers, $getter));
        }
        return $data;
    }

    /**
     * Loop through each tracking iterable payload and add the tracking
     * number to a new array index.
     *
     * @param  IPayload
     * @param  string
     * @return float
     */
    protected function _getIterableValuesAsArray(IPayload $payload, $getter)
    {
        /** @var array */
        $data = [];
        /** @var IPayload $sub */
        foreach ($payload as $sub) {
            $data[] = $this->getStringValue($sub, $getter);
        }
        return $data;
    }

    /**
     * Get shipped items data.
     *
     * @param  IPayload
     * @param  string
     * @return array
     */
    public function getShippedItems(IPayload $payload, $getter)
    {
        /** @var IShippedItemIterable $shippedItems */
        $shippedItems = $payload->getShippedItems();
        return $this->_getIterableValuesAsArray($shippedItems, $getter);
    }

    /**
     * Get ship group order items data.
     *
     * @param  IPayload
     * @param  string
     * @return array
     */
    public function getShipGroupItemsAsArray(IPayload $payload, $getter)
    {
        /** @var IOrderItemReferenceIterable $orderItems */
        $orderItems = $payload->getItemReferences();
        return $this->_getIterableValuesAsArray($orderItems, $getter);
    }
}
