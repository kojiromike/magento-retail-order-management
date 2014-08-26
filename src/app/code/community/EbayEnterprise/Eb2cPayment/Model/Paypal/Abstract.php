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

	/** @var EbayEnterprise_Eb2cPayment_Helper_Data $_helper */
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data $_coreHelper */
	protected $_coreHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;
	/** @var string $_xmlNs The xml namespace for the payment service */
	protected $_xmlNs;

	/**
	 * Initialize members
	 */
	public function __construct()
	{
		$this->_helper = Mage::helper('eb2cpayment');
		$this->_coreHelper = Mage::helper('eb2ccore');
		$this->_log = Mage::helper('ebayenterprise_magelog');
		$this->_xmlNs = $this->_helper->getXmlNs();
	}

	/**
	 * Do paypal express checkout from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to do express paypal checkout for in eb2c
	 * @return string the eb2c response to the request
	 */
	public function processExpressCheckout(Mage_Sales_Model_Quote $quote)
	{
		$helper = $this->_helper;
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
		$errorMessages = $xpath->query($errorPath) ?: new DomNodeList();
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
				$this->_log->logWarn(static::PAYPAL_REQUEST_WARNING_FORMAT, array($messages));
			} else {
				$e = new EbayEnterprise_Eb2cPayment_Model_Paypal_Exception(
					$this->_helper->__(static::PAYPAL_REQUEST_FAILED_TRANSLATE_KEY, $messages)
				);
				// this exception is logged when caught in
				// Mage_Paypal_Controller_Express_Abstract::placeOrderAction
				throw $e;
			}
		}
	}

	/**
	 * Build common request for set and do.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return EbayEnterprise_Dom_Document The XML document to be sent as request to eb2c.
	 */
	protected function _buildRequest(Mage_Sales_Model_Quote $quote)
	{
		/**
		 * @var float $gwPrice price of order level gift wrapping
		 * @var string $gwId id of giftwrapping object
		 * @var EbayEnterprise_Dom_Element $request
		 * @var array $addresses
		 */
		$gwPrice = $quote->getGwPrice();
		$gwId = $quote->getGwId();
		$totals = $quote->getTotals();
		$grandTotal = isset($totals['grand_total']) ? $totals['grand_total']->getValue() : 0;
		$shippingTotal = isset($totals['shipping']) ? $totals['shipping']->getValue() : 0;
		$taxTotal = isset($totals['tax']) ? $totals['tax']->getValue() : 0;
		$lineItemsTotal = (isset($totals['subtotal']) ? $totals['subtotal']->getValue() : 0) + $gwPrice;
		$curCodeAttr = array('currencyCode' => $quote->getQuoteCurrencyCode());
		$doc = $this->_getRequest($quote, $grandTotal, $curCodeAttr);
		$request = $doc->documentElement;

		$lineItems = $request->createChild('LineItems', null);
		$lineItemsTotalNode = $lineItems->createChild('LineItemsTotal', null, $curCodeAttr); // value to be inserted below
		$lineItems
			->addChild('ShippingTotal', sprintf('%.02f', $shippingTotal), $curCodeAttr)
			->addChild('TaxTotal', sprintf('%.02f', $taxTotal), $curCodeAttr);

		if ($gwId) {
			$lineItems
				->createChild('LineItem', null)
				->addChild('Name', 'GiftWrap')
				->addChild('Quantity', '1')
				->addChild('UnitAmount', sprintf('%.02f', $gwPrice), $curCodeAttr);
		}
		foreach($quote->getAllAddresses() as $addresses){
			foreach ($addresses->getAllItems() as $item) {
				// If gw_price is empty, php will treat it as zero.
				$lineItemsTotal += $item->getGwPrice();
				$lineItems
					->createChild('LineItem', null)
					->addChild('Name', (string) $item->getName())
					->addChild('Quantity', (string) $item->getQty())
					->addChild('UnitAmount', sprintf('%.02f', $item->getPrice()), $curCodeAttr);
				$itemGwId = $item->getGwId();
				if ($itemGwId) {
					$lineItems
						->createChild('LineItem', null)
						->addChild('Name', 'GiftWrap')
						->addChild('Quantity', '1')
						->addChild('UnitAmount', sprintf('%.02f', $item->getGwPrice()), $curCodeAttr);
				}
			}
		}
		$lineItemsTotalNode->nodeValue = sprintf('%.02f', $lineItemsTotal);
		return $doc;
	}
}
