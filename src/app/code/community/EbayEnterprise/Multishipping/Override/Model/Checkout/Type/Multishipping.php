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

class EbayEnterprise_Multishipping_Override_Model_Checkout_Type_Multishipping extends Mage_Checkout_Model_Type_Multishipping
{
    const MULTI_SHIPPING_ORDER_CREATE_EVENT = 'ebayenterprise_multishipping_before_submit_order_create';

    /** @var EbayEnterprise_Multishipping_Helper_Factory */
    protected $_multishippingFactory;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_logContext;

    /**
     * Skip parent __construct which breaks setting Varien_Object data via
     * the constructor. Any functionality of parent::__construct will need to
     * be replicated in the protected self::_construct method (as is the
     * convention when extending Varien_Objects).
     *
     * @param array
     */
    public function __construct(array $args = [])
    {
        Mage_Checkout_Model_Type_Abstract::__construct($args);
    }

    /**
     * Deal with injected dependencies and resolve any that were not injected
     * to some default.
     *
     * Also must replicate any behavior that would have been inherited in
     * the parent::__construct method.
     */
    protected function _construct()
    {
        list(
            $this->_multishippingFactory,
            $this->_logger,
            $this->_logContext,
            $this->_data['core_session'],
            $this->_data['checkout_session']
        ) = $this->_checkTypes(
            $this->getData('multishipping_factory') ?: Mage::helper('ebayenterprise_multishipping/factory'),
            $this->getData('logger') ?: Mage::helper('ebayenterprise_magelog'),
            $this->getData('log_context') ?: Mage::helper('ebayenterprise_magelog/context'),
            $this->getData('core_session'),
            $this->getData('checkout_session')
        );
        $this->_init();
    }

    /**
     * Enforce type checks on construct args array.
     *
     * @param EbayEnterprise_Multishipping_Helper_Factory
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @param Mage_Core_Model_Session
     * @param Mage_Checkout_Model_Session
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Multishipping_Helper_Factory $multishippingFactory,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext,
        Mage_Core_Model_Session $coreSession = null,
        Mage_Checkout_Model_Session $checkoutSession = null
    ) {
        return func_get_args();
    }

    /**
     * Create orders per each quote address
     *
     * @return self
     */
    public function createOrders()
    {
        $this->_validate();
        // Submit order via quote service - handles converting the quote to
        // an order, placing payments and saving the order as well as any event
        // dispatches and error handling related to those processes.
        $order = $this->_submitOrder();
        if ($order) {
            $this->_queueNewOrderEmail($order)->_signalOrderSuccess($order);
            // Quote does not get saved after being made inactive by the
            // service or further up in the controller. Being saved here to
            // persist the change to the quote.
            $this->getQuote()->save();
        }
        return $this;
    }

    /**
     * Submit the order, returning the created order object if successful.
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _submitOrder()
    {
        /** @var Mage_Sales_Model_Quote */
        $quote = $this->getQuote();
        Mage::dispatchEvent(static::MULTI_SHIPPING_ORDER_CREATE_EVENT, ['quote' => $quote]);
        $service = $this->_multishippingFactory
            ->createQuoteService($quote, true);
        // Using submitOrder instead of submitAll. submitAll is only necessary
        // when creating an order that may contain recurring profiles/nominal
        // items. As such orders can only be ordered separately, they should
        // never find their way into multishipping checkout.
        return $service->submitOrder();
    }

    /**
     * If a new email can be sent for the order, queue it to be sent.
     *
     * @param Mage_Sales_Model_Order
     * @return self
     */
    protected function _queueNewOrderEmail(Mage_Sales_Model_Order $order)
    {
        if ($order->getCanSendNewEmailFlag()) {
            try {
                $order->queueNewOrderEmail();
            } catch (Exception $e) {
                $this->_logger->warning('Unable to queue new order email.', $this->_logContext->getMetaData(__CLASS__, [], $e));
            }
        }
        return $this;
    }

    /**
     * Signal to the rest of the system that the order has been created
     * successfully - add success data to the session and dispatch an even
     * with the new order.
     *
     * @param Mage_Sales_Model_Order $order The newly created order object
     * @return self
     */
    protected function _signalOrderSuccess(Mage_Sales_Model_Order $order)
    {
        // add order information to the session
        $this->getCoreSession()->setOrderIds([$order->getId() => $order->getIncrementId()]);
        $this->getCheckoutSession()->setLastQuoteId($this->getQuote()->getId());
        Mage::dispatchEvent(
            'checkout_submit_all_after',
            ['order' => $order, 'quote' => $this->getQuote()]
        );
        return $this;
    }

    /**
     * Retrieve core session model.
     *
     * @return Mage_Core_Model_Session
     * @codeCoverageIgnore Wrapping session instantiation which cannot occur in tests without causing session_start errors.
     */
    public function getCoreSession()
    {
        $session = $this->getData('core_session');
        if (is_null($session)) {
            $session = Mage::getSingleton('core/session');
            $this->setData('core_session', $session);
        }
        return $session;
    }
}
