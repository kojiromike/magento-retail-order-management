<?php
class EbayEnterprise_Eb2cPayment_Model_Paypal_Get_Express_Checkout extends EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract
{
	// A mapping to something in the helper. Pretty contrived.
	const URI_KEY = 'get_paypal_get_express_checkout';
	const XSD_FILE = 'xsd_file_paypal_get_express';
	const STORED_FIELD = 'payer_id';
	/**
	 * Build PaypalGetExpressCheckout request.
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The XML document to be sent as request to eb2c.
	 */
	protected function _buildRequest(Mage_Sales_Model_Quote $quote)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalGetExpressCheckoutRequest = $domDocument->addElement('PayPalGetExpressCheckoutRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalGetExpressCheckoutRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);
		$paypal = Mage::getModel('eb2cpayment/paypal')->loadByQuoteId($quote->getEntityId());
		$payPalGetExpressCheckoutRequest->createChild(
			'Token',
			(string) $paypal->getEb2cPaypalToken()
		);
		$payPalGetExpressCheckoutRequest->createChild(
			'CurrencyCode',
			(string) $quote->getQuoteCurrencyCode()
		);
		return $domDocument;
	}
	/**
	 * Parse PayPal GetExpress reply xml.
	 *
	 * @param string $payPalGetExpressCheckoutReply the xml response from eb2c
	 * @return Varien_Object an object of response data
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function parseResponse($payPalGetExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalGetExpressCheckoutReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalGetExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$this->_blockIfRequestFailed($nodeResponseCode->item(0)->nodeValue, $checkoutXpath);

			$nodePayerEmail = $checkoutXpath->query('//a:PayerEmail');
			$nodePayerId = $checkoutXpath->query('//a:PayerId');
			$nodePayerStatus = $checkoutXpath->query('//a:PayerStatus');
			$nodeHonorific = $checkoutXpath->query('//a:PayerName/a:Honorific');
			$nodeLastName = $checkoutXpath->query('//a:PayerName/a:LastName');
			$nodeMiddleName = $checkoutXpath->query('//a:PayerName/a:MiddleName');
			$nodeFirstName = $checkoutXpath->query('//a:PayerName/a:FirstName');
			$nodePayerCountry = $checkoutXpath->query('//a:PayerCountry');
			$nodeLineA = $checkoutXpath->query('//a:BillingAddress/a:Line1');
			$nodeLineB = $checkoutXpath->query('//a:BillingAddress/a:Line2');
			$nodeLineC = $checkoutXpath->query('//a:BillingAddress/a:Line3');
			$nodeLineD = $checkoutXpath->query('//a:BillingAddress/a:Line4');
			$nodeCity = $checkoutXpath->query('//a:BillingAddress/a:City');
			$nodeMainDivision = $checkoutXpath->query('//a:BillingAddress/a:MainDivision');
			$nodeCountryCode = $checkoutXpath->query('//a:BillingAddress/a:CountryCode');
			$nodePostalCode = $checkoutXpath->query('//a:BillingAddress/a:PostalCode');
			$nodeAddressStatus = $checkoutXpath->query('//a:BillingAddress/a:AddressStatus');
			$nodePayerPhone = $checkoutXpath->query('//a:PayerPhone');
			$nodeShpLineA = $checkoutXpath->query('//a:ShippingAddress/a:Line1');
			$nodeShpLineB = $checkoutXpath->query('//a:ShippingAddress/a:Line2');
			$nodeShpLineC = $checkoutXpath->query('//a:ShippingAddress/a:Line3');
			$nodeShpLineD = $checkoutXpath->query('//a:ShippingAddress/a:Line4');
			$nodeShpCity = $checkoutXpath->query('//a:ShippingAddress/a:City');
			$nodeShpMainDivision = $checkoutXpath->query('//a:ShippingAddress/a:MainDivision');
			$nodeShpCountryCode = $checkoutXpath->query('//a:ShippingAddress/a:CountryCode');
			$nodeShpPostalCode = $checkoutXpath->query('//a:ShippingAddress/a:PostalCode');
			$nodeShpAddressStatus = $checkoutXpath->query('//a:ShippingAddress/a:AddressStatus');
			$checkoutObject->setData(array(
				'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
				'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'payer_email' => ($nodePayerEmail->length)? (string) $nodePayerEmail->item(0)->nodeValue : null,
				'payer_id' => ($nodePayerId->length)? (string) $nodePayerId->item(0)->nodeValue : null,
				'payer_status' => ($nodePayerStatus->length)? (string) $nodePayerStatus->item(0)->nodeValue : null,
				'payer_name_honorific' => ($nodeHonorific->length)? (string) $nodeHonorific->item(0)->nodeValue : null,
				'payer_name_last_name' => ($nodeLastName->length)? (string) $nodeLastName->item(0)->nodeValue : null,
				'payer_name_middle_name' => ($nodeMiddleName->length)? (string) $nodeMiddleName->item(0)->nodeValue : null,
				'payer_name_first_name' => ($nodeFirstName->length)? (string) $nodeFirstName->item(0)->nodeValue : null,
				'payer_country' => ($nodePayerCountry->length)? (string) $nodePayerCountry->item(0)->nodeValue : null,
				'billing_address_line1' => ($nodeLineA->length)? (string) $nodeLineA->item(0)->nodeValue : null,
				'billing_address_line2' => ($nodeLineB->length)? (string) $nodeLineB->item(0)->nodeValue : null,
				'billing_address_line3' => ($nodeLineC->length)? (string) $nodeLineC->item(0)->nodeValue : null,
				'billing_address_line4' => ($nodeLineD->length)? (string) $nodeLineD->item(0)->nodeValue : null,
				'billing_address_city' => ($nodeCity->length)? (string) $nodeCity->item(0)->nodeValue : null,
				'billing_address_main_division' => ($nodeMainDivision->length)? (string) $nodeMainDivision->item(0)->nodeValue : null,
				'billing_address_country_code' => ($nodeCountryCode->length)? (string) $nodeCountryCode->item(0)->nodeValue : null,
				'billing_address_postal_code' => ($nodePostalCode->length)? (string) $nodePostalCode->item(0)->nodeValue : null,
				'billing_address_status' => ($nodeAddressStatus->length)? (string) $nodeAddressStatus->item(0)->nodeValue : null,
				'payer_phone' => ($nodePayerPhone->length)? (string) $nodePayerPhone->item(0)->nodeValue : null,
				'shipping_address_line1' => ($nodeShpLineA->length)? (string) $nodeShpLineA->item(0)->nodeValue : null,
				'shipping_address_line2' => ($nodeShpLineB->length)? (string) $nodeShpLineB->item(0)->nodeValue : null,
				'shipping_address_line3' => ($nodeShpLineC->length)? (string) $nodeShpLineC->item(0)->nodeValue : null,
				'shipping_address_Line4' => ($nodeShpLineD->length)? (string) $nodeShpLineD->item(0)->nodeValue : null,
				'shipping_address_city' => ($nodeShpCity->length)? (string) $nodeShpCity->item(0)->nodeValue : null,
				'shipping_address_main_division' => ($nodeShpMainDivision->length)? (string) $nodeShpMainDivision->item(0)->nodeValue : null,
				'shipping_address_country_code' => ($nodeShpCountryCode->length)? (string) $nodeShpCountryCode->item(0)->nodeValue : null,
				'shipping_address_postal_code' => ($nodeShpPostalCode->length)? (string) $nodeShpPostalCode->item(0)->nodeValue : null,
				'shipping_address_status' => ($nodeShpAddressStatus->length)? (string) $nodeShpAddressStatus->item(0)->nodeValue : null,
			));
		}
		return $checkoutObject;
	}
}
