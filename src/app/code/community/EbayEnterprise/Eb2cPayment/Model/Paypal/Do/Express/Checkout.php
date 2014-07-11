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

class EbayEnterprise_Eb2cPayment_Model_Paypal_Do_Express_Checkout extends EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract
{
	// A mapping to something in the helper. Pretty contrived.
	const URI_KEY = 'get_paypal_do_express_checkout';
	const XSD_FILE = 'xsd_file_paypal_do_express';
	const STORED_FIELD = 'transaction_id';
	const ERROR_MESSAGE_ELEMENT = '//a:ErrorMessage';

	/**
	 * Build PaypalDoExpressCheckout request.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The XML document to be sent as request to eb2c.
	 */
	protected function _buildRequest(Mage_Sales_Model_Quote $quote)
	{
		/**
		 * @var EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
		 * @var EbayEnterprise_Eb2cPayment_Helper_Data $helper
		 * @var EbayEnterprise_Eb2cPayment_Model_Paypal $paypal
		 * @var Mage_Sales_Model_Quote_Address $quoteShippingAddress
		 * @var EbayEnterprise_Dom_Element $request
		 */
		$coreHelper = Mage::helper('eb2ccore');
		$helper = Mage::helper('eb2cpayment');
		$paypal = Mage::getModel('eb2cpayment/paypal');
		$paypal->loadByQuoteId($quote->getEntityId());
		$doc = $coreHelper->getNewDomDocument();
		$gwPrice = $quote->getGwPrice();
		$gwId = $quote->getGwId();
		$totals = $quote->getTotals();
		$grandTotal = isset($totals['grand_total']) ? $totals['grand_total']->getValue() : 0;
		$shippingTotal = isset($totals['shipping']) ? $totals['shipping']->getValue() : 0;
		$taxTotal = isset($totals['tax']) ? $totals['tax']->getValue() : 0;
		$lineItemsTotal = (isset($totals['subtotal']) ? $totals['subtotal']->getValue() : 0) + $gwPrice;
		$curCodeAttr = array('currencyCode' => $quote->getQuoteCurrencyCode());
		$quoteShippingAddress = $quote->getShippingAddress();
		$request = $doc->createElement('PayPalDoExpressCheckoutRequest', null, $helper->getXmlNs());
		$request
			->addAttribute('requestId', $helper->getRequestId($quote->getEntityId()))
			->addChild('OrderId', (string) $quote->getEntityId())
			->addChild('Token', (string) $paypal->getEb2cPaypalToken())
			->addChild('PayerId', (string) $paypal->getEb2cPaypalPayerId())
			->addChild('Amount', sprintf('%.02f', $grandTotal), $curCodeAttr)
			->addChild('ShipToName', (string) $quoteShippingAddress->getName());

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
	/**
	 * Parse PayPal DoExpress reply xml.
	 *
	 * @param string $payPalDoExpressCheckoutReply the xml response from eb2c
	 * @return Varien_Object an object of response data
	 */
	public function parseResponse($payPalDoExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalDoExpressCheckoutReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalDoExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$nodeTransactionID = $checkoutXpath->query('//a:TransactionID');
			$this->_blockIfRequestFailed($nodeResponseCode->item(0)->nodeValue, $checkoutXpath);

			$nodePaymentStatus = $checkoutXpath->query('//a:PaymentInfo/a:PaymentStatus');
			$nodePendingReason = $checkoutXpath->query('//a:PaymentInfo/a:PendingReason');
			$nodeReasonCode = $checkoutXpath->query('//a:PaymentInfo/a:ReasonCode');
			$checkoutObject->setData(array(
				'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
				'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'transaction_id' => ($nodeTransactionID->length)? (string) $nodeTransactionID->item(0)->nodeValue : null,
				'payment_status' => ($nodePaymentStatus->length)? (string) $nodePaymentStatus->item(0)->nodeValue : null,
				'pending_reason' => ($nodePendingReason->length)? (string) $nodePendingReason->item(0)->nodeValue : null,
				'reason_code' => ($nodeReasonCode->length)? (string) $nodeReasonCode->item(0)->nodeValue : null,
			));
		}
		return $checkoutObject;
	}
}
