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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer;

/**
 * Singleton model to perform default handling of payments.
 */
class EbayEnterprise_Order_Model_Create_Payment
{
	/**
	 * Make prepaid credit card payloads for any payments
	 * remaining in the list
	 * @param Mage_Sales_Model_Order $order
	 * @param IPaymentContainer      $paymentContainer
	 * @param SplObjectStorage       $processedPayments
	 */
	public function addPaymentsToPayload(
		Mage_Sales_Model_Order $order,
		IPaymentContainer $paymentContainer,
		SplObjectStorage $processedPayments
	) {
		foreach ($order->getAllPayments() as $payment) {
			if ($this->_shouldIgnorePayment($payment, $processedPayments)) {
				continue;
			}
			$iterable = $paymentContainer->getPayments();
			$payload = $iterable->getEmptyPrepaidCreditCardPayment();
			$ccType = $payment->getCcType();
			if ($ccType) {
				$payload->setBrand($this->_getCcTypeName($ccType));
			}
			$payload->setAmount($payment->getAmountOrdered());
			$iterable[$payload] = $payload;
		}
	}

	/**
	 * Retrieve credit card type name
	 *
	 * @param  string $ccType
	 * @return string
	 */
	protected function _getCcTypeName($ccType)
	{
		$types = Mage::getSingleton('payment/config')->getCcTypes();
		if (isset($types[$ccType])) {
			return $types[$ccType];
		}
		return null;
	}

	/**
	 * return true if the payment should not be processed
	 *
	 * @param  Mage_Sales_Model_Order_Payment $payment
	 * @param  SplObjectStorage               $processedPayments
	 * @return bool
	 */
	protected function _shouldIgnorePayment(Mage_Sales_Model_Order_Payment $payment, SplObjectStorage $processedPayments)
	{
		return $processedPayments->offsetExists($payment) ||
			$payment->getMethod() === Mage::getModel('payment/method_free')->getCode();
	}
}
