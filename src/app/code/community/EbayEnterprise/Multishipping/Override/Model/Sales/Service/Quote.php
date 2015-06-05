<?php
/**
 * Copyright (c) 2013-2015 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Multishipping_Override_Model_Sales_Service_Quote extends Mage_Sales_Model_Service_Quote
{
    /** @var EbayEnterprise_Multishipping_Helper_Factory */
    protected $_multishippingFactory;
    /** @var EbayEnterprise_Multishipping_Helper_Dispatcher_Interface */
    protected $_checkoutDispatcher;
    /** @var Mage_Customer_Model_Session */
    protected $_customerSession;

    /**
     * @param Mage_Sales_Model_Quote
     */
    public function __construct(Mage_Sales_Model_Quote $quote)
    {
        parent::__construct($quote);
        $this->_multishippingFactory = Mage::helper('ebayenterprise_multishipping/factory');
        $this->_checkoutDispatcher = Mage::helper('ebayenterprise_multishipping/dispatcher_onepage');
    }

    /**
     * Set the multishipping factory helper.
     *
     * Allows the factory helper to be injected instead of always using the
     * instance retrieved from the environment.
     *
     * @param EbayEnterprise_Multishipping_Helper_Factory
     * @return self
     */
    public function setMultishippingFactory(EbayEnterprise_Multishipping_Helper_Factory $multishippingFactory)
    {
        $this->_multishippingFactory = $multishippingFactory;
        return $this;
    }

    /**
     * Set the checkout dispatcher to use while creating a new order.
     *
     * Allows the checkout dispatcher - object responsible for dispatching
     * appropriate Magento events while submitting the order - to be injected
     * instead of always using the instance retrieved from the environment.
     *
     * @param EbayEnterprise_Multishipping_Helper_Dispatcher_Interface
     * @return self
     */
    public function setCheckoutDispatcher(EbayEnterprise_Multishipping_Helper_Dispatcher_Interface $dispatcher)
    {
        $this->_checkoutDispatcher = $dispatcher;
        return $this;
    }

    /**
     * Set the customer session instance to use.
     *
     * Allows session instances to be injected instead of always retrieved from
     * the environment.
     *
     * @param Mage_Customer_Model_Session
     * @return self
     */
    public function setCustomerSession(Mage_Customer_Model_Session $customerSession)
    {
        $this->_customerSession = $customerSession;
        return $this;
    }

    /**
     * Get the customer session model.
     *
     * @return Mage_Customer_Model_Session
     */
    public function getCustomerSession()
    {
        if (!$this->_customerSession) {
            $this->_customerSession = Mage::getSingleton('customer/session');
        }
        return $this->_customerSession;
    }

    /**
     * Submit the quote. Quote submit process will create the order based on
     * quote data.
     *
     * @return Mage_Sales_Model_Order
     */
    public function submitOrder()
    {
        $this->_prepareQuote();
        $order = $this->_createOrder();
        // Create the transaction to place the order.
        $transaction = $this->_multishippingFactory->createOrderSaveTransaction(
            $this->_quote,
            $order,
            ($this->_quote->getCustomerId() ? $this->_quote->getCustomer() : null)
        );

        // Dispatch events for when the order has been instantiated but before
        // any attempt has been made to actually save the order. Allows for
        // additional modifications to the order to be made before attempting
        // to save and place the order.
        $this->_checkoutDispatcher->dispatchBeforeOrderSubmit($this->_quote, $order);
        try {
            // Saving the transaction should trigger the order to be created -
            // save quote and order objects (incl. addresses & items), place
            // payments, and dispatch one last event that may be able to block
            // the order from being created.
            $transaction->save();
            $this->_inactivateQuote();
            $this->_checkoutDispatcher->dispatchOrderSubmitSuccess($this->_quote, $order);
        } catch (Exception $e) {
            $this->_cleanupFailedOrder($order);
            // Dispatch events signaling that the order failed to be created.
            // Allows related modules to cleanup or roll back actions taken
            // while attempting to creating the order.
            $this->_checkoutDispatcher->dispatchOrderSubmitFailure($this->_quote, $order);
            // Re-throw the exception to be handled in a user-friendly way elsewhere.
            throw $e;
        }
        // Signal that the order create was a success and the order has been
        // placed and saved.
        $this->_checkoutDispatcher->dispatchAfterOrderSubmit($this->_quote, $order);
        $this->_order = $order;
        return $order;
    }

    /**
     * Validate and prepare the quote for being submitted as an order.
     *
     * Behaviors of this method are inherited from the core Magento
     * implementation of submitOrder.
     *
     * @return self
     */
    protected function _prepareQuote()
    {
        $this->_deleteNominalItems();
        $this->_validate();
        $this->_quote->reserveOrderId();
        return $this;
    }

    /**
     * Create a new order object for a quote. Populate the new order with
     * order data transferred from the quote.
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _createOrder()
    {
        $order = $this->_convertor->toOrder($this->_quote);
        // Create the order addresses and items for each quote address being
        // converted to an order.
        $this->_createOrderAddresses($this->_quote->getAllAddresses(), $order);
        // Collect shipment level amounts onto the order.
        $order->collectShipmentAmounts()
            ->setPayment($this->_convertor->paymentToOrderPayment($this->_quote->getPayment()))
            ->setQuote($this->_quote)
            ->addData($this->_orderData);
        return $order;
    }

    /**
     * Create order addresses for addresses in the quote.
     *
     * @param Mage_Sales_Model_Quote_Address[]
     * @param Mage_Sales_Model_Order
     * @return self
     */
    protected function _createOrderAddresses(array $addresses, Mage_Sales_Model_Order $order)
    {
        foreach ($addresses as $quoteAddress) {
            $orderAddress = $this->_convertor->addressToOrderAddress($quoteAddress);
            $this->_addAddressToOrder($orderAddress, $order);
            $this->_createOrderItems($quoteAddress->getAllItems(), $orderAddress, $order);
        }
        return $this;
    }

    /**
     * Create order items for quote items belonging to an address.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @param Mage_Sales_Model_Order_Address
     * @param Mage_Sales_Model_Order
     * @return self
     */
    protected function _createOrderItems(
        array $quoteItems,
        Mage_Sales_Model_Order_Address $orderAddress,
        Mage_Sales_Model_Order $order
    ) {
        foreach ($quoteItems as $quoteAddressItem) {
            $orderItem = $this->_convertor->itemToOrderItem($quoteAddressItem)
                ->setOrderAddress($orderAddress)
                ->setProductType($this->_getItemProductType($quoteAddressItem));
            $order->addItem($orderItem);
        }
        return $this;
    }

    /**
     * Add a new order address to a new order.
     *
     * @param Mage_Sales_Model_Order_Address
     * @param Mage_Sales_Model_Order
     * @return self
     */
    protected function _addAddressToOrder(
        Mage_Sales_Model_Order_Address $address,
        Mage_Sales_Model_Order $order
    ) {
        if ($address->getAddressType() === Mage_Sales_Model_Order_Address::TYPE_BILLING) {
            $order->setBillingAddress($address);
        } else {
            $order->addAddress($address);
        }
        return $this;
    }

    /**
     * Get the type of product the item represents - e.g. simple or configurable.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return string
     */
    protected function _getItemProductType(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        return $item->getProductType() ?: $item->getQuoteItem()->getProductType();
    }

    /**
     * Remove ids and foreign keys between order objects that failed to be
     * saved.
     *
     * @param Mage_Sales_Model_Order
     * @return self
     */
    protected function _cleanupFailedOrder(Mage_Sales_Model_Order $order)
    {
        if (!$this->getCustomerSession()->isLoggedIn()) {
            // reset customer ID's on exception, because customer not saved
            $this->_quote->getCustomer()->setId(null);
        }

        //reset order id's on exception, because order not saved
        $order->setId(null);
        /** @var $item Mage_Sales_Model_Order_Item */
        foreach ($order->getItemsCollection() as $item) {
            $item->setOrderId(null)
                ->setItemId(null)
                ->setOrderAddressId(null);
        }
        return $this;
    }
}
