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

class EbayEnterprise_Eb2cPayment_Model_Paypal_Set_Express_Checkout extends EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract
{
	// A mapping to something in the helper. Pretty contrived.
	const URI_KEY = 'get_paypal_set_express_checkout';
	const XSD_FILE = 'xsd_file_paypal_set_express';
	const STORED_FIELD = 'token';
	const ERROR_MESSAGE_ELEMENT = '//a:ErrorMessage';

	/**
	 * Build PaypalSetExpressCheckout request.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The XML document to be sent as request to eb2c.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	protected function _buildRequest(Mage_Sales_Model_Quote $quote)
	{
		$totals = $quote->getTotals();
		Mage::log(array_keys($totals['grand_total']->getData()));
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalSetExpressCheckoutRequest = $domDocument->addElement('PayPalSetExpressCheckoutRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalSetExpressCheckoutRequest->createChild('OrderId', (string) $quote->getEntityId());
		$payPalSetExpressCheckoutRequest->createChild('ReturnUrl', (string) Mage::getUrl('*/*/return'));
		$payPalSetExpressCheckoutRequest->createChild('CancelUrl', (string) Mage::getUrl('*/*/cancel'));
		$payPalSetExpressCheckoutRequest->createChild('LocaleCode', (string) Mage::app()->getLocale()->getDefaultLocale());
		$payPalSetExpressCheckoutRequest->createChild(
			'Amount',
			sprintf('%.02f', (isset($totals['grand_total']) ? $totals['grand_total']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		$lineItems = $payPalSetExpressCheckoutRequest->createChild('LineItems', null);
		// LineItemsTotal _must_ come first and I need to add in Gift Wrap. So I end up looping
		// twice. Surely there's a more efficient way to do this.
		$lineItemsTotal = isset($totals['subtotal']) ? $totals['subtotal']->getValue() : 0;
		if ($quote) {
			$lineItemsTotal += $quote->getGwPrice();
			foreach ($quote->getAllAddresses() as $addresses) {
				if ($addresses) {
					foreach ($addresses->getAllItems() as $item) {
						if ($item->getGwPrice()) {
							$lineItemsTotal += $item->getGwPrice();
						}
					}
				}
			}
		}
		$lineItems->createChild(
			'LineItemsTotal',
			sprintf('%.02f', $lineItemsTotal),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);

		// add ShippingTotal
		$lineItems->createChild(
			'ShippingTotal',
			sprintf('%.02f', (isset($totals['shipping']) ? $totals['shipping']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		// add TaxTotal
		$lineItems->createChild(
			'TaxTotal',
			sprintf('%.02f', (isset($totals['tax']) ? $totals['tax']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		if ($quote) {
			if ($quote->getGwPrice()) {
				$lineItem = $lineItems->createChild('LineItem', null);
				$lineItem->createChild('Name', 'GiftWrap');
				$lineItem->createChild('Quantity', '1');
				$lineItem->createChild('UnitAmount', sprintf('%.02f', $quote->getGwPrice()), array('currencyCode' => $quote->getQuoteCurrencyCode()));
			}
			foreach($quote->getAllAddresses() as $addresses) {
				if ($addresses) {
					foreach ($addresses->getAllItems() as $item) {
						$lineItem = $lineItems->createChild('LineItem', null);
						$lineItem->createChild('Name', (string) $item->getName());
						$lineItem->createChild('Quantity', (string) $item->getQty());
						$lineItem->createChild('UnitAmount', sprintf('%.02f', $item->getPrice()), array('currencyCode' => $quote->getQuoteCurrencyCode()));
						if ($item->getGwPrice()) {
							$lineItem = $lineItems->createChild('LineItem', null);
							$lineItem->createChild('Name', 'ItemGiftWrap');
							$lineItem->createChild('Quantity', '1');
							$lineItem->createChild('UnitAmount', sprintf('%.02f', $item->getGwPrice()), array('currencyCode' => $quote->getQuoteCurrencyCode()));
						}
					}
				}
			}
		}
		return $domDocument;
	}
	/**
	 * Parse PayPal SetExpress reply xml.
	 *
	 * @param string $payPalSetExpressCheckoutReply the xml response from eb2c
	 * @return Varien_Object an object of response data
	 */
	public function parseResponse($payPalSetExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalSetExpressCheckoutReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalSetExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$this->_blockIfRequestFailed($nodeResponseCode->item(0)->nodeValue, $checkoutXpath);

			$nodeToken = $checkoutXpath->query('//a:Token');
			$checkoutObject->setData(array(
				'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
				'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'token' => ($nodeToken->length)? (string) $nodeToken->item(0)->nodeValue : null,
			));
		}
		return $checkoutObject;
	}
}
