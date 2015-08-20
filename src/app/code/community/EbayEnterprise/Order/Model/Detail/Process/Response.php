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
use eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderDetailResponse;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IMailingAddress;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IEmailAddressDestination;
use eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderDetailItem;
use eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IShipment;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IPayment;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup;

class EbayEnterprise_Order_Model_Detail_Process_Response extends Mage_Sales_Model_Order implements EbayEnterprise_Order_Model_Detail_Process_IResponse
{
    const ADDRESS_DATA_KEY = 'address_data';
    const ITEM_DATA_KEY = 'item_data';
    const ORDER_DATA_KEY = 'order_data';
    const PAYMENT_DATA_KEY = 'payment_data';
    const SHIPMENT_DATA_KEY = 'shipment_data';
    const SHIPGROUP_DATA_KEY = 'ship_group_data';
    const EMAIL_ADDRESS_DATA_KEY = 'email_address_data';

    /** @var IOrderDetailResponse */
    protected $_response;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $_factory;
    /** @var EbayEnterprise_Order_Model_Detail_Process_Response_IMap */
    protected $_map;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_config;
    /** @var array */
    protected $_detailConfigMap = [];
    /** @var Varien_Data_Collection */
    protected $_shipGroups;

    /**
     * @param array $initParams Must have this key:
     *                          - 'response' => IOrderDetailResponse
     */
    public function __construct(array $initParams)
    {
        list($this->_response, $this->_coreHelper, $this->_factory, $this->_map, $this->_config) = $this->_checkTypes(
            $initParams['response'],
            $this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
            $this->_nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory')),
            $this->_nullCoalesce($initParams, 'map', Mage::getModel('ebayenterprise_order/detail_process_response_map')),
            $this->_nullCoalesce($initParams, 'config', Mage::helper('ebayenterprise_order')->getConfigModel())
        );
        $this->_detailConfigMap = $this->_config->mapDetailResponse;
        parent::__construct($this->_removeKnownKeys($initParams));
    }

    protected function _construct()
    {
        parent::_construct();
        // disabled data from saving
        $this->_dataSaveAllowed = false;
        // Initialize the various collections that need to overridden to use
        // plain Varien_Data_Collections instead of DB backed resource collections.
        // This eliminates the need for most method overrides as all of the methods
        // will simply return the existing collection when it is already set.
        /** @see Mage_Sales_Model_Order::$_addresses */
        $this->_addresses = $this->_coreHelper->getNewVarienDataCollection();
        /** @see Mage_Sales_Model_Order::$_shipments */
        $this->_shipments = $this->_coreHelper->getNewVarienDataCollection();
        /** @see Mage_Sales_Model_Order::$_items */
        $this->_items = $this->_coreHelper->getNewVarienDataCollection();
        /** @see Mage_Sales_Model_Order::$_payments */
        $this->_payments = $this->_coreHelper->getNewVarienDataCollection();
        /** @see Mage_Sales_Model_Order::$_statusHistory */
        $this->_statusHistory = $this->_coreHelper->getNewVarienDataCollection();
        /** @see Mage_Sales_Model_Order::$_invoices */
        $this->_invoices = $this->_coreHelper->getNewVarienDataCollection();
        /** @see Mage_Sales_Model_Order::$_tracks */
        $this->_tracks = $this->_coreHelper->getNewVarienDataCollection();
        /** @see Mage_Sales_Model_Order::$_creditmemos */
        $this->_creditmemos = $this->_coreHelper->getNewVarienDataCollection();
        $this->_shipGroups = $this->_coreHelper->getNewVarienDataCollection();
    }

    /**
     * Get the shipment collection.
     *
     * @return EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
     */
    public function getShipmentsCollection()
    {
        return $this->_shipments;
    }

