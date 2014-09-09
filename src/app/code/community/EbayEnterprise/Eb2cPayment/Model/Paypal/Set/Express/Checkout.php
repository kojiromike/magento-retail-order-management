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
			$doc = $this->_coreHelper->getNewDomDocument();
			$doc->loadXML($payPalSetExpressCheckoutReply);
			$checkoutXpath = $this->_coreHelper->getNewDomXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_xmlNs);
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

	/**
	 * @param Mage_Sales_Model_Quote $quote
	 * @param float|int|string $grandTotal
	 * @param array $curCodeAttr
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _getRequest(Mage_Sales_Model_Quote $quote, $grandTotal, array $curCodeAttr)
	{
		$doc = $this->_coreHelper->getNewDomDocument();
		$request = $doc->addElement('PayPalSetExpressCheckoutRequest', null, $this->_xmlNs)->firstChild;
		$request
			->addChild('OrderId', (string) $quote->getEntityId())
			->addChild('ReturnUrl', (string) Mage::getUrl('*/*/return'))
			->addChild('CancelUrl', (string) Mage::getUrl('*/*/cancel'))
			->addChild('LocaleCode', (string) Mage::app()->getLocale()->getDefaultLocale())
			->addChild('Amount', sprintf('%.02f', $grandTotal), $curCodeAttr);
		return $doc;
	}
}
