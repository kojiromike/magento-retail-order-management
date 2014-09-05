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
	const GIFTWRAP_NAME = 'GiftWrap';
	const GIFTWRAP_QTY = '1';

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
	protected function _savePaymentData(Varien_Object $checkoutObject, Mage_Sales_Model_Quote $quote)
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

	/**
	 * Build common request for set and do.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return EbayEnterprise_Dom_Document The XML document to be sent as request to eb2c.
	 */
	protected function _buildRequest(Mage_Sales_Model_Quote $quote)
	{
		$totals = $quote->getTotals();
		$curCodeAttr = array('currencyCode' => $quote->getQuoteCurrencyCode());
		$doc = $this->_getRequest($quote, $this->_getTotal($totals, 'grand_total'), $curCodeAttr);
		/** @var EbayEnterprise_Dom_Element $request */
		$request = $doc->documentElement;

		$lineItems = $request->createChild('LineItems', null)
			// value to be inserted below
			->addChild('LineItemsTotal', $this->_calculateLineItemsTotal($quote), $curCodeAttr)
			->addChild('ShippingTotal', sprintf('%.02f', $this->_getTotal($totals, 'shipping')), $curCodeAttr)
			->addChild('TaxTotal', sprintf('%.02f', $this->_getTotal($totals, 'tax')), $curCodeAttr);

		if ($quote->getGwId()) {
			$this->_addLineItem($lineItems, $quote->getGwPrice(), $curCodeAttr, static::GIFTWRAP_NAME, static::GIFTWRAP_QTY);
		}
		foreach($quote->getAllItems() as $item) {
			$this->_addLineItem($lineItems, $this->_calculateUnitAmount($item), $curCodeAttr, $item->getName(), $item->getQty());
			if ($item->getGwId()) {
				$this->_addLineItem($lineItems, $item->getGwPrice(), $curCodeAttr, static::GIFTWRAP_NAME, static::GIFTWRAP_QTY);
			}
		}
		return $doc;
	}
	/**
	 * If the total type is in the array, use it. Otherwise 0
	 * @param  array $totals
	 * @param  string $totalType
	 * @return float
	 */
	protected function _getTotal(array $totals, $totalType)
	{
		return (float) (isset($totals[$totalType]) ? $totals[$totalType]->getValue() : 0);
	}
	/**
	 * Add '//LineItem[]' node to a passed in DOMElement object.
	 * @param  DOMElement $lineItems
	 * @param  float $price
	 * @param  array $curCodeAttr
	 * @param  string $name
	 * @param  string $qty
	 * @return self
	 */
	protected function _addLineItem(DOMElement $lineItems, $price, array $curCodeAttr, $name, $qty='1')
	{
		$lineItems->createChild('LineItem', null)
			->addChild('Name', (string) $name)
			->addChild('Quantity', (string) $qty)
			->addChild('UnitAmount', sprintf('%.02f', $price), $curCodeAttr);
		return $this;
	}
	/**
	 * Calculates `//LineItem/UnitAmount' node value using the passed in 'sales/quote_item' class instance parameter.
	 * Subtracts item discount amount from item row total, and then divide it by the item quantity to get the correct
	 * unit amount value.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @return float
	 */
	protected function _calculateUnitAmount(Mage_Sales_Model_Quote_Item $item)
	{
		return ($item->getRowTotal() - $item->getDiscountAmount()) / $item->getQty();
	}
	/**
	 * Calculates `//LineItems/LineItemsTotal' node value using the passed in 'sales/quote' class instance as parameter.
	 * Use the subtotal and discount from the passed in totals parameter plus the gift wrapping price on the quote plus
	 * the sum of all quote item gift wrap prices to calculate the line items total.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return float
	 */
	protected function _calculateLineItemsTotal(Mage_Sales_Model_Quote $quote)
	{
		return (float) ($quote->getGwPrice() + $quote->getSubtotalWithDiscount() + $this->_sumItemGwPrice($quote->getAllItems()));
	}
	/**
	 * Sums up all quote item gift wrap prices from a passed in array of 'sales/order_item' class instances.
	 * @param  array $items
	 * @return float
	 */
	protected function _sumItemGwPrice(array $items=array())
	{
		$gwTotal = 0;
		foreach ($items as $item) {
			$gwTotal += $item->getGwPrice();
		}
		return $gwTotal;
	}
}
