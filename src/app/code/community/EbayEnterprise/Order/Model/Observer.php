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

use \eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;

/**
 * A collection of entrypoint methods for handling order-related events.
 */
class EbayEnterprise_Order_Model_Observer
{
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $factory;
    /** @var EbayEnterprise_Order_Helper_Event_Shipment */
    protected $shipmentEventHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Order_Helper_Event */
    protected $orderEventHelper;
    /** @var EbayEnterprise_Order_Helper_Data */
    protected $orderHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $orderCfg;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;

    /**
     * Initialize properties
     */
    public function __construct(array $args = [])
    {
        list(
            $this->factory,
            $this->logger,
            $this->orderHelper,
            $this->orderCfg,
            $this->orderEventHelper,
            $this->shipmentEventHelper,
            $this->coreHelper,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce('factory', $args, Mage::helper('ebayenterprise_order/factory')),
            $this->nullCoalesce('logger', $args, Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce('helper', $args, Mage::helper('ebayenterprise_order')),
            $this->nullCoalesce('config', $args, Mage::helper('ebayenterprise_order')->getConfigModel()),
            $this->nullCoalesce('event_helper', $args, Mage::helper('ebayenterprise_order/event')),
            $this->nullCoalesce('shipment_event_helper', $args, Mage::helper('ebayenterprise_order/event_shipment')),
            $this->nullCoalesce('core_helper', $args, Mage::helper('eb2ccore')),
            $this->nullCoalesce('log_context', $args, Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * ensure correct types are being injected
     * @param EbayEnterprise_Order_Helper_Factory
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_Order_Helper_Data
     * @param EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param EbayEnterprise_Order_Helper_Event
     * @param EbayEnterprise_Order_Helper_Event_Shipment
     * @param EbayEnterprise_Eb2cCore_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Order_Helper_Factory $factory,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_Order_Helper_Data $orderHelper,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg,
        EbayEnterprise_Order_Helper_Event $orderEventHelper,
        EbayEnterprise_Order_Helper_Event_Shipment $shipmentEventHelper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    /**
     * return $ar[$key] if it exists otherwise return $default
     * @param  string
     * @param  array
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce($key, array $ar, $default)
    {
        return isset($ar[$key]) ? $ar[$key] : $default;
    }

    /**
     * Fetch an instance of the order create request model
     *
     * @param array $args Key value pair of constructor arguments.
     *                    You must at least provide the order object.
     * @return EbayEnterprise_Order_Model_Create
     */
    protected function getOrderCreateModel(array $args)
    {
        return Mage::getModel('ebayenterprise_order/create', $args);
    }

    /**
     * Submit an order create request
     *
     * @param Varien_Event_Observer
     * @return void
     */
    public function handleSalesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof Mage_Sales_Model_Order) {
            $api = $this->coreHelper->getSdkApi(
                $this->orderCfg->apiService,
                $this->orderCfg->apiCreateOperation
            );
            $constructorArgs = [
                'api' => $api,
                'config' => $this->orderCfg,
                'order' => $order,
                'payload' => $api->getRequestBody(),
            ];
            $this->getOrderCreateModel($constructorArgs)->send();
        } else {
            $this->logger->logWarn(
                '[%s] Attempted to submit order create request, but parameter (%s) is not an order.',
                [__CLASS__, gettype($order)]
            );
        }
    }

    /**
     * Fetch all orders with state 'new' and status 'unsubmitted'.
     *
     * @return Mage_Sales_Model_Order_Resource_Collection
     */
    protected function getUnsubmittedOrders()
    {
        $status = EbayEnterprise_Order_Model_Create::STATUS_NEW;
        return Mage::getResourceModel('sales/order_collection')
            ->addAttributeToSelect('*')
            ->addFieldToFilter('status', ['eq' => $status]);
    }

    /**
     * Retry order create requests on unsubmitted orders.
     * Run this on cron.
     *
     * @return void
     */
    public function handleEbayEnterpriseOrderCreateRetryJob()
    {
        $orders = $this->getUnsubmittedOrders();
        $this->logger->debug(
            'Found {order_retry_count} order(s) to be resubmitted.',
            $this->logContext->getMetaData(__CLASS__, ['order_retry_count' => $orders->getSize()])
        );
        $api = $this->coreHelper->getSdkApi(
            $this->orderCfg->apiService,
            $this->orderCfg->apiCreateOperation
        );
        $createArgs = [
            'api' => $api,
            'config' => $this->orderCfg,
            'payload' => $api->getRequestBody(),
            'is_payload_prebuilt' => true
        ];
        foreach ($orders as $order) {
            $this->resubmit($order, $createArgs);
        }
        $this->logger->debug('Order retry complete.', $this->logContext->getMetaData(__CLASS__));
    }

    /**
     * resubmit the order
     * @param  Mage_Sales_Model_Order
     * @param  array
     */
    protected function resubmit(Mage_Sales_Model_Order $order, array $createArgs)
    {
        $raw = $order->getEb2cOrderCreateRequest();
        if ($raw) {
            $createArgs['order'] = $order;
            $this->getOrderCreateModel($createArgs)->send();
        } else {
            $this->logger->warning(
                'Unable to resubmit "{order_id}". Please see documentation for possible solutions.',
                $this->logContext->getMetaData(['order_id' => $order->getIncrementId()])
            );
        }
    }

    /**
     * Consume the 'ebayenterprise_amqp_message_credit_issued' event.
     * Pass the payload from the event to the 'ebayenterprise_order/creditissued' model instance.
     * Invoke the `process` method on the model to process the payload and issue the credit memo
     *
     * @param Varien_Event_Observer
     * @return void
     */
    public function handleEbayEnterpriseAmqpMessageOrderCreditIssued(Varien_Event_Observer $observer)
    {
        Mage::getModel('ebayenterprise_order/creditissued', ['payload' => $observer->getEvent()->getPayload()])
            ->process();
    }

    /**
     * Consume the event 'ebayenterprise_amqp_message_order_rejected'. Pass the payload
     * from the event down to the 'ebayenterprise_order/orderrejected' instance. Invoke the process
     * method on the 'ebayenterprise_order/orderrejected' instance.
     * @param  Varien_Event_Observer
     * @return void
     */
    public function handleEbayEnterpriseAmqpMessageOrderRejected(Varien_Event_Observer $observer)
    {
        Mage::getModel('ebayenterprise_order/orderrejected', [
            'payload' => $observer->getEvent()->getPayload(),
            'order_event_helper' => $this->orderEventHelper,
            'logger' => $this->logger,
        ])->process();
    }
    /**
     * Listen for an order cancel event.
     * Load a collection using the extracted order increment ids.
     * Update each order's state and status to 'canceled' and the associated status respectively.
     *
     * @param Varien_Event_Observer
     * @return void
     */
    public function handleEbayEnterpriseOrderEventCancel(Varien_Event_Observer $observer)
    {
        $message = trim($observer->getEvent()->getMessage());
        $orderCollection = $this->loadOrdersFromXml($message);
        $eventName = $observer->getEvent()->getName();
        foreach ($orderCollection as $order) {
            $this->orderEventHelper->attemptCancelOrder($order, $eventName);
        }
    }

    /**
     * Consume the event 'ebayenterprise_amqp_message_order_shipped'. Pass the payload
     * from the event down to the 'ebayenterprise_order/ordershipped' instance. Invoke the process
     * method on the 'ebayenterprise_order/ordershipped' instance.
     *
     * @param  Varien_Event_Observer
     * @return void
     */
    public function handleEbayEnterpriseAmqpMessageOrderShipped(Varien_Event_Observer $observer)
    {
        Mage::getModel('ebayenterprise_order/ordershipped', [
            'payload' => $observer->getEvent()->getPayload(),
            'shipment_event_helper' => $this->shipmentEventHelper,
            'logger' => $this->logger,
        ])->process();
    }

    /**
     * Responsible for extracting order increment ids from a passed in DOM document
     * and then load a collection of sales/order instances for any increment ids in
     * the document.
     *
     * @param  string
     * @return Varien_Data_Collection
     */
    protected function loadOrdersFromXml($xml)
    {
        return $this->orderHelper->getOrderCollectionByIncrementIds(
            $this->orderHelper->extractOrderEventIncrementIds($xml)
        );
    }

    /**
     * Copy custom data to order items.
     *
     * @param Varien_Event_Observer
     * @return void
     */
    public function handleSalesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event $event */
        $event = $observer->getEvent();
        /** @var Mage_Sales_Model_Quote_Item_Abstract $item */
        $item = $event->getItem();
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        $orderItem = $event->getOrderItem();
        $data = $item->getEbayEnterpriseOrderDiscountData();
        $orderItem->setEbayEnterpriseOrderDiscountData($data);
    }

    /**
     * Copy custom data to order addresses.
     *
     * @param Varien_Event_Observer
     * @return void
     */
    public function handleSalesConvertQuoteAddressToOrderAddress(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event $event */
        $event = $observer->getEvent();
        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = $event->getAddress();
        /** @var Mage_Sales_Model_Order_Address $orderAddress */
        $orderAddress = $event->getOrderAddress();
        $data = $address->getEbayEnterpriseOrderDiscountData();
        $orderAddress->setEbayEnterpriseOrderDiscountData($data);
    }

    public function handleEbayEnterpriseOrderCreateItemEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $this->factory->getNewRelationshipsModel($event->getItem(), $event->getItemPayload())
            ->injectItemRelationship();
    }
}
