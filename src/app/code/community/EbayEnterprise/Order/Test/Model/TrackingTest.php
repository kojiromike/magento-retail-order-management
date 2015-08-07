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

class EbayEnterprise_Order_Test_Model_TrackingTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * @return array
     */
    public function providerTrackingInfo()
    {
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        return [
            [$order, Mage::getModel('ebayenterprise_order/detail_process_response_shipment', ['increment_id' => 's0-9399111188381', 'order' => $order]), 's0-9399111188381', '9938811121'],
            [$order, Mage::getModel('ebayenterprise_order/detail_process_response_shipment', ['order' => $order]), 's0-9399111188381', '9938811121'],
        ];
    }

    /**
     * Scenario: Get tracking data
     * Given a ROM order object
     * And shipment id
     * And tracking number
     * When getting tracking data
     * Then get the specified shipment from the ROM order object
     * And extract tracking data for it.
     *
     * @param Mage_Sales_Model_Order
     * @param EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
     * @param string
     * @param string
     * @dataProvider providerTrackingInfo
     */
    public function testGetTrackingData(Mage_Sales_Model_Order $order, EbayEnterprise_Order_Model_Detail_Process_Response_Shipment $shipment, $shipmentId, $trackingNumber)
    {
        /** @var Varien_Data_Collection */
        $shipments = new Varien_Data_Collection();
        $shipments->addItem($shipment);

        EcomDev_Utils_Reflection::setRestrictedPropertyValues($order, [
            '_shipments' => $shipments,
        ]);

        /** @var EbayEnterprise_Order_Model_Tracking */
        $tracking = $this->getModelMock('ebayenterprise_order/tracking', ['extractTrackingDataFromShipment'], false, [[
            'order' => $order,
            'shipment_id' => $shipmentId,
            'tracking_number' => $trackingNumber,
        ]]);
        $tracking->expects($this->once())
            ->method('extractTrackingDataFromShipment')
            ->with($this->identicalTo($shipment))
            ->will($this->returnValue([]));

        $this->assertCount(1, $tracking->getTrackingData());
    }

    /**
     * @return array
     */
    public function providerExtractTrackingDataFromShipment()
    {
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var Varien_Object */
        $trackA = new Varien_Object([
            'carrier' => 'ups',
            'carrier_title' => 'Ground',
            'tracking' => '9938811121',
            'popup' => true,
            'url' => 'https://www.wapps.com/track/',
        ]);
        $trackB = clone $trackA;
        $trackB->setTracking('1111111111');
        return [
            [$order, Mage::getModel('ebayenterprise_order/detail_process_response_shipment', ['order' => $order, 'tracks' => ['9938811121']]), $trackA, 's0-9399111188381', '9938811121'],
            [$order, Mage::getModel('ebayenterprise_order/detail_process_response_shipment', ['order' => $order, 'tracks' => ['1111111111']]), $trackB, 's0-9399111188381', '9938811121'],
        ];
    }

    /**
     * Scenario: Extract tracking data from shipment
     * Given a shipment object
     * When extracting tracking data from shipment
     * Then extract all tracking data.
     *
     * @param Mage_Sales_Model_Order
     * @param EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
     * @param Varien_Object
     * @param string
     * @param string
     * @dataProvider providerExtractTrackingDataFromShipment
     */
    public function testExtractTrackingDataFromShipment(Mage_Sales_Model_Order $order, EbayEnterprise_Order_Model_Detail_Process_Response_Shipment $shipment, Varien_Object $track, $shipmentId, $trackingNumber)
    {
        /** @var EbayEnterprise_Order_Model_Tracking */
        $tracking = $this->getModelMock('ebayenterprise_order/tracking', ['getTrack'], false, [[
            'order' => $order,
            'shipment_id' => $shipmentId,
            'tracking_number' => $trackingNumber,
        ]]);
        $tracking->expects($this->once())
            ->method('getTrack')
            ->with($this->identicalTo($shipment), $this->identicalTo($track->getTracking()))
            ->will($this->returnValue($track));

        $this->assertSame([$track], EcomDev_Utils_Reflection::invokeRestrictedMethod($tracking, 'extractTrackingDataFromShipment', [$shipment]));
    }

    /**
     * Scenario: Get track object
     * Given a shipment object
     * And tracking number
     * When getting track object
     * Then return a Varien_Object instance with magic tracking information
     */
    public function testGetTrack()
    {
        /** @var string */
        $shipmentId = 's0-9399111188381';
        /** @var string */
        $trackingNumber = '9938811121';
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var string */
        $trackingUrl = 'https://www.wapps.com/track/';
        /** @var string */
        $carrier = 'ups';
        /** @var string */
        $title = 'Ground';
        /** @var array */
        $data = [
            'carrier' => $carrier,
            'carrier_title' => $title,
            'tracking' => $trackingNumber,
            'popup' => true,
            'url' => $trackingUrl,
        ];
        /** @var Varien_Object */
        $track = new Varien_Object($data);

        /** @var Mage_Shipping_Model_Tracking_Result_Status */
        $status = Mage::getModel('shipping/tracking_result_status', $data);

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Shipment */
        $shipment = $this->getModelMock('ebayenterprise_order/detail_process_response_shipment', ['foo'], false, [[
            'order' => $order,
        ]]);

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewVarienObject']);
        $factory->expects($this->once())
            ->method('getNewVarienObject')
            ->with($this->identicalTo($data))
            ->will($this->returnValue($track));
        /** @var EbayEnterprise_Order_Model_Tracking */
        $tracking = $this->getModelMock('ebayenterprise_order/tracking', ['getTrackingInfo'], false, [[
            'order' => $order,
            'shipment_id' => $shipmentId,
            'tracking_number' => $trackingNumber,
            'factory' => $factory,
        ]]);
        $tracking->expects($this->once())
            ->method('getTrackingInfo')
            ->with($this->identicalTo($shipment), $this->identicalTo($trackingNumber))
            ->will($this->returnValue($status));

        $this->assertSame($track, EcomDev_Utils_Reflection::invokeRestrictedMethod($tracking, 'getTrack', [$shipment, $trackingNumber]));
    }

    /**
     * Scenario: Get tracking Information
     * Given a shipment object
     * And tracking number
     * When getting tracking information
     * Then get a shipping method instance using the shipment carrier
     * And get the tracking information instance from the shipping method instance
     * And get the shipping/tracking_result_status instance
     */
    public function testGetTrackingInfo()
    {
        /** @var string */
        $shipmentId = 's0-9399111188381';
        /** @var string */
        $trackingNumber = '9938811121';
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var string */
        $carrier = 'ups';
        /** @var Mage_Shipping_Model_Tracking_Result_Status */
        $result = Mage::getModel('shipping/tracking_result_status');

        /** @var Mage_Usa_Model_Shipping_Carrier_Ups */
        $ups = $this->getModelMock('usa/shipping_carrier_ups', ['getTrackingInfo']);
        $ups->expects($this->once())
            ->method('getTrackingInfo')
            ->with($this->identicalTo($trackingNumber))
            ->will($this->returnValue($result));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Shipment */
        $shipment = Mage::getModel('ebayenterprise_order/detail_process_response_shipment', [
            'order' => $order,
        ]);

        /** @var Mage_Shipping_Model_Config */
        $shippingConfig = $this->getModelMock('shipping/config', ['getCarrierInstance']);
        $shippingConfig->expects($this->once())
            ->method('getCarrierInstance')
            ->with($this->identicalTo($carrier))
            ->will($this->returnValue($ups));

        /** @var EbayEnterprise_Order_Model_Tracking */
        $tracking = $this->getModelMock('ebayenterprise_order/tracking', ['getShippingCarrierCode'], false, [[
            'order' => $order,
            'shipment_id' => $shipmentId,
            'tracking_number' => $trackingNumber,
            'shipping_config' => $shippingConfig,
        ]]);
        $tracking->expects($this->once())
            ->method('getShippingCarrierCode')
            ->with($this->identicalTo($shipment))
            ->will($this->returnValue($carrier));

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($tracking, 'getTrackingInfo', [$shipment, $trackingNumber]));
    }

    /**
     * @return array
     */
    public function providerGetShippingCarrierCode()
    {
        return [
            ['UPSN', 'ups'],
            ['ANY', null],
        ];
    }
    /**
     * Scenario: Get shipping carrier code
     * Given a shipment object
     * When getting shipping carrier code
     * Then get all Magento's shipping carrier
     * And if any Magento's shipping carrier code matches ROM
     * carrier code then return the Magento's Carrier code.
     *
     * @param string
     * @param string
     * @dataProvider providerGetShippingCarrierCode
     */
    public function testGetShippingCarrierCode($romCarrierCode, $expect)
    {
        /** @var string */
        $shipmentId = 's0-9399111188381';
        /** @var string */
        $trackingNumber = '9938811121';
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var Mage_Usa_Model_Shipping_Carrier_Ups */
        $ups = Mage::getModel('usa/shipping_carrier_ups');

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Shipment */
        $shipment = $this->getModelMock('ebayenterprise_order/detail_process_response_shipment', ['getCarrier'], false, [[
            'order' => $order,
        ]]);
        $shipment->expects($this->once())
            ->method('getCarrier')
            ->will($this->returnValue($romCarrierCode));

        /** @var Mage_Shipping_Model_Config */
        $shippingConfig = $this->getModelMock('shipping/config', ['getAllCarriers']);
        $shippingConfig->expects($this->once())
            ->method('getAllCarriers')
            ->will($this->returnValue([$ups]));

        /** @var EbayEnterprise_Order_Model_Tracking */
        $tracking = Mage::getModel('ebayenterprise_order/tracking', [
            'order' => $order,
            'shipment_id' => $shipmentId,
            'tracking_number' => $trackingNumber,
            'shipping_config' => $shippingConfig,
        ]);

        $this->assertSame($expect, EcomDev_Utils_Reflection::invokeRestrictedMethod($tracking, 'getShippingCarrierCode', [$shipment]));
    }
}
