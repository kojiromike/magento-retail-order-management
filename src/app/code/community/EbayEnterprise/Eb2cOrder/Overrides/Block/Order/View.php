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


class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_View extends Mage_Sales_Block_Order_View
{
	/**
	 * overriding order recent to fetch order summary from eb2c webservice to present to customer the order status
	 */
	protected function _prepareLayout()
	{
		// get order id
		$orderId = $this->getOrder()->getRealOrderId();

		// assigned current session customer id to a local variable
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		// instantiate eb2c customer order search class
		$orderSearchObj = Mage::getModel('eb2corder/customer_order_search');

		$cfg = Mage::helper('eb2corder')->getConfigModel();

		if ($customerId > 0) {
			// we have a registered customer
			// making eb2c customer order search request base on current session order id and then
			// parse result in a collection of varien object
			$orderHistorySearchResults = $orderSearchObj->parseResponse($orderSearchObj->requestOrderSummary(
				sprintf('%s%s', $cfg->clientCustomerIdPrefix, $customerId), $orderId
			));
		} else {
			// we have a guest customer
			// let check the session for previous eb2c order result
			$orderHistorySearchResults = Mage::getSingleton('core/session')->getEbcGuestCustomerOrderResults();
			if (empty($orderHistorySearchResults)) {
				// we have nothing in the session let's proceed to make search call to eb2c
				$orderHistorySearchResults = $orderSearchObj->parseResponse($orderSearchObj->requestOrderSummary(
					sprintf('%s%s', $cfg->clientCustomerIdPrefix, $customerId), $orderId
				));
			} elseif (!isset($orderHistorySearchResults[$orderId])) {
				// just for precautions if the search data doesn't match the current $orderId,
				// let's make a call to eb2c just to get the right data
				$orderHistorySearchResults = $orderSearchObj->parseResponse($orderSearchObj->requestOrderSummary(
					sprintf('%s%s', $cfg->clientCustomerIdPrefix, $customerId), $orderId
				));
			}
		}

		// get eb2c order data
		$ebcData = (isset($orderHistorySearchResults[$orderId]))? $orderHistorySearchResults[$orderId] : null;
		if ($ebcData instanceof Varien_Object) {
			$this->getOrder()->addData(array(
				'status' => Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($ebcData->getStatus()),
				'created_at' => $ebcData->getOrderDate(),
				'grand_total' => $ebcData->getOrderTotal(),
			));
		}

		parent::_prepareLayout();
	}
}
