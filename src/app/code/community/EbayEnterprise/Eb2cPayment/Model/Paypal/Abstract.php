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

abstract class EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract
{
	const STORED_FIELD_PREFIX = 'eb2c_paypal';
	const PAYPAL_REQUEST_FAILED_TRANSLATE_KEY = 'EbayEnterprise_Eb2cPayment_Paypal_Request_Failed';
	const PAYPAL_REQUEST_WARNING_FORMAT = "Request succeeded with warnings.\n%s";
	const SUCCESS = 'SUCCESS';
	const SUCCESSWITHWARNINGS = 'SUCCESSWITHWARNINGS';
	const ERROR_MESSAGE_ELEMENT = '';

	/**
	 * Do paypal express checkout from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to do express paypal checkout for in eb2c
	 * @return string the eb2c response to the request
	 */
	public function processExpressCheckout(Mage_Sales_Model_Quote $quote)
	{
		$helper = Mage::helper('eb2cpayment');
		$response = Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH)
			->request(
				$this->_buildRequest($quote),
				$helper->getConfigModel()->getConfig(static::XSD_FILE),
				$helper->getOperationUri(static::URI_KEY)
			);
		$this->_savePaymentData($this->parseResponse($response), $quote);
		return $response;
	}

	/**
	 * Save payment data to quote_payment.
	 *
	 * @param Varien_Object $checkoutObject response data
	 * @param Mage_Sales_Model_Quote $quote sales quote instantiated object
	 * @return EbayEnterprise_Eb2cPayment_Model_Paypal|null
	 */
	public function _savePaymentData(Varien_Object $checkoutObject, Mage_Sales_Model_Quote $quote)
	{
		$storedData = $checkoutObject->getData(static::STORED_FIELD);
		if ($storedData !== '') {
			$saveToField = sprintf('%s_%s', static::STORED_FIELD_PREFIX, static::STORED_FIELD);
			$qId = $quote->getEntityId();
			return Mage::getModel('eb2cpayment/paypal')
				->loadByQuoteId($qId)
				->setQuoteId($qId)
				->setData($saveToField, $storedData)
				->save();
		}
		return null;
	}

	/**
	 * extract error messages from the response and return them as a single
	 * string
	 * @param  string   $errorPath
	 * @param  DOMXPath $xpath
	 * @return string
	 */
	protected function _extractMessages($errorPath, DOMXPath $xpath)
	{
		$delim = '';
		$messages = '';
		$errorMessages = $xpath->query($errorPath) ?: new NodeList();
		foreach($errorMessages as $node) {
			$messages .= $delim . (string) $node->nodeValue;
			$delim = ' ';
		}
		return $messages;
	}

	/**
	 * prevent the checkout process from moving forward if the response code indicates
	 * the request failed
	 * @param  string   $responseCode
	 * @param  DOMXPath $xpath
	 * @throws EbayEnterprise_Eb2cPayment_Model_Paypal_Exception if PayPal returns a failure response
	 */
	protected function _blockIfRequestFailed($responseCode, DOMXPath $xpath)
	{
		$responseCode = strtoupper($responseCode);
		if ($responseCode !== static::SUCCESS) {
			$messages = $this->_extractMessages(static::ERROR_MESSAGE_ELEMENT, $xpath);
			if ($responseCode === static::SUCCESSWITHWARNINGS) {
				Mage::helper('ebayenterprise_magelog')
					->logWarn(static::PAYPAL_REQUEST_WARNING_FORMAT, array($messages));
			} else {
				$e = new EbayEnterprise_Eb2cPayment_Model_Paypal_Exception(
					Mage::helper('eb2cpayment')
						->__(static::PAYPAL_REQUEST_FAILED_TRANSLATE_KEY, $messages)
				);
				// this exception is logged when caught in
				// Mage_Paypal_Controller_Express_Abstract::placeOrderAction
				throw $e;
			}
		}
	}
}
