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
		/** @var EbayEnterprise_Eb2cCore_Helper_Data $coreHelper */
		$coreHelper = Mage::helper('eb2ccore');
		$domDocument = $coreHelper->getNewDomDocument();
		$totals = $quote->getTotals();
		$curCodeAttr = array('currencyCode' => $quote->getQuoteCurrencyCode());
		/** @var EbayEnterprise_Dom_Element $payPalSetExpressCheckoutRequest */
		$payPalSetExpressCheckoutRequest = $domDocument->addElement('PayPalSetExpressCheckoutRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalSetExpressCheckoutRequest->createChild('OrderId', (string) $quote->getEntityId());
		$payPalSetExpressCheckoutRequest->createChild('ReturnUrl', (string) Mage::getUrl('*/*/return'));
		$payPalSetExpressCheckoutRequest->createChild('CancelUrl', (string) Mage::getUrl('*/*/cancel'));
		$payPalSetExpressCheckoutRequest->createChild('LocaleCode', (string) Mage::app()->getLocale()->getDefaultLocale());
		$payPalSetExpressCheckoutRequest->createChild('Amount', sprintf('%.02f', (isset($totals['grand_total']) ? $totals['grand_total']->getValue() : 0)), $curCodeAttr);
		$lineItems = $payPalSetExpressCheckoutRequest->createChild('LineItems', null);
		$gwPrice = $quote->getGwPrice();
		// LineItemsTotal _must_ come first and I need to add in Gift Wrap. So I end up looping
		// twice. Surely there's a more efficient way to do this.
		$lineItemsTotal = isset($totals['subtotal']) ? $totals['subtotal']->getValue() : 0;
		$lineItemsTotal += $gwPrice;
		/** @var array $addresses */
		$addresses = $quote->getAllAddresses();
		foreach ($addresses as $address) {
			foreach ($address->getAllItems() as $item) {
				// If gw_price is empty, php will treat it as zero.
				$lineItemsTotal += $item->getGwPrice();
			}
		}

		$shippingTotals = isset($totals['shipping']) ? $totals['shipping']->getValue() : 0;
		$taxTotals = isset($totals['tax']) ? $totals['tax']->getValue() : 0;

		$lineItems
			->addChild('LineItemsTotal', sprintf('%.02f', $lineItemsTotal), $curCodeAttr)
			->addChild('ShippingTotal', sprintf('%.02f', $shippingTotals), $curCodeAttr)
			->addChild('TaxTotal', sprintf('%.02f', $taxTotals), $curCodeAttr);

		if ($gwPrice) {
			$lineItem = $lineItems->createChild('LineItem', null);
			$lineItem
				->addChild('Name', 'GiftWrap')
				->addChild('Quantity', '1')
				->addChild('UnitAmount', sprintf('%.02f', $gwPrice), $curCodeAttr);
		}
		foreach($addresses as $address) {
			foreach ($address->getAllItems() as $item) {
				$lineItem = $lineItems->createChild('LineItem', null);
				$lineItem
					->addChild('Name', (string) $item->getName())
					->addChild('Quantity', (string) $item->getQty())
					->addChild('UnitAmount', sprintf('%.02f', $item->getPrice()), $curCodeAttr);
				if ($item->getGwPrice()) {
					$lineItem = $lineItems->createChild('LineItem', null);
					$lineItem
						->addChild('Name', 'ItemGiftWrap')
						->addChild('Quantity', '1')
						->addChild('UnitAmount', sprintf('%.02f', $item->getGwPrice()), $curCodeAttr);
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
			/** @var EbayEnterprise_Dom_Document $doc */
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
