<?php
class EbayEnterprise_Eb2cPayment_Model_Storedvalue_Redeem_Void
{
	/**
	 * Void the SVC redemption and return the parsed response data.
	 *
	 * @param string $pan either a raw PAN or a token representing a PAN
	 * @param string $pin personal identification number or code associated with a gift card or gift certificate
	 * @param $quoteId sales/quote entity_id value
	 * @param string $amount amount to redeem void
	 * @return string eb2c response to the request
	 */
	public function voidCardRedemption($pan, $pin, $quoteId, $amount)
	{
		return $this->_parseResponse($this->_makeVoidRequest(
			$pan, $this->_buildRequest($pan, $pin, $quoteId, $amount)
		));
	}
	/**
	 * Get gift card redeem void from eb2c.
	 *
	 * @param string $pan either a raw PAN or a token representing a PAN
	 * @param DOMDocument $requestMessage message to send
	 * @return string eb2c response to the request
	 */
	protected function _makeVoidRequest($pan, DOMDocument $requestMessage)
	{
		$hlpr = Mage::helper('eb2cpayment');
		$uri = $hlpr->getSvcUri('get_gift_card_redeem_void', $pan);
		if ($uri === '') {
			Mage::log(sprintf('[ %s ] pan "%s" is out of range of any configured tender type bin.', __CLASS__, $pan), Zend_Log::ERR);
			return '';
		}
		return Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH)
			->request(
				$requestMessage,
				$hlpr->getConfigModel()->xsdFileStoredValueVoidRedeem,
				$uri
			);
	}
	/**
	 * Build gift card Redeem Void request.
	 * @param string $pan, the payment account number
	 * @param string $pin, the personal identification number
	 * @param string $entityId, the sales/quote entity_id value
	 * @param string $amount, the amount to Redeem Void
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	protected function _buildRequest($pan, $pin, $entityId, $amount)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$storedValueRedeemVoidRequest = $domDocument->addElement('StoredValueRedeemVoidRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$storedValueRedeemVoidRequest->setAttribute('requestId', Mage::helper('eb2cpayment')->getRequestId($entityId));

		// creating PaymentContent element
		$paymentContext = $storedValueRedeemVoidRequest->createChild('PaymentContext', null);

		// creating OrderId element
		$paymentContext->createChild('OrderId', $entityId);

		// creating PaymentAccountUniqueId element
		$paymentContext->createChild('PaymentAccountUniqueId', $pan, array('isToken' => 'false'));

		// add Pin
		$storedValueRedeemVoidRequest->createChild('Pin', (string) $pin);

		// add amount
		$storedValueRedeemVoidRequest->createChild('Amount', $amount, array('currencyCode' => 'USD'));

		return $domDocument;
	}

	/**
	 * Parse gift card Redeem Void response xml.
	 * @param string $storeValueRedeemVoidReply the xml response from eb2c
	 * @return array, an associative array of response data
	 */
	protected function _parseResponse($storeValueRedeemVoidReply)
	{
		$redeemVoidData = array();
		if ($storeValueRedeemVoidReply) {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($storeValueRedeemVoidReply);
			$redeemVoidXpath = new DOMXPath($doc);
			$redeemVoidXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $redeemVoidXpath->query('//a:PaymentContext/a:OrderId');
			$nodePaymentAccountUniqueId = $redeemVoidXpath->query('//a:PaymentContext/a:PaymentAccountUniqueId');
			$nodeResponseCode = $redeemVoidXpath->query('//a:ResponseCode');
			$redeemVoidData = array(
				'orderId' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
				'paymentAccountUniqueId' => ($nodePaymentAccountUniqueId->length)? (string) $nodePaymentAccountUniqueId->item(0)->nodeValue : null,
				'responseCode' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue: null,
			);
		}
		return $redeemVoidData;
	}
}
