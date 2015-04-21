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

class EbayEnterprise_Multishipping_Helper_Dispatcher_Multishipping implements EbayEnterprise_Multishipping_Helper_Dispatcher_Interface
{
	/**
	 * Dispatch events for before the order has been submitted.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 */
	public function dispatchBeforeOrderSubmit(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
	{
		// Dispatch the multishipping single order create event. This event
		// doesn't exactly mean the same in ROM Multishipping checkout but
		// is still used to preserve compatibility with extensions that expect
		// the event. Provide the primary shipping address as the address in
		// the event as this would be closes to the one address included
		// in base Magento. Existing observers in Magento don't appear to need
		// it but keeping it in case any 3rd party extensions do expect it.
		Mage::dispatchEvent(
			'checkout_type_multishipping_create_orders_single',
			['order' => $order, 'address' => $order->getShippingAddress()]
		);
		return $this;
	}

	/**
	 * Dispatch events for immediately after an order has been submitted.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 * @codeCoverageIgnore Nothing to test here.
	 */
	public function dispatchOrderSubmitSuccess(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
	{
		// No comparable event in multishipping checkout here.
		return $this;
	}

	/**
	 * Dispatch events for when the order fails to be submitted.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 */
	public function dispatchOrderSubmitFailure(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
	{
		Mage::dispatchEvent('checkout_multishipping_refund_all', ['orders' => [$order]]);
		return $this;
	}

	/**
	 * Dispatch events for when the order has been completely submitted
	 * successfully.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 * @codeCoverageIgnore Nothing to test here.
	 */
	public function dispatchAfterOrderSubmit(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
	{
		// No comparable event in multishipping checkout here.
		return $this;
	}
}
