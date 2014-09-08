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
 * Implementation of eBay Enterprise credit card processing via Payment Bridge
 */
class EbayEnterprise_Pbridge_Model_Cc extends Mage_Payment_Model_Method_Cc
{
	/**
	 * Payment method code
	 *
	 * @var string
	 */
	const PAYMENT_CODE = 'pbridge_eb2cpayment_cc';

	protected $_code = self::PAYMENT_CODE;

	protected $_isGateway               = true;
	protected $_canAuthorize            = true;
	protected $_canCapture              = false;
	protected $_canCapturePartial       = false;
	protected $_canRefund               = false;
	protected $_canRefundInvoicePartial = false;
	protected $_canVoid                 = false;
	protected $_canUseInternal          = true;
	protected $_canUseCheckout          = true;
	protected $_canUseForMultishipping  = true;
	protected $_canSaveCc               = false;

	/**
	 * Form block type for the frontend
	 *
	 * @var string
	 */
	protected $_formBlockType = 'ebayenterprise_pbridge/checkout_payment_cc';

	/**
	 * Form block type for the backend
	 *
	 * @var string
	 */
	protected $_backendFormBlockType = 'ebayenterprise_pbridge/adminhtml_sales_order_create_cc';

	/**
	 * Payment Bridge Payment Method Instance
	 *
	 * @var Enterprise_Pbridge_Model_Payment_Method_Pbridge
	 */
	protected $_pbridgeMethodInstance = null;

	/**
	 * Return that current payment method is dummy
	 *
	 * @return boolean
	 */
	public function getIsDummy()
	{
		return true;
	}

	/**
	 * Return Payment Bridge method instance
	 *
	 * @return Enterprise_Pbridge_Model_Payment_Method_Pbridge
	 */
	public function getPbridgeMethodInstance()
	{
		if ($this->_pbridgeMethodInstance === null) {
			$this->_pbridgeMethodInstance = Mage::helper('payment')->getMethodInstance('pbridge');
			if ($this->_pbridgeMethodInstance) {
				$this->_pbridgeMethodInstance->setOriginalMethodInstance($this);
			}
		}
		return $this->_pbridgeMethodInstance;
	}

	/**
	 * Retrieve original payment method code
	 *
	 * @return string
	 */
	public function getOriginalCode()
	{
		return parent::getCode();
	}

	/**
	 * Assign data to info model instance
	 *
	 * @param  mixed $data
	 * @return Mage_Payment_Model_Info
	 */
	public function assignData($data)
	{
		$this->getPbridgeMethodInstance()->assignData($data);
		return $this;
	}

	/**
	 * Check whether payment method can be used
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @return boolean
	 */
	public function isAvailable($quote = null)
	{
		return $this->getPbridgeMethodInstance() && $this->getPbridgeMethodInstance()->isDummyMethodAvailable($quote);
	}

	/**
	 * Retrieve block type for method form generation
	 *
	 * @return string
	 */
	public function getFormBlockType()
	{
		return Mage::app()->getStore()->isAdmin() ?
			$this->_backendFormBlockType :
			$this->_formBlockType;
	}

	/**
	 * Validate payment method information object
	 *
	 * @return Enterprise_Pbridge_Model_Payment_Method_Authorizenet
	 */
	public function validate()
	{
		$this->getPbridgeMethodInstance()->validate();
		return $this;
	}

	/**
	 * Authorization method being executed via Payment Bridge
	 *
	 * @param Varien_Object $payment
	 * @param float $amount
	 * @return Enterprise_Pbridge_Model_Payment_Method_Authorizenet
	 */
	public function authorize(Varien_Object $payment, $amount)
	{
		$response = $this->getPbridgeMethodInstance()->authorize($payment, $amount);
		$payment->addData((array)$response);
		$authCodes = array(
			'response_code',
			'bank_authorization_code',
			'cvv2_response_code',
			'avs_response_code',
			'expiration_date',
			'cc_type',
			'gateway_transaction_id',
			'phone_response_code',
			'name_response_code',
			'email_response_code',
			'tender_code',
			'request_id',
		);
		foreach ($authCodes as $code) {
			if (isset($response[$code])) {
				$payment->setAdditionalInformation($code, $response[$code]);
			}
		}
		return $this;
	}

	/**
	 * Store id setter, also set storeId to helper
	 *
	 * @param int $store
	 * @return Enterprise_Pbridge_Model_Payment_Method_Dibs
	 */
	public function setStore($store)
	{
		$this->setData('store', $store);
		Mage::helper('enterprise_pbridge')->setStoreId(is_object($store) ? $store->getId() : $store);
		return $this;
	}
}
