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
	/**
	 * undo/cancel the PayPal payment
	 *
	 * @param  Varien_Event_Observer $observer
	 *
	 * @self
	 */
	public function rollbackExpressPayment(Varien_Event_Observer $observer)
	{
		$logger = Mage::helper('ebayenterprise_magelog');
		$logger->logDebug('[%s] Rollback event triggered', array(__CLASS__));
		$order = $observer->getEvent()->getOrder();
		$payment = $order->getPayment();
		$methodInstance = $payment->getMethodInstance();
		if ($methodInstance instanceof
			EbayEnterprise_Paypal_Model_Method_Express
			&& $methodInstance->canVoid($payment)
		) {
			$api = Mage::getModel('ebayenterprise_paypal/express_api');
			try {
				$logger->logDebug(
					'[%s] Sending void request', array(__CLASS__)
				);
				$api->doVoid($order);
				$logger->logDebug(
					'[%s] Void request completed', array(__CLASS__)
				);
			} catch (EbayEnterprise_PayPal_Exception $e) {
				$logger->logWarn('[%s] Void request failed', array(__CLASS__));
			}
		}
		return $this;
	}
}
