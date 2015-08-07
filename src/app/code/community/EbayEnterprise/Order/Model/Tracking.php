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

class EbayEnterprise_Order_Model_Tracking
{
    /** @var Mage_Sales_Model_Order */
    protected $order;
    /** @var string */
    protected $shipmentId;
    /** @var string | null */
    protected $trackingNumber;
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $factory;
    /** @var Mage_Shipping_Model_Config */
    protected $shippingConfig;

    /**
     * @param array $initParams Must have these keys:
     *                          - 'order' => Mage_Sales_Model_Order
     *                          - 'shipment_id' => string
     *                          - 'tracking_number' => string | null
     */
    public function __construct(array $initParams)
    {
        list($this->factory, $this->shippingConfig, $this->order, $this->shipmentId, $this->trackingNumber) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory')),
            $this->nullCoalesce($initParams, 'shipping_config', Mage::getModel('shipping/config')),
            $initParams['order'],
            $initParams['shipment_id'],
            $initParams['tracking_number']
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Order_Helper_Factory
     * @param  Mage_Shipping_Model_Config
     * @param  Mage_Sales_Model_Order
     * @param  string
     * @param  string | null
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Order_Helper_Factory $factory,
        Mage_Shipping_Model_Config $shippingConfig,
        Mage_Sales_Model_Order $order,
        $shipmentId,
        $trackingNumber=null
    )
    {
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
     * Build an array of tracking data for shipments in ROM order.
     *
     * @return array
     */
    public function getTrackingData()
    {
        /** @var array */
        $data = [];
        /** @var Varien_Data_Collection */
        $shipments = $this->order->getShipmentsCollection();
        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Shipment */
        $shipment = $shipments->getItemByColumnValue('increment_id', $this->shipmentId);
        if ($shipment) {
            $data[] = $this->extractTrackingDataFromShipment($shipment);
        } else {
            foreach ($shipments as $shipment) {
                $data[] = $this->extractTrackingDataFromShipment($shipment);
            }
        }
        return $data;
    }

    /**
     * Extracts tracking informations from the passed shipment object.
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
     * @return array
     */
    protected function extractTrackingDataFromShipment(EbayEnterprise_Order_Model_Detail_Process_Response_Shipment $shipment)
    {
        /** @var array */
        $trackingData = [];
        /** @var array */
        $tracks = $shipment->getTracks();
        foreach ($tracks as $track) {
            if ($this->trackingNumber && $this->trackingNumber === $track) {
                return [$this->getTrack($shipment, $track)];
            } else {
                $trackingData[] = $this->getTrack($shipment, $track);
            }
        }
        return $trackingData;
    }

    /**
     * Get the tracking data.
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
     * @param  string
     * @return Varien_Object
     */
    protected function getTrack(EbayEnterprise_Order_Model_Detail_Process_Response_Shipment $shipment, $track)
    {
        /** @var Mage_Shipping_Model_Tracking_Result_Status | null */
        $trackingInfo = $this->getTrackingInfo($shipment, $track);
        return $this->factory->getNewVarienObject([
            'carrier' => $trackingInfo ? $trackingInfo->getCarrier() : $shipment->getCarrier(),
            'carrier_title' => $trackingInfo ? $trackingInfo->getCarrierTitle() : $shipment->getShippingCarrierTitle(),
            'tracking' => $track,
            'popup' => true,
            'url' => $trackingInfo ? $trackingInfo->getUrl() : null,
        ]);
    }

    /**
     * Get the shipment tracking information.
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
     * @param  string
     * @return Mage_Shipping_Model_Tracking_Result_Status | null
     */
    protected function getTrackingInfo(EbayEnterprise_Order_Model_Detail_Process_Response_Shipment $shipment, $track)
    {
        /** @var string */
        $mageShippingCarrierCode = $this->getShippingCarrierCode($shipment);
        if ($mageShippingCarrierCode) {
            /** @var Mage_Usa_Model_Shipping_Carrier_Abstract */
            $carrierInstance = $this->shippingConfig->getCarrierInstance($mageShippingCarrierCode);
            if ($carrierInstance) {
                /** @var Mage_Shipping_Model_Tracking_Result_Status | null */
                return $carrierInstance->getTrackingInfo($track);
            }
        }
        return null;
    }

    /**
     * Get Magento's shipping carrier code.
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
     * @return string | null
     */
    protected function getShippingCarrierCode(EbayEnterprise_Order_Model_Detail_Process_Response_Shipment $shipment)
    {
        /** @var Mage_Shipping_Model_Carrier_Abstract[] */
        $carrriers = $this->shippingConfig->getAllCarriers();
        /** @var string */
        $romCarrierCode = strtolower($shipment->getCarrier());
        foreach ($carrriers as $carrier) {
            /** @var string */
            $mageCarrierCode = $carrier->getCarrierCode();
            if (strpos($romCarrierCode, $mageCarrierCode) !== false) {
                return $mageCarrierCode;
            }
        }
        return null;
    }
}
