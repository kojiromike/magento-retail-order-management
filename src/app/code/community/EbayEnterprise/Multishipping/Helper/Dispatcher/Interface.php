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

interface EbayEnterprise_Multishipping_Helper_Dispatcher_Interface
{
	/**
	 * Dispatch events for before the order has been submitted.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 */
	public function dispatchBeforeOrderSubmit(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order);

	/**
	 * Dispatch events for orders that have just been submitted but not yet
	 * committed. Any exceptions triggered from these events should prevent
	 * the order from being created and saved.
	 *
	 * Events will be dispatched during the transaction that will be saving
	 * the order. Causing the transaction to fail will prevent any order related
	 * objects from being saved.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 */
	public function dispatchOrderSubmitSuccess(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order);

	/**
	 * Dispatch events for when the order fails to be submitted.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 */
	public function dispatchOrderSubmitFailure(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order);

	/**
	 * Dispatch events for when the order has been completely submitted
	 * successfully.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param Mage_Sales_Model_Order
	 * @return self
	 */
	public function dispatchAfterOrderSubmit(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order);
}
