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

class EbayEnterprise_Eb2cTax_Helper_Data extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	protected $_service        = 'taxes';
	protected $_operation      = 'quote';
	protected $_responseFormat = 'xml';

	protected $_apiModel       = null;

	/**
	 * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
	 * @param mixed $store
	 * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('eb2ctax/config'));
	}

	/**
	 * Request tax and duty information from EB2C and return the xml response string.
	 *
	 * @param EbayEnterprise_Eb2cTax_Model_Request $request
	 * @return EbayEnterprise_Eb2cTax_Model_Response
	 */
	public function sendRequest(EbayEnterprise_Eb2cTax_Model_Request $request)
	{
		return Mage::getModel('eb2ctax/response', array(
			'request' => $request,
			'xml' => Mage::getModel('eb2ccore/api')->request(
				$request->getDocument(),
				$this->getConfigModel()->xsdFileTaxDutyFeeQuoteRequest,
				Mage::helper('eb2ccore')->getApiUri($this->_service, $this->_operation, array(), $this->_responseFormat)
			),
		));
	}
	/**
	 * Record the tax request failure - flip the flag in the eb2ccore/session to true.
	 * @return self
	 */
	public function failTaxRequest()
	{
		Mage::log(sprintf('[%s] Failing tax request', __CLASS__), Zend_Log::DEBUG);
		Mage::getSingleton('eb2ccore/session')->setHaveTaxRequestsFailed(true);
	}
	/**
	 * Determine if a tax request is needed for the given address object. Request is only needed
	 * if the eb2ccore/session tax flag is true, no previous errors have been encountered by
	 * tax requests (flag also stored in eb2ccore/session but managed solely by tax module)
	 * and the given address has items
	 * @param  Mage_Sales_Model_Quote_Address $address Address request would be for
	 * @return bool True if request should be made, false otherwise
	 */
	public function isRequestForAddressRequired(Mage_Sales_Model_Quote_Address $address)
	{
		$session = Mage::getSingleton('eb2ccore/session');
		$session->updateWithQuote($address->getQuote());
		return $session->isTaxUpdateRequired() &&
			!$session->getHaveTaxRequestsFailed() &&
			$address->getAllVisibleItems();
	}
	/**
	 * unset the trigger so the tax request will not be sent until triggerRequest is called.
	 * @return self
	 */
	public function cleanupSessionFlags()
	{
		$session = Mage::getSingleton('eb2ccore/session');
		if (!$session->getHaveTaxRequestsFailed()) {
			$session->resetTaxUpdateRequired();
		}
		$session->unsHaveTaxRequestsFailed();
		return $this;
	}

	/**
	 * get the default namespace
	 * @param  Mage_Core_Model_Store $store
	 * @return int
	 */
	public function getNamespaceUri($store=null)
	{
		return Mage::helper('eb2ccore')->getConfigModel($store)->apiNamespace;
	}

	/**
	 * return true if the prices already include VAT.
	 *
	 * @param null $store
	 * @return bool
	 */
	public function getVatInclusivePricingFlag($store=null)
	{
		return $this->getConfigModel($store)->taxVatInclusivePricing;
	}

	/**
	 * @param mixed $store
	 * @return bool configuration flag
	 */
	public function getApplyTaxAfterDiscount($store=null)
	{
		return $this->getConfigModel($store)->taxApplyAfterDiscount;
	}

	/**
	 * @param mixed $store
	 * @return float
	 */
	public function taxDutyAmountRateCode($store=null)
	{
		return $this->getConfigModel($store)->taxDutyRateCode;
	}
}
