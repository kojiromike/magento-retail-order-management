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
 * Set admin configuration needed for PayPal to work with the
 * extension.
 */
class EbayEnterprise_Eb2cPayment_Model_Paypal_Adminhtml_Config
{
	// Path to the paypal express checkout payment action setting.
	const PAYMENT_ACTION_CONFIG_PATH = 'payment/paypal_express/payment_action';
	// Path to the paypal express checkout enabler setting.
	const PAYPAL_EXPRESS_ENABLE_PATH = 'payment/paypal_express/active';
	// Path to the payments module enabler setting.
	const PAYMENT_MODULE_ACTIVE_PATH = 'eb2ccore/eb2cpayment/enabled';

	// @var Mage_Core_Model_Config Magento's global config
	protected $_mageConfig;
	// @var EbayEnterprise_MageLog_Helper_Data logging helper
	protected $_logger;

	/**
	 * Establish a logger and grab Magento's global config.
	 */
	public function __construct()
	{
		$this->_logger = Mage::helper('ebayenterprise_magelog');
		$this->_mageConfig = Mage::getConfig();
	}
	/**
	 * If the payment module and paypal express are enabled,
	 * set the payment action for express checkout payments to
	 * the order action.
	 * @param  string $store
	 * @param  string $website
	 * @return self
	 */
	public function applyExpressPaymentAction($store, $website)
	{
		list($scope, $scopeObj) = $this->_determineScope($store, $website);
		if ($this->_isPayPalExpressEnabled($scopeObj)) {
			$this->_logger->logInfo(
				'[%s] Setting Paypal Express payment action to "%s"',
				array(__CLASS__, Mage_Paypal_Model_Config::PAYMENT_ACTION_ORDER)
			);
			$scope = 'default';
			$scopeId = 0;
			if (!is_null($store)) {
				$scope = 'store';
				$scopeId = Mage::app()->getStore($store)->getId();
			} elseif (!is_null($website)) {
				$scope = 'website';
				$scopeId = Mage::app()->getWebsite($website)->getId();
			}
			$this->_mageConfig->saveConfig(
				self::PAYMENT_ACTION_CONFIG_PATH,
				Mage_Paypal_Model_Config::PAYMENT_ACTION_ORDER,
				$scope,
				$scopeObj->getId()
			);
		}
		return $this;
	}
	/**
	 * return true if the payment module and paypal express checkout
	 * are both enabled in the specified scope.
	 * Mage_Core_Model_Abstract $scopeObj
	 * @return bool
	 */
	protected function _isPayPalExpressEnabled(Mage_Core_Model_Abstract $scopeObj)
	{
		return $scopeObj->getConfig(self::PAYPAL_EXPRESS_ENABLE_PATH) &&
			$scopeObj->getConfig(self::PAYMENT_MODULE_ACTIVE_PATH);
	}
	/**
	 * get the scope string and object for the specified store/website.
	 * @param  string $store
	 * @param  string $website
	 * @return array(string, Mage_Core_Model_Abstract) the scope code and scope object
	 */
	protected function _determineScope($store=null, $website=null)
	{
		if (!is_null($store)) {
			$scope = 'store';
			$scopeObj = Mage::app()->getStore($store);
		} elseif (!is_null($website)) {
			$scope = 'website';
			$scopeObj = Mage::app()->getWebsite($website);
		} else {
			$scope = 'default';
			$scopeObj = Mage::app()->getWebsite(0);
		}
		return array($scope, $scopeObj);
	}
}
