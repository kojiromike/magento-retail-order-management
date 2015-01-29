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

/**
 * Event observer for Ebay Enterprise PayPal
 */
class EbayEnterprise_Paypal_Model_Observer
{
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/**
	 * Set up the logger
	 */
	public function __construct()
	{
		$this->_logger = Mage::helper('ebayenterprise_magelog');
	}

	/**
	 * undo/cancel the PayPal payment
	 *
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function rollbackExpressPayment(Varien_Event_Observer $observer)
	{
		$order = $observer->getEvent()->getOrder();
		if ($order instanceof Mage_Sales_Model_Order) {
			$this->_getVoidModel()->void($order);
		}
		return $this;
	}

	/**
	 * add paypal payment payloads to the order create
	 * request.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function handleOrderCreatePaymentEvent(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$order = $event->getOrder();
		$processedPayments = $event->getProcessedPayments();
		$paymentContainer = $event->getPaymentContainer();
		Mage::getModel('ebayenterprise_paypal/order_create_payment')
			->addPaymentsToPayload($order, $paymentContainer, $processedPayments);
		return $this;
	}

	/**
	 * Update the order create request context with paypal information.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function handleOrderCreateContextEvent(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$order = $event->getOrder();
		$orderContext = $event->getOrderContext();
		Mage::getModel('ebayenterprise_paypal/order_create_context')
			->updateOrderContext($order, $orderContext);
		return $this;
	}

	/**
	 * @return EbayEnterprise_PayPal_Model_Void
	 */
	protected function _getVoidModel()
	{
		return Mage::getModel('ebayenterprise_paypal/void');
	}
}
