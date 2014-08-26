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
	 * Parse PayPal DoExpress reply xml.
	 *
	 * @param string $payPalDoExpressCheckoutReply the xml response from eb2c
	 * @return Varien_Object an object of response data
	 */
	public function parseResponse($payPalDoExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalDoExpressCheckoutReply) !== '') {
			$doc = $this->_coreHelper->getNewDomDocument();
			$doc->loadXML($payPalDoExpressCheckoutReply);
			$checkoutXpath = $this->_coreHelper->getNewDomXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_xmlNs);
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

	/**
	 * @param Mage_Sales_Model_Quote $quote
	 * @param string|float|int $grandTotal
	 * @param array $curCodeAttr
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _getRequest(Mage_Sales_Model_Quote $quote, $grandTotal, array $curCodeAttr)
	{
		$doc = $this->_coreHelper->getNewDomDocument();
		$paypal = Mage::getModel('eb2cpayment/paypal');
		$paypal->loadByQuoteId($quote->getEntityId());
		$quoteShippingAddress = $quote->getShippingAddress();
		$request = $doc->addElement('PayPalDoExpressCheckoutRequest', null, $this->_xmlNs)->firstChild;
		$request
			->addAttribute('requestId', $this->_helper->getRequestId($quote->getEntityId()))
			->addChild('OrderId', (string)$quote->getEntityId())
			->addChild('Token', (string)$paypal->getEb2cPaypalToken())
			->addChild('PayerId', (string)$paypal->getEb2cPaypalPayerId())
			->addChild('Amount', sprintf('%.02f', $grandTotal), $curCodeAttr)
			->addChild('ShipToName', (string)$quoteShippingAddress->getName());
		return $doc;
	}
}
