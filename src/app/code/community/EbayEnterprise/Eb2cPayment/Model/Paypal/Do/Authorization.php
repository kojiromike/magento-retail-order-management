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

class EbayEnterprise_Eb2cPayment_Model_Paypal_Do_Authorization
	extends EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract
{
	const REQUEST_ID_PREFIX = 'PDA-';
	/** @var string $_requestId Request id of the last message sent */
	protected $_requestId;
	/**
	 * Get the request id used for the last Do Authorization request message sent.
	 * @return string
	 */
	public function getRequestId()
	{
		return $this->_requestId;
	}
	/**
	 * Do paypal Authorization from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to do Authorization paypal checkout for in eb2c
	 * @return string the eb2c response to the request
	 */
	public function doAuthorization(Mage_Sales_Model_Quote $quote)
	{
		$helper = $this->_helper;
		return Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH)
			->request(
				$this->buildPayPalDoAuthorizationRequest($quote),
				$helper->getConfigModel()->xsdFilePaypalDoAuth,
				$helper->getOperationUri('get_paypal_do_authorization')
			);
	}

	/**
	 * Build  PaypalDoAuthorization request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildPayPalDoAuthorizationRequest($quote)
	{
		$totals = $quote->getTotals();
		$this->_requestId = $this->_coreHelper->generateRequestId(self::REQUEST_ID_PREFIX);
		$domDocument = $this->_coreHelper->getNewDomDocument();
		$payPalDoAuthorizationRequest = $domDocument->addElement('PayPalDoAuthorizationRequest', null, $this->_xmlNs)->firstChild;
		$payPalDoAuthorizationRequest->setAttribute('requestId', $this->_requestId);
		$payPalDoAuthorizationRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);

		$payPalDoAuthorizationRequest->createChild(
			'Amount',
			sprintf('%.02f', (isset($totals['grand_total']) ? $totals['grand_total']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);

		return $domDocument;
	}

	/**
	 * Parse PayPal DoAuthorization reply xml.
	 *
	 * @param string $payPalDoAuthorizationReply the xml response from eb2c
	 *
	 * @return Varien_Object, an object of response data
	 */
	public function parseResponse($payPalDoAuthorizationReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalDoAuthorizationReply) !== '') {
			$doc = $this->_coreHelper->getNewDomDocument();
			$doc->loadXML($payPalDoAuthorizationReply);
			$checkoutXpath = $this->_coreHelper->getNewDomXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_xmlNs);
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$this->_blockIfRequestFailed($nodeResponseCode->item(0)->nodeValue, $checkoutXpath);

			$nodePaymentStatus = $checkoutXpath->query('//a:AuthorizationInfo/a:PaymentStatus');
			$nodePendingReason = $checkoutXpath->query('//a:AuthorizationInfo/a:PendingReason');
			$nodeReasonCode = $checkoutXpath->query('//a:AuthorizationInfo/a:ReasonCode');
			$checkoutObject = new Varien_Object(
				array(
					'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue: 0,
					'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
					'payment_status' => ($nodePaymentStatus->length)? (string) $nodePaymentStatus->item(0)->nodeValue : null,
					'pending_reason' => ($nodePendingReason->length)? (string) $nodePendingReason->item(0)->nodeValue : null,
					'reason_code' => ($nodeReasonCode->length)? (string) $nodeReasonCode->item(0)->nodeValue : null,
				)
			);
		}

		return $checkoutObject;
	}
}
