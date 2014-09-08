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

class EbayEnterprise_Eb2cPayment_Model_Storedvalue_Redeem
{
	// array key for card PIN
	const CARD_PIN_KEY = 'pin';
	// array key for card PAN
	const CARD_PAN_KEY = 'pan';
	// array key for card base amount
	const CARD_AMOUNT_KEY = 'ba';
	// operation "key"
	const PAYMENT_SERVICE_OPERATION = 'get_gift_card_redeem';
	const REQUEST_ID_PREFIX = 'SVR-';
	/**
	 * Order the gift cards are attached to
	 * @var Mage_Sales_Model_Order
	 */
	protected $_order;
	/**
	 * Map of SVC card data
	 * @see EbayEnterprise_Eb2cPayment_Overrides_Model_Giftcardaccount::addToCard for included key/value pairs
	 * @var array
	 */
	protected $_card;
	/** @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data $_coreHelper */
	protected $_coreHelper;
	/** @var EbayEnterprise_Eb2cPayment_Helper_Data $_paymentHelper */
	protected $_paymentHelper;

	/** @var string $_responseMessage Message received from the service call */
	protected $_responseMessage;
	/** @var string $_responseOrderId Order id extracted from the redeem response message. */
	protected $_responseOrderId;
	/** @var string $_paymentAccountUniqueId Payment Account Unique Id extracted from the redeem response message. */
	protected $_paymentAccountUniqueId;
	/** @var string $_responseCode Response code extracted from the redeem response message. */
	protected $_responseCode;
	/** @var string $_amountRedeemed Amount redeemed extracted from the redeem response message. */
	protected $_amountRedeemed;
	/** @var string $_balanceAmount Balance amount extracted from the redeem response message. */
	protected $_balanceAmount;
	/** @var string $_requestId Request id of the SVC redeem request. */
	protected $_requestId;
	/**
	 * Inject an order and key/value map of gift card data the redeem
	 * request will be for.
	 *
	 * $params expected to have following key/value pairs
	 * Mage_Sales_Model_Order $params['order'] Order card is attached to
	 * array $params['card'] key/value pair of SVC data
	 * @param mixed[] $params Required to have a following key/value pairs
	 *                        'order' Mage_Sales_Model_Order order object the card is attached to
	 *                        'card' array key/value pairs of gift card data
	 */
	public function __construct(array $params=array())
	{
		// required params - order and card
		$this->_validateOrderParam($params);
		$this->_order = $params['order'];
		$this->_validateCardParam($params);
		$this->_card = $params['card'];

		// optionally injected dependencies - use params value or new/singleton instances
		$this->_log = isset($params['log']) ? $params['log'] : Mage::helper('ebayenterprise_magelog');
		$this->_coreHelper = isset($params['core_helper']) ? $params['core_helper'] : Mage::helper('eb2ccore');
		$this->_paymentHelper = isset($params['payment_helper']) ? $params['payment_helper'] : Mage::helper('eb2cpayment');
	}
	/**
	 * Validate the constructor params to include an 'order' key with a
	 * Mage_Sales_Model_Order value
	 * @param mixed[]  $params Constructor $params argument
	 * @return bool
	 * @throws Mage_Core_Exception If given array does not include an 'order' key with a Mage_Sales_Model_Order value
	 */
	protected function _validateOrderParam(array $params=array())
	{

		if (!(isset($params['order']) && $params['order'] instanceof Mage_Sales_Model_Order)) {
			throw Mage::exception('Mage_Core', 'Mage_Sales_Model_Order instance must be provided.');
		}
		return $this;
	}
	/**
	 * Validate the constructor params to include a 'card' key with an
	 * array value containing 'pin', 'pan', and 'ba' key/value pairs
	 * @param mixed[]  $params Constructor $params argument
	 * @return bool
	 * @throws Mage_Core_Exception If given array does not include a 'card' array with an array with 'pin', 'pan', and 'ba' key value
	 */
	protected function _validateCardParam(array $params=array())
	{
		if (!(isset($params['card']) && is_array($params['card']))) {
			throw Mage::Exception('Mage_Core', "A 'card' must be included in the params.");
		}
		$card = $params['card'];
		// card must be array with pin, pan and ba keys
		$requiredCardFields = array(self::CARD_PIN_KEY, self::CARD_PAN_KEY, self::CARD_AMOUNT_KEY);
		$missingFields = array_diff($requiredCardFields, array_keys($card));
		if ($missingFields) {
			throw Mage::exception('Mage_Core', "'card' is missing fields: " . implode(', ', $missingFields));
		}
		return $this;
	}
	/**
	 * Get the response order id extracted from the response message.
	 * @return string|null Null if request has not been made or response did not include an order id
	 */
	public function getResponseOrderId()
	{
		return $this->_responseOrderId;
	}
	/**
	 * Get the payment account unique id extracted from the response message.
	 * @return string|null Null if request has not been made or response did not include a payment account unique id
	 */
	public function getPaymentAccountUniqueId()
	{
		return $this->_paymentAccountUniqueId;
	}
	/**
	 * Get the response code extracted from the redeem response message.
	 * @return string|null Null if request has not been made or response did not include a response code
	 */
	public function getResponseCode()
	{
		return $this->_responseCode;
	}
	/**
	 * Get the amount redeemed indicated in the redeem response message.
	 * @return string|null Null if request has not been made or response did not include a redeemed amount.
	 */
	public function getAmountRedeemed()
	{
		return $this->_amountRedeemed;
	}
	/**
	 * Get the balance amount indicated in the redeem response message.
	 * @return string|null Null if request has not been made or response did not include a balance amount.
	 */
	public function getBalanceAmount()
	{
		return $this->_balanceAmount;
	}
	/**
	 * Get the request id of the last request message sent.
	 * @return string
	 */
	public function getRequestId()
	{
		return $this->_requestId;
	}
	/**
	 * Redeem the gift card - make the SVC redeem request and parse out
	 * data from the response.
	 * @return self
	 */
	public function redeemGiftCard()
	{
		return $this->_makeRedeemRequest()->_extractResponse();
	}
	/**
	 * Get gift card redeem from eb2c.
	 * @return self
	 */
	public function _makeRedeemRequest()
	{
		$pan = $this->_card[self::CARD_PAN_KEY];
		$this->_requestId = $this->_coreHelper->generateRequestId(self::REQUEST_ID_PREFIX);
		$uri = $this->_paymentHelper->getSvcUri(self::PAYMENT_SERVICE_OPERATION, $pan);
		if ($uri === '') {
			$this->_log->logWarn('[%s] pan "%s" is not in any configured tender type bin.', array(__CLASS__, $pan));
			$this->_responseMessage = '';
			return $this;
		}
		$this->_responseMessage = trim(Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH)
			->request(
				$this->_paymentHelper->buildRedeemRequest(
					$pan, $this->_card[self::CARD_PIN_KEY], $this->_order->getIncrementId(), $this->_card[self::CARD_AMOUNT_KEY], $this->_requestId, false
				),
				$this->_paymentHelper->getConfigModel()->xsdFileStoredValueRedeem,
				$uri
			));
		return $this;
	}
	/**
	 * Parse gift card Redeem response xml.
	 * @return self
	 */
	public function _extractResponse()
	{
		if ($this->_responseMessage) {
			$responseDoc = $this->_coreHelper->getNewDomDocument();
			$responseDoc->loadXML($this->_responseMessage);
			$redeemXpath = $this->_coreHelper->getNewDomXPath($responseDoc);
			$redeemXpath->registerNamespace('a', $this->_paymentHelper->getXmlNs());
			$this->_responseOrderId = $redeemXpath->evaluate('string(//a:PaymentContext/a:OrderId)');
			$this->_paymentAccountUniqueId = $redeemXpath->evaluate('string(//a:PaymentContext/a:PaymentAccountUniqueId)');
			$this->_responseCode = strtoupper($redeemXpath->evaluate('string(//a:ResponseCode)'));
			$this->_amountRedeemed = $redeemXpath->evaluate('string(//a:AmountRedeemed)');
			$this->_balanceAmount = $redeemXpath->evaluate('string(//a:BalanceAmount)');
		}
		return $this;
	}
}