    /**
     * Remove the all the require and optional keys from the $initParams
     * parameter.
     *
     * @param  array
     * @return array
     */
    protected function _removeKnownKeys(array $initParams)
    {
        foreach (['response', 'core_helper', 'factory', 'map', 'config'] as $key) {
            if (isset($initParams[$key])) {
                unset($initParams[$key]);
            }
        }
        return $initParams;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  IOrderDetailResponse
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @param  EbayEnterprise_Order_Helper_Factory
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_IMap
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function _checkTypes(
        IOrderDetailResponse $response,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_Order_Helper_Factory $factory,
        EbayEnterprise_Order_Model_Detail_Process_Response_IMap $map,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config
    ) {
        return [$response, $coreHelper, $factory, $map, $config];
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @see EbayEnterprise_Order_Model_Detail_Process_IResponse::process()
     */
    public function process()
    {
        return $this->_response instanceof IOrderDetailResponse
            ? $this
            ->_populateOrder()
            ->_populateOrderAddress()
            ->_determineBillingAddress()
            ->_determineShippingAddress()
            ->_populateOrderItem()
            ->_populateOrderPayment()
            ->_populateOrderShipment()
            ->_groupBundleItems()
            ->_populateOrderShipGroup()
            : $this;
    }

    /**
     * Populating the order address collection using payload data.
     *
     * @return self
     */
    protected function _populateOrderAddress()
    {
        /** @var IOrderResponse $order */
        $order = $this->_response->getOrder();

        /** @var IOrderDetailShipping $shipping */
        $shipping = $order->getShipping();

        /** @var IDestinationIterable $destinations */
        $destinations = $shipping->getDestinations();

        /** @var IMailingAddress | IEmailAddressDestination $mailingAddress */
        foreach ($destinations as $mailingAddress) {
            if ($mailingAddress instanceof IMailingAddress) {
                $this->_extractMailingAddresInfo($mailingAddress);
            } elseif ($mailingAddress instanceof IEmailAddressDestination) {
                $this->_extractEmailAddresInfo($mailingAddress);
            }
        }
        return $this;
    }

    /**
     * Extracts all address information from payload.
     *
     * @param  IMailingAddress
     * @return self
     */
    protected function _extractMailingAddresInfo(IMailingAddress $address)
    {
        $addressData = $this->_extractData($address, $this->_detailConfigMap[static::ADDRESS_DATA_KEY]);
        $this->getAddressesCollection()
            ->addItem($this->_factory->getNewDetailProcessResponseAddress($addressData, $this));
        return $this;
    }

    /**
     * Extract email address information from payload.
     *
     * @param  IEmailAddressDestination
     * @return self
     */
    protected function _extractEmailAddresInfo(IEmailAddressDestination $address)
    {
        $addressData = $this->_extractData($address, $this->_detailConfigMap[static::EMAIL_ADDRESS_DATA_KEY]);
        $addressData['is_virtual_address'] = true;
        $this->getAddressesCollection()
            ->addItem($this->_factory->getNewDetailProcessResponseAddress($addressData, $this));
        return $this;
    }

    /**
     * Get the payload billing address id ref.
     *
     * @return string
     */
    protected function _getPayloadBillingIdRef()
    {
        /** @var IOrderResponse $order */
        $order = $this->_response->getOrder();

        /** @var IOrderDetailPayment $payment */
        $payment = $order->getPayment();

        return $payment->getRef();
    }

    /**
     * Determine which address is a billing address
     *
     * @return self
     */
    protected function _determineBillingAddress()
    {
        $billingAddress = $this->getAddressesCollection()->getItemById($this->_getPayloadBillingIdRef());
        if ($billingAddress) {
            $billingAddress->setAddressType(Mage_Customer_Model_Address_Abstract::TYPE_BILLING);
        }
        return $this;
    }

    /**
     * Get the payload ship address id ref and charge type data.
     *
     * @return array
     */
    protected function _getPayloadShippingData()
    {
        /** @var IOrderResponse $order */
        $order = $this->_response->getOrder();

        /** @var IOrderDetailShipping $shipping */
        $shipping = $order->getShipping();

        /** @var IShipGroupIterable $shipGroups */
        $shipGroups = $shipping->getShipGroups();

        /** @var IShipGroup $shipGroup */
        foreach ($shipGroups as $shipGroup) {
            $destinationId = $shipGroup->getDestinationId();
            if ($destinationId) {
                return [
                    'id_ref' =>$destinationId,
                    'charge_type' => $shipGroup->getChargeType()
                ];
            }
        }
        return [];
    }

    /**
     * Determine which address is a shipping address.
     *
     * @return self
     */
    protected function _determineShippingAddress()
    {
        $data = $this->_getPayloadShippingData();
        $shippingAddress = $this->getAddressesCollection()->getItemById($data['id_ref']);
        if ($shippingAddress) {
            $shippingAddress->addData([
                'address_type' => Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING,
                'charge_type' => $data['charge_type'],
            ]);
        }
        return $this;
    }

    /**
     * Populating the order item collection using payload data.
     *
     * @return self
     */
    protected function _populateOrderItem()
    {
        /** @var IOrderResponse $order */
        $order = $this->_response->getOrder();

        /** @var IOrderDetailItemIterable $items */
        $items = $order->getOrderDetailItems();

        /** @var OrderDetailItem $item */
        foreach ($items as $item) {
            $this->_extractOrderDetailItemInfo($item);
        }
        return $this;
    }

    /**
     * @param  IOrderDetailItem
     * @return self
     */
    protected function _extractOrderDetailItemInfo(IOrderDetailItem $item)
    {
        $itemData = $this->_extractData($item, $this->_detailConfigMap[static::ITEM_DATA_KEY]);
        $this->getItemsCollection()
            ->addItem($this->_factory->getNewDetailProcessResponseItem($itemData, $this));
        return $this;
    }

    /**
     * Populating the order object using payload data.
     *
     * @return self
     */
    protected function _populateOrder()
    {
        $this->_extractOrderDetailInfo($this->_response);
        return $this;
    }

    /**
     * @param  IOrderDetailResponse
     * @return self
     */
    protected function _extractOrderDetailInfo(IOrderDetailResponse $order)
    {
        $orderData = $this->_extractData($order, $this->_detailConfigMap[static::ORDER_DATA_KEY]);
        $this->addData($orderData);
        return $this;
    }

    /**
     * Populating the order payment collection using payload data.
     *
     * @return self
     */
    protected function _populateOrderPayment()
    {
        /** @var IOrderResponse $order */
        $order = $this->_response->getOrder();

        /** @var IOrderDetailPayment $payment */
        $payment = $order->getPayment();

        /** @var IPaymentIterable $payments */
        $payments = $payment->getPayments();

        /** @var IPayment $paymentType */
        foreach ($payments as $paymentType) {
            $this->_extractOrderDetailPaymentInfo($paymentType);
        }
        return $this;
    }

    /**
     * @param  IPayment
     * @return self
     */
    protected function _extractOrderDetailPaymentInfo(IPayment $payment)
    {
        $paymentData = $this->_extractData($payment, $this->_detailConfigMap[static::PAYMENT_DATA_KEY]);
        $paymentData['order'] = $this;
        $paymentData['payment_type_name'] = $payment::ROOT_NODE;
        $this->getPaymentsCollection()
            ->addItem($this->_factory->getNewDetailProcessResponsePayment($paymentData));
        return $this;
    }

    /**
     * Populating the order shipment collection using payload data.
     *
     * @return self
     */
    protected function _populateOrderShipment()
    {
        /** @var IOrderResponse $order */
        $order = $this->_response->getOrder();

        /** @var IOrderDetailShipping $shipping */
        $shipping = $order->getShipping();

        /** @var IShipmentIterable $shipments */
        $shipments = $shipping->getShipments();

        /** @var IShipment $shipment */
        foreach ($shipments as $shipment) {
            $this->_extractOrderDetailShipmentInfo($shipment);
        }
        return $this;
    }

    /**
     * @param  IShipment
     * @return self
     */
    protected function _extractOrderDetailShipmentInfo(IShipment $shipment)
    {
        $shipmentData = $this->_extractData($shipment, $this->_detailConfigMap[static::SHIPMENT_DATA_KEY]);
        $shipmentData['order'] = $this;
        $this->getShipmentsCollection()
            ->addItem($this->_factory->getNewDetailProcessResponseShipment($shipmentData));
        return $this;
    }

    /**
     * Populating the order ship group collection using payload data.
     *
     * @return self
     */
    protected function _populateOrderShipGroup()
    {
        /** @var IOrderResponse $order */
        $order = $this->_response->getOrder();

        /** @var IOrderDetailShipping $shipping */
        $shipping = $order->getShipping();

        /** @var IShipGroupIterable $shipGroups */
        $shipGroups = $shipping->getShipGroups();

        /** @var IShipGroup $shipGroup */
        foreach ($shipGroups as $shipGroup) {
            $this->_extractOrderDetailShipGroupInfo($shipGroup);
        }
        return $this;
    }

    /**
     * @param  IShipGroup
     * @return self
     */
    protected function _extractOrderDetailShipGroupInfo(IShipGroup $shipGroup)
    {
        $shipGroupData = $this->_extractData($shipGroup, $this->_detailConfigMap[static::SHIPGROUP_DATA_KEY]);
        $shipGroupData['order'] = $this;
        $this->getShipGroupsCollection()
            ->addItem($this->_factory->getNewDetailProcessResponseShipGroup($shipGroupData));
        return $this;
    }

    /**
     * Extracting the response data using the configuration
     * callback methods.
     *
     * @param  IPayload
     * @param  array
     * @return array
     */
    protected function _extractData(IPayload $payload, array $map)
    {
        $data = [];
        foreach ($map as $key => $callback) {
            if ($callback['type'] !== 'disabled') {
                $getter = $callback['getter'];
                $callback['parameters'] = [$payload, $getter];
                $data[$key] = $this->_coreHelper->invokeCallback($callback);
            }
        }
        return $data;
    }

    /**
     * @see Mage_Sales_Model_Order::getStatusHistoryCollection()
     * Override method to remove/ignore the $reload parameter and always return the
     * collection it already has. When dealing with non-DB backed collection, the
     * reload flag doesn't really mean anything as the collection can't really
     * be reloaded.
     *
     * @return Varien_Data_Collection
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getStatusHistoryCollection($reload = false)
    {
        return $this->_statusHistory;
    }

    /**
     * Contains order detail ship groups informations.
     *
     * @return Varien_Data_Collection
     */
    public function getShipGroupsCollection()
    {
        return $this->_shipGroups;
    }

    /**
     * @return IOrderDetailResponse
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Group each bundle items and their children as a single item with options.
     *
     * @return self
     */
    protected function _groupBundleItems()
    {
        $this->_factory
            ->getNewDetailProcessResponseRelationship($this)
            ->process();
        return $this;
    }
}
