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
	const GIFTWRAP_NAME = 'EbayEnterprise_Eb2cPayment_Paypal_Giftwrap_Name';
	const GIFTWRAP_QTY = 1;
	const ROUNDING_ADJUSTMENT_NAME = 'EbayEnterprise_Eb2cPayment_Paypal_Promotional_Rounding_Adjustment';
	const ROUNDING_ADJUSTMENT_QTY = 1;
	const CURRENCY_FORMAT = '%.02F';

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
		$totals = $quote->getTotals();
		$curCodeAttr = array('currencyCode' => $quote->getQuoteCurrencyCode());
		$doc = $this->_getRequest($quote, $this->_getTotal($totals, 'grand_total'), $curCodeAttr);
		/** @var EbayEnterprise_Dom_Element $request */
		$request = $doc->documentElement;

		$lineItems = $request->createChild('LineItems', null)
			// value to be inserted below
			->addChild('LineItemsTotal', sprintf(self::CURRENCY_FORMAT, $this->_calculateQuoteSubtotal($quote)), $curCodeAttr)
			->addChild('ShippingTotal', sprintf(self::CURRENCY_FORMAT, $this->_getTotal($totals, 'shipping')), $curCodeAttr)
			->addChild('TaxTotal', sprintf(self::CURRENCY_FORMAT, $this->_getTotal($totals, 'tax')), $curCodeAttr);
		$this->_addLineItems($quote, $lineItems, $curCodeAttr);
		return $doc;
	}
	/**
	 * Add line items totals for items in the quote to the LineItems node.
	 * @param Mage_Sales_Model_Quote     $quote
	 * @param EbayEnterprise_Dom_Element $lineItemsNode
	 * @param array $curCodeAttr key/value pair representing currency code attribute
	 * @return self
	 */
	protected function _addLineItems(Mage_Sales_Model_Quote $quote, EbayEnterprise_Dom_Element $lineItemsNode, array $curCodeAttr)
	{
		foreach ($this->_calculateLineItemTotals($quote) as $line) {
			$this->_addLineItem($lineItemsNode, $line, $curCodeAttr);
		}
		return $this;
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
	 * Calculate line item totals for the given items. Returned values will
	 * have a "price", "name" and "qty". The returned "item" data will
	 * also include meta-line items for gift wrapping and, if necessary,
	 * a line item for correction rounding issues.
	 * @param Mage_Sales_Model_Quote  $quote
	 * @return EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total[]
	 */
	protected function _calculateLineItemTotals(Mage_Sales_Model_Quote $quote)
	{
		$quoteGiftWrapTotals = $this->_calculateQuoteGiftWrapLines($quote);
		// each item maps to 1 or 2 lines of totals (item + gift wrapping), flatten
		// each possible set of totals for a line into a single array of totals
		$itemTotals = call_user_func_array(
			'array_merge',
			array_map(array($this, '_calculateItemLines'), $quote->getAllItems())
		);
		$quoteTotals = array_merge($itemTotals, $quoteGiftWrapTotals);
		$roundingAdjustments = $this->_calculateRoundingAdjustmentLines($quote, $quoteTotals);
		// merge the quote totals with the rounding line (if there is one) to get the
		// complete list of item totals
		return array_merge($quoteTotals, $roundingAdjustments);
	}
	/**
	 * Get the line item totals for quote gift wrapping.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total[]
	 */
	protected function _calculateQuoteGiftWrapLines(Mage_Sales_Model_Quote $quote)
	{
		if ($quote->getGwId()) {
			return array($this->_createLineTotal($this->_helper->__(static::GIFTWRAP_NAME), $quote->getGwPrice(), static::GIFTWRAP_QTY));
		}
		return array();
	}
	/**
	 * Get the line item totals for a quote item, including any meta lines for
	 * item level gift wrapping.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @return EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total[]
	 */
	protected function _calculateItemLines(Mage_Sales_Model_Quote_Item $item)
	{
		$totals = array($this->_createLineTotal($item->getName(), $this->_calculateUnitAmount($item), $item->getQty()));
		if ($item->getGwId()) {
			$totals[] = $this->_createLineTotal($this->_helper->__(static::GIFTWRAP_NAME), $item->getGwPrice(), $item->getQty());
		}
		return $totals;
	}
	/**
	 * Compare the quote subtotal with discounts to the line totals that have
	 * been calculated. Any difference in quote subtotal to calculated lines
	 * should be captured and sent as a rounding correction line.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @param  array                  $totals
	 * @return EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total[]
	 */
	protected function _calculateRoundingAdjustmentLines(Mage_Sales_Model_Quote $quote, array $totals=array())
	{
		$quoteSubtotal = $this->_calculateQuoteSubtotal($quote);
		$calculatedTotal = array_reduce($totals, function ($carry, $item) { return $carry += $item->getPrice() * $item->getQty(); }, 0.0000);
		if ($quoteSubtotal > $calculatedTotal) {
			return array($this->_createLineTotal(
				$this->_helper->__(static::ROUNDING_ADJUSTMENT_NAME),
				// any rounding here should be negligible enough to round away (tiny
				// amounts simply due to the nature of floats)
				round($quoteSubtotal - $calculatedTotal, 2),
				static::ROUNDING_ADJUSTMENT_QTY
			));
		}
		return array();
	}
	/**
	 * Add '//LineItem[]' node to a passed in DOMElement object.
	 * @param DOMElement $lineItems
	 * @param EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total $line
	 * @param array $curCodeAttr key/value pair representing currency code attribute
	 * @return self
	 */
	protected function _addLineItem(DOMElement $lineItems, EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total $line, array $curCodeAttr)
	{
		$lineItems->createChild('LineItem', null)
			->addChild('Name', $line->getName())
			->addChild('Quantity', $line->getQty())
			->addChild('UnitAmount', $line->getFormattedPrice(self::CURRENCY_FORMAT), $curCodeAttr);
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
		// always round line totals down, rounding errors captured as a separate line
		return $this->_helper->floorToPrecision(($item->getRowTotal() - $item->getDiscountAmount()) / $item->getQty(), 2);
	}
	/**
	 * Calculates `//LineItems/LineItemsTotal' node value using the passed in 'sales/quote' class instance as parameter.
	 * Use the subtotal and discount from the passed in totals parameter plus the gift wrapping price on the quote plus
	 * the sum of all quote item gift wrap prices to calculate the line items total.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return float
	 */
	protected function _calculateQuoteSubtotal(Mage_Sales_Model_Quote $quote)
	{
		return (float) ($quote->getGwPrice() + $quote->getSubtotalWithDiscount() + $quote->getGwItemsPrice());
	}
	/**
	 * Get a new line total instance.
	 * @param string $name  Name of the line
	 * @param float $price Price of the line
	 * @param float $qty   Qty of the line
	 * @return EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total
	 */
	protected function _createLineTotal($name, $price, $qty)
	{
		return Mage::getModel('eb2cpayment/paypal_line_total', array('name' => $name, 'price' => $price, 'qty' => $qty));
	}
	/**
	 * Get the order id reserved by the quote.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return string
	 */
	protected function _getReservedOrderId(Mage_Sales_Model_Quote $quote)
	{
		return $quote->reserveOrderId()->getReservedOrderId();
	}
}
