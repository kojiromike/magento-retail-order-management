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

use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents;

class EbayEnterprise_Eb2cOrder_Model_Creditissued
{
	/** @var  OrderEvents\IOrderCreditIssued $_payload */
	protected $_payload;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var Mage_Sales_Model_Order_Creditmemo */
	protected $_creditMemo;
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/**
	 * @param array $initParams Must include the payload key:
	 *                          - 'payload' => OrderEvents\OrderCreditIssued
	 *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
	 */
	public function __construct(array $initParams=array())
	{
		list($this->_payload, $this->_logger) = $this->_checkTypes(
			$initParams['payload'],
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog'))
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 * @param  OrderEvents\IOrderCreditIssued $payload
	 * @param  EbayEnterprise_MageLog_Helper_Data $logger
	 * @return array
	 */
	protected function _checkTypes(
		OrderEvents\IOrderCreditIssued $payload,
		EbayEnterprise_MageLog_Helper_Data $logger
	) {
		return array($payload, $logger);
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the default value.
	 * @param  array $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Verify that the requested refund amount is allowed
	 *
	 * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
	 * @param float $amount
	 * @return bool
	 */
	protected function _canRefundAmount(Mage_Sales_Model_Order_Creditmemo $creditmemo, $amount)
	{
		return $amount <= $creditmemo->getBaseCustomerBalanceReturnMax();
	}

	/**
	 * @param string $orderId
	 * @return Mage_Sales_Model_Order|false
	 */
	protected function _getOrder($orderId)
	{
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if (!$order->getId()) {
			$this->_logger->logWarn('[%s] Customer order id %s was not found.', array(__CLASS__, $orderId));
			return false;
		}

		return $order;
	}

	/**
	 * Append a new quantity index to the passed in 'qtys' array when a match Magento
	 * order item is found in the pass in items collection.
	 * @param  OrderEvents\IOrderItem $orderItem
	 * @param  Varien_Data_Collection $items
	 * @param  array $qtys
	 * @return self
	 */
	protected function _appendToQtyArray(OrderEvents\IOrderItem $orderItem, Varien_Data_Collection $items, array $qtys)
	{
		$item = $items->getItemByColumnValue('sku', $orderItem->getItemId());
		if ($item) {
			// Magento only support integer quantity
			$qtys[$item->getItemId()] = (int) $orderItem->getQuantity();
		}
		return $qtys;
	}

	/**
	 * return float $total
	 */
	protected function _totalLineItemsCredit()
	{
		$total = 0.0;
		foreach ($this->_payload->getOrderItems() as $item) {
			$total += $item->getAmount();
		}

		return total;
	}

	/**
	 * Set all the return qtys to 0
	 *
	 * @return array
	 */
	protected function _clearReturnQtys()
	{
		$qtys = array();
		foreach ($this->_order->getItemsCollection() as $item) {
			$qtys[$item->getItemId()] = 0;
		}

		return $qtys;
	}

	/**
	 * @return array quantity of each item to be returned
	 */
	protected function _refundLineItems()
	{
		$data = array();
		$qtys = $this->_clearReturnQtys();
		if ($this->_payload->isReturn()) {
			$items = $this->_order->getItemsCollection();
			foreach ($this->_payload->getOrderItems() as $orderItem) {
				$qtys = $this->_appendToQtyArray($orderItem, $items, $qtys);
			}
		}
		$data['qtys'] = $qtys;

		return $data;
	}

	protected function _creditmemoInitData()
	{
		$data = $this->_refundLineItems();

		// totalCredit is passed as a negative number but the credit memo uses
		// positive numbers for refunds so use abs() to get rid of negative numbers
		$amount = abs($this->_payload->getTotalCredit());
		if (strtolower($this->_payload->getReturnOrCredit()) === 'credit') {
			$data['adjustment_positive'] = $amount;
		}

		return $data;
	}

	/**
	 * The grand total is explicitly set from the totalCredit value in the payload
	 * Compute any adjustments necessary to make the credit memo total up properly
	 *
	 * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
	 * @return Mage_Sales_Model_Order_Creditmemo
	 */
	protected function _fixupTotals(Mage_Sales_Model_Order_Creditmemo $creditmemo)
	{
		$creditmemo->setBaseShippingAmount(0.0)
			->setShippingAmount(0.0)
			->setBaseTaxAmount(0.0)
			->setTaxAmount(0.0);

		// totalCredit is passed as a negative number but the credit memo uses
		// positive numbers for refunds so use abs() to get rid of negative numbers
		$grandTotal = abs($this->_payload->getTotalCredit());
		$subTotal = $creditmemo->getBaseSubtotal();
		$creditmemo->setBaseGrandTotal($grandTotal);
		$creditmemo->setGrandTotal($grandTotal);
		if ($grandTotal < $subTotal) {
			$creditmemo->setAdjustmentNegative($subTotal - $grandTotal);
		} elseif ($grandTotal > $subTotal) {
			$creditmemo->setAdjustmentPositive($grandTotal - $subTotal);
		}

		return $creditmemo;
	}

	/**
	 * Save the credit memo and the related order and invoice
	 *
	 * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
	 */
	protected function _saveCreditMemo(Mage_Sales_Model_Order_Creditmemo $creditmemo)
	{
		$creditmemo = $this->_fixupTotals($creditmemo);

		try {
			$creditmemo->register();
		} catch (Mage_Core_Exception $e) {
			$this->_logger->logWarn('[%s] Could not refund order %s', array(__CLASS__, $creditmemo->getOrderId()));
			$this->_logger->logException($e);
		}

		$transactionSave = Mage::getModel('core/resource_transaction')
			->addObject($creditmemo)
			->addObject($creditmemo->getOrder());
		if ($creditmemo->getInvoice()) {
			$transactionSave->addObject($creditmemo->getInvoice());
		}

		try {
			$transactionSave->save();
		} catch (Exception $e) {
			$this->_logger->logWarn('[$s] Could not save credit memo for order %s', array(__CLASS__, $creditmemo->getOrderId()));
			$this->_logger->logException($e);
		}
	}

	/**
	 * Uses Magento to update save the credit memo and update the associated
	 * order, invoice and payment tables
	 *
	 * The credit memo is set to process the refund offline and not call
	 * the payment refund method since OMS has already processed the credit.
	 * This just updates the magento database to reflect the credited amount.
	 *
	 * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
	 * @param float $amount
	 */
	protected function _refund(Mage_Sales_Model_Order_Creditmemo $creditmemo, $amount)
	{
		if (!$this->_canRefundAmount($creditmemo, $amount)) {
			$this->_logger->logWarn('[%s] Requested refund amount $%F exceeds allowable refund amount $%F for order %s',
				array(
					__CLASS__,
					$amount,
					$creditmemo->getBaseCustomerBalanceReturnMax(),
					$this->_payload->getCustomerOrderId()
				)
			);
			return;
		}

		$creditmemo->setOfflineRequested(true)
			->setPaymentRefundDisallowed(true);
		$this->_saveCreditMemo($creditmemo);
	}

	/**
	 * @param Mage_Sales_Model_Order_Invoice $invoice
	 */
	protected function _processInvoicedCreditMemo(Mage_Sales_Model_Order_Invoice $invoice)
	{
		$service = Mage::getModel('sales/service_order', $this->_order);
		$creditmemo = $service->prepareInvoiceCreditmemo($invoice, $this->_creditmemoInitData());
		// totalCredit is passed as a negative number but the credit memo uses
		// positive numbers for refunds so use abs() to get rid of negative numbers
		$this->_refund($creditmemo, abs($this->_payload->getTotalCredit()));
	}

	/**
	 *
	 */
	protected function _processInvoicedCreditMemos()
	{
		foreach ($this->_order->getInvoiceCollection() as $invoice) {
			if ($invoice->canRefund()) {
				$this->_processInvoicedCreditMemo($invoice);
			}
		}
	}

	/**
	 *
	 */
	protected function _processCreditMemo()
	{
		$service = Mage::getModel('sales/service_order', $this->_order);
		$creditmemo = $service->prepareCreditmemo($this->_creditmemoInitData());
		// totalCredit is passed as a negative number but the credit memo uses
		// positive numbers for refunds so use abs() to get rid of negative numbers
		$this->_refund($creditmemo, abs($this->_payload->getTotalCredit()));
	}

	/**
	 * @return self
	 */
	public function process()
	{
		$orderId = $this->_payload->getCustomerOrderId();
		$this->_order = $this->_getOrder($orderId);
		if (!$this->_order) {
			$this->_logger->logWarn('[%s] Order "%s" not found in Magento. Could not process that order.', array(__CLASS__, $orderId));
			return $this;
		}

		if (!$this->_order->canCreditMemo()) {
			$this->_logger->logWarn('[%s] Credit memo cannot be created for order %s', array(__CLASS__, $orderId));
			return $this;
		}

		$this->_order->getAllVisibleItems();
		if ($this->_order->hasInvoices()) {
			$this->_processInvoicedCreditMemos();
		} else {
			$this->_processCreditMemo();
		}

		return $this;
	}
}
