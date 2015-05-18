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

class EbayEnterprise_Inventory_Model_Details_Item
{
    /** @var int */
    protected $itemId;
    /** @var string */
    protected $sku;
    /** @var DateTime */
    protected $deliveryWindowFromDate;
    /** @var DateTime */
    protected $deliveryWindowToDate;
    /** @var DateTime */
    protected $shippingWindowFromDate;
    /** @var DateTime */
    protected $shippingWindowToDate;
    /** @var DateTime */
    protected $deliveryEstimateCreationTime;
    /** @var bool */
    protected $deliveryEstimateDisplayFlag;
    /** @var string */
    protected $deliveryEstimateMessage;
    /** @var string */
    protected $shipFromLines;
    /** @var string */
    protected $shipFromCity;
    /** @var string */
    protected $shipFromMainDivision;
    /** @var string */
    protected $shipFromCountryCode;
    /** @var string */
    protected $shipFromPostalCode;
    protected $isAvailable = false;

    public function __construct(array $init)
    {
        $this->itemId = $init['item_id'];
        $this->sku = $init['sku'];
        if (count($init) > 2) {
            $this->isAvailable = true;
            $this->deliveryWindowFromDate = $init['delivery_window_from_date'];
            $this->deliveryWindowToDate = $init['delivery_window_to_date'];
            $this->shippingWindowFromDate = $init['shipping_window_from_date'];
            $this->shippingWindowToDate = $init['shipping_window_to_date'];
            $this->deliveryEstimateCreationTime = $init['delivery_estimate_creation_time'];
            $this->deliveryEstimateDisplayFlag = $init['delivery_estimate_display_flag'];
            $this->deliveryEstimateMessage = $init['delivery_estimate_message'];
            $this->shipFromLines = $init['ship_from_lines'];
            $this->shipFromCity = $init['ship_from_city'];
            $this->shipFromMainDivision = $init['ship_from_main_division'];
            $this->shipFromCountryCode = $init['ship_from_country_code'];
            $this->shipFromPostalCode = $init['ship_from_postal_code'];
        }
    }

    /**
     * is the item available
     * @return bool
     */
    public function isAvailable()
    {
        return $this->isAvailable;
    }

    /**
     * Identifier for an order item.
     *
     * restrictions: 1 <= length <= 39, unique in request
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * identifier for an inventoriable product. a.k.a. SKU
     *
     * restrictions: length <= 20
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * The earliest date when the order line item is expected to arrive at the ship-to address.
     *
     * @return DateTime
     */
    public function getDeliveryWindowFromDate()
    {
        return $this->deliveryWindowFromDate;
    }

    /**
     * The latest date when the order line item is expected to arrive at the ship-to address.
     *
     * @return DateTime
     */
    public function getDeliveryWindowToDate()
    {
        return $this->deliveryWindowToDate;
    }

    /**
     * The earliest date when the order line item is expected to leave the fulfillment node.
     *
     * @return DateTime
     */
    public function getShippingWindowFromDate()
    {
        return $this->shippingWindowFromDate;
    }

    /**
     * The latest date when the order line item is expected to leave the fulfillment node.
     *
     * @return DateTime
     */
    public function getShippingWindowToDate()
    {
        return $this->shippingWindowToDate;
    }

    /**
     * The date-time when this delivery estimate was created
     *
     * @return DateTime
     */
    public function getDeliveryEstimateCreationTime()
    {
        return $this->deliveryEstimateCreationTime;
    }

    /**
     * Indicates if the delivery estimate should be displayed.
     *
     * @return DateTime
     */
    public function getDeliveryEstimateDisplayFlag()
    {
        return $this->deliveryEstimateDisplayFlag;
    }

    /**
     * not currently used.
     *
     * restrictions: optional
     * @return string
     */
    public function getDeliveryEstimateMessage()
    {
        return $this->deliveryEstimateMessage;
    }

    /**
     * The street address and/or suite and building
     *
     * Newline-delimited string, at most four lines
     * restriction: 1-70 characters per line
     * @return string
     */
    public function getShipFromLines()
    {
        return $this->shipFromLines;
    }

    /**
     * Name of the city
     *
     * restriction: 1-35 characters
     * @return string
     */
    public function getShipFromCity()
    {
        return $this->shipFromCity;
    }

    /**
     * Typically a two- or three-digit postal abbreviation for the state or province.
     * ISO 3166-2 code is recommended, but not required
     *
     * restriction: 1-35 characters
     * @return string
     */
    public function getShipFromMainDivision()
    {
        return $this->shipFromMainDivision;
    }

    /**
     * Two character country code.
     *
     * restriction: 2-40 characters
     * @return string
     */
    public function getShipFromCountryCode()
    {
        return $this->shipFromCountryCode;
    }

    /**
     * Typically, the string of letters and/or numbers that more closely
     * specifies the delivery area than just the City component alone,
     * for example, the Zip Code in the U.S.
     *
     * restriction: 1-15 characters
     * @return string
     */
    public function getShipFromPostalCode()
    {
        return $this->shipFromPostalCode;
    }
}
