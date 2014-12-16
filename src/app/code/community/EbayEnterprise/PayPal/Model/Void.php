<?php
/**
 * Created by PhpStorm.
 * User: smithm5
 * Date: 12/16/14
 * Time: 3:58 PM
 */

/**
 * Class EbayEnterprise_PayPal_Model_Void
 *
 * Handle voiding PayPal auth
 */
class EbayEnterprise_PayPal_Model_Void {
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_log;

	/**
	 * Set up the logger
	 */
	public function __construct()
	{
		$this->_log = Mage::helper('ebayenterprise_magelog');
	}

	/**
	 * @param Mage_Sales_Model_Order
	 * @return self
	 */
	public function void(Mage_Sales_Model_Order $order)
	{
		if (!$this->_canVoid($order)) {
			return $this;
		}
		$logClass = array(__CLASS__);
		$this->_log->logDebug('[%s] Sending void request', $logClass);
		try {
			$this->_getVoidApi()->doVoid($order);
			$this->_log->logDebug('[%s] Void request completed', $logClass);
		} catch (EbayEnterprise_PayPal_Exception $e) {
			$this->_log->logWarn('[%s] Void request failed', $logClass);
		}
		return $this;
	}

	/**
	 * Check if we can void the PayPal payment method in this order.
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return bool
	 */
	protected function _canVoid(Mage_Sales_Model_Order $order)
	{
		$payment = $order->getPayment();
		if (!$payment instanceof Mage_Sales_Model_Order_Payment) {
			return false;
		}
		$methodInstance = $payment->getMethodInstance();
		return (
			$methodInstance instanceof EbayEnterprise_Paypal_Model_Method_Express
			&& $methodInstance->canVoid($payment)
		);
	}

	/**
	 * @return EbayEnterprise_PayPal_Model_Express_Api
	 */
	protected function _getVoidApi()
	{
		return Mage::getModel('ebayenterprise_paypal/express_api');
	}
}