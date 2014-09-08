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

class EbayEnterprise_Eb2cPayment_Model_Storedvalue_Redeem_Void
{
	const REQUEST_ID_PREFIX = 'SVV-';
	/** @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;
	/** @var string $_requestId Request id of the last message sent. */
	protected $_requestId;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data $_coreHelper */
	protected $_coreHelper;
	/** @var EbayEnterprise_Eb2cPayment_Helper_Data $_paymentHelper */
	protected $_paymentHelper;
	/**
	 * @param mixed[] $params
	 */
	public function __construct(array $params=array())
	{
		$this->_log = isset($params['log']) ? $params['log'] : Mage::helper('ebayenterprise_magelog');
		$this->_coreHelper = isset($params['core_helper']) ? $params['core_helper'] : Mage::helper('eb2ccore');
		$this->_paymentHelper = isset($params['payment_helper']) ? $params['payment_helper'] : Mage::helper('eb2cpayment');
	}
	/**
	 * Get the request id of the last redeem void message sent.
	 * @return string
	 */
	public function getRequestId()
	{
		return $this->_requestId;
	}
	/**
	 * Void the SVC redemption and return the parsed response data.
	 * @param string $pan either a raw PAN or a token representing a PAN
	 * @param string $pin personal identification number or code associated with a gift card or gift certificate
	 * @param string $quoteId
	 * @param string $amount amount to redeem void
	 * @return string eb2c response to the request
	 */
	public function voidCardRedemption($pan, $pin, $quoteId, $amount)
	{
		$this->_requestId = $this->_coreHelper->generateRequestId(self::REQUEST_ID_PREFIX);
		return $this->_parseResponse($this->_makeVoidRequest(
			$pan, $this->_paymentHelper->buildRedeemRequest($pan, $pin, $quoteId, $amount, $this->_requestId, true)
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
		$uri = $this->_paymentHelper->getSvcUri('get_gift_card_redeem_void', $pan);
		if ($uri === '') {
			$this->_log->logWarn('[%s] pan "%s" is out of range of any configured tender type bin.', array(__CLASS__, $pan));
			return '';
		}
		return Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH)
			->request(
				$requestMessage,
				$this->_paymentHelper->getConfigModel()->xsdFileStoredValueVoidRedeem,
				$uri
			);
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
			$doc = $this->_coreHelper->getNewDomDocument();
			$doc->loadXML($storeValueRedeemVoidReply);
			$redeemVoidXpath = $this->_coreHelper->getNewDomXPath($doc);
			$redeemVoidXpath->registerNamespace('a', $this->_paymentHelper->getXmlNs());
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
