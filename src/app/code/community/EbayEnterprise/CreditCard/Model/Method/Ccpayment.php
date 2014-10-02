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

use eBayEnterprise\RetailOrderManagement\Api;
use eBayEnterprise\RetailOrderManagement\Payload;

class EbayEnterprise_CreditCard_Model_Method_Ccpayment extends Mage_Payment_Model_Method_Cc
{
	const CREDITCARD_AUTH_FAILED_MESSAGE = 'EBAYENTERPRISE_CREDITCARD_AUTH_FAILED';
	const CREDITCARD_AVS_FAILED_MESSAGE = 'EBAYENTERPRISE_CREDITCARD_AVS_FAILED';
	const CREDITCARD_CVV_FAILED_MESSAGE = 'EBAYENTERPRISE_CREDITCARD_CVV_FAILED';

	protected $_code          = 'ebayenterprise_creditcard';
	/**
	 * Is this payment method a gateway (online auth/charge) ?
	 */
	protected $_isGateway               = false;

	/**
	 * Can authorize online?
	 */
	protected $_canAuthorize            = true;

	/**
	 * Can capture funds online?
	 */
	protected $_canCapture              = false;

	/**
	 * Can capture partial amounts online?
	 */
	protected $_canCapturePartial       = false;

	/**
	 * Can refund online?
	 */
	protected $_canRefund               = false;

	/**
	 * Can void transactions online?
	 */
	protected $_canVoid                 = false;

	/**
	 * Can use this payment method in administration panel?
	 */
	protected $_canUseInternal          = true;

	/**
	 * Can show this payment method as an option on checkout payment page?
	 */
	protected $_canUseCheckout          = true;

	/**
	 * Is this payment method suitable for multi-shipping checkout?
	 */
	protected $_canUseForMultishipping  = true;

	/**
	 * Can save credit card information for future processing?
	 */
	protected $_canSaveCc = true;

	/** @var EbayEnterprise_CreditCard_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var Mage_Core_Helper_Http */
	protected $_httpHelper;
	/** @var Mage_Checkout_Model_Session */
	protected $_checkoutSession;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/**
	 * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
	 * Override __construct here as the usual protected `_construct` is not called.
	 * @param array $initParams May contain:
	 *                          -  'helper' => EbayEnterprise_CreditCard_Helper_Data
	 *                          -  'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 *                          -  'http_helper' => Mage_Core_Helper_Http
	 *                          -  'checkout_session' => Mage_Checkout_Model_Session
	 *                          -  'logger' => EbayEnterprise_MageLog_Helper_Data
	 */
	public function __construct(array $initParams=array())
	{
		list($this->_helper, $this->_coreHelper, $this->_httpHelper, $this->_checkoutSession, $this->_logger) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_creditcard')),
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
			$this->_nullCoalesce($initParams, 'http_helper', Mage::helper('core/http')),
			$this->_nullCoalesce($initParams, 'checkout_session', Mage::getSingleton('checkout/session')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog'))
		);
	}
	/**
	 * Type hinting for self::__construct $initParams
	 * @param EbayEnterprise_CreditCard_Helper_Data $helper
	 * @param EbayEnterprise_Eb2cCore_Helper_Data   $coreHelper
	 * @param Mage_Core_Helper_Http                 $httpHelper
	 * @param Mage_Checkout_Model_Session           $checkoutSession
	 * @param EbayEnterprise_MageLog_Helper_Data    $logger
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_CreditCard_Helper_Data $helper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		Mage_Core_Helper_Http $httpHelper,
		Mage_Checkout_Model_Session $checkoutSession,
		EbayEnterprise_MageLog_Helper_Data $logger
	) {
		return array($helper, $coreHelper, $httpHelper, $checkoutSession, $logger);
	}
	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 * @param  array      $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed      $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}
	/**
	 * Authorize payment abstract method
	 *
	 * @param Varien_Object $payment
	 * @param float         $amount unused; only here to maintain signature
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function authorize(Varien_Object $payment, $amount)
	{
		$api = $this->_getApi($payment);
		$this->_prepareApiRequest($api, $payment);
		$this->_logger->logDebug(sprintf("[%s] Sending auth request:\n%s", __CLASS__, $this->_helper->cleanAuthXml($api->getRequestBody()->serialize())));
		$this->_sendAuthRequest($api);
		$this->_logger->logDebug(sprintf("[%s] Received auth response:\n%s", __CLASS__, $this->_helper->cleanAuthXml($api->getResponseBody()->serialize())));
		$this->_handleApiResponse($api, $payment);
		return $this;
	}
	/**
	 * Fill out the request payload with payment data and update the API request
	 * body with the complete request.
	 * @param Api\IBidirectionalApi $api
	 * @param Varien_Object         $payment Most likely a Mage_Sales_Model_Order_Payment
	 * @return self
	 */
	protected function _prepareApiRequest(Api\IBidirectionalApi $api, Varien_Object $payment)
	{
		$request = $api->getRequestBody();
		$order = $payment->getOrder();
		$billingAddress = $order->getBillingAddress();
		$shippingAddress = $order->getShippingAddress() ?: $billingAddress;
		$request
			->setRequestId($this->_coreHelper->generateRequestId('CCA-'))
			->setOrderId($order->getIncrementId())
			->setPanIsToken(false)
			->setCardNumber($payment->getCcNumber())
			->setExpirationDate($this->_coreHelper->getNewDateTime(sprintf('%s-%s', $payment->getCcExpYear(), $payment->getCcExpMonth())))
			->setCardSecurityCode($payment->getCcCid())
			->setAmount($payment->getBaseAmountAuthorized())
			->setCurrencyCode(Mage::app()->getStore()->getBaseCurrencyCode())
			->setEmail($billingAddress->getEmail())
			->setIp($this->_httpHelper->getRemoteAddr())
			->setBillingFirstName($billingAddress->getFirstname())
			->setBillingLastName($billingAddress->getLastname())
			->setBillingPhone($billingAddress->getTelephone())
			->setBillingLines($billingAddress->getStreet(-1)) // returns all lines, \n separated
			->setBillingCity($billingAddress->getCity())
			->setBillingMainDivision($billingAddress->getRegionCode())
			->setBillingCountryCode($billingAddress->getCountry())
			->setBillingPostalCode($billingAddress->getPostcode())
			->setShipToFirstName($shippingAddress->getFirstname())
			->setShipToLastName($shippingAddress->getLastname())
			->setShipToPhone($shippingAddress->getTelephone())
			->setShipToLines($shippingAddress->getStreet(-1)) // returns all lines, \n separated
			->setShipToCity($shippingAddress->getCity())
			->setShipToMainDivision($shippingAddress->getRegionCode())
			->setShipToCountryCode($shippingAddress->getCountry())
			->setShipToPostalCode($shippingAddress->getPostcode())
			->setIsRequestToCorrectCVVOrAVSError($this->_getIsCorrectionNeededForPayment($payment));
		return $this;
	}
	/**
	 * Check for the response to be valid.
	 * @param Payload\Payment\ICreditCardAuthReply $response
	 * @return self
	 */
	protected function _validateResponse(Payload\Payment\ICreditCardAuthReply $response)
	{
		// if auth was a complete success, accept the response and move on
		if ($response->getIsAuthSuccessful()) {
			return $this;
		}
		// if AVS correction is needed, redirect to billing address step
		if ($response->getIsAVSCorrectionRequired()) {
			$this->_failPaymentAuth(self::CREDITCARD_AVS_FAILED_MESSAGE, 'billing');
		}
		// if CVV correction is needed, redirect to payment method step
		if ($response->getIsCVV2CorrectionRequired()) {
			$this->_failPaymentAuth(self::CREDITCARD_CVV_FAILED_MESSAGE, 'payment');
		}
		// if AVS & CVV did not fail but was not a complete success, see if the
		// request is at least acceptable - timeout perhaps - and if so, take it
		// and allow order submit to continue
		if ($response->getIsAuthAcceptable()) {
			return $this;
		}
		// auth failed for some other reason, possibly declined, making it unacceptable
		// send user to payment step of checkout with an error message
		$this->_failPaymentAuth(self::CREDITCARD_AUTH_FAILED_MESSAGE, 'payment');
	}
	/**
	 * Update the order payment and quote payment with details from the CC auth
	 * request/response.
	 * @param  Varien_Data $payment
	 * @param  Payload\Payment\ICreditCardAuthRequest $request
	 * @param  Payload\Payment\ICreditCardAuthReply   $response
	 * @return self
	 */
	protected function _updatePaymentsWithAuthData(
		Varien_Object $payment,
		Payload\Payment\ICreditCardAuthRequest $request,
		Payload\Payment\ICreditCardAuthReply $response
	) {
		return $this
			->_updatePayment($payment, $request, $response)
			->_updatePayment($payment->getOrder()->getQuote()->getPayment(), $request, $response);
	}
	/**
	 * Update the payment with details from the CC Auth Request and Reply
	 * @param  Varien_Object                          $payment
	 * @param  Payload\Payment\ICreditCardAuthRequest $request
	 * @param  Payload\Payment\ICreditCardAuthReply   $response
	 * @return self
	 */
	public function _updatePayment(
		Varien_Object $payment,
		Payload\Payment\ICreditCardAuthRequest $request,
		Payload\Payment\ICreditCardAuthReply $response
	) {
		$correctionRequired = $response->getIsAVSCorrectionRequired() || $response->getIsCVV2CorrectionRequired();
		$payment->setAdditionalInformation(array(
			'request_id' => $request->getRequestId(),
			'response_code' => $response->getResponseCode(),
			'pan_is_token' => $response->getPanIsToken(),
			'bank_authorization_code' => $response->getBankAuthorizationCode(),
			'cvv2_response_code' => $response->getCVV2ResponseCode(),
			'avs_response_code' => $response->getAVSResponseCode(),
			'phone_response_code' => $response->getPhoneResponseCode(),
			'name_response_code' => $response->getNameResponseCode(),
			'email_response_code' => $response->getEmailResponseCode(),
			'currency_code' => $response->getCurrencyCode(),
			'tender_type' => $this->_helper->getTenderTypeForCcType($payment->getCcType()),
			'is_correction_required' => $correctionRequired,
			'last4_to_correct' => $correctionRequired ? $payment->getCcLast4() : null,
		))
			->setAmountAuthorized($response->getAmountAuthorized())
			->setBaseAmountAuthorized($response->getAmountAuthorized())
			->setCcNumberEnc($payment->encrypt($response->getCardNumber()));
		return $this;
	}
	/**
	 * Check if the payment needs to be corrected - payment additional information
	 * would have the is_correction_required flag set to true and the cc last 4
	 * for the current payment would match the last4_to_correct payment
	 * additional information.
	 * @param  Varien_Object $payment
	 * @return bool
	 */
	protected function _getIsCorrectionNeededForPayment(Varien_Object $payment)
	{
		return $payment->getAdditionalInformation('is_correction_required')
			&& $payment->getCcLast4() === $payment->getAdditionalInformation('last4_to_correct');
	}
	/**
	 * Set the checkout session's goto section to the provided step.
	 * One of: 'login', 'billing', 'shipping', 'shipping_method', 'payment', 'review'
	 * @param string $step Step in checkout
	 */
	public function _setCheckoutStep($step)
	{
		$this->_checkoutSession->setGotoSection($step);
		return $this;
	}
	/**
	 * Fail the auth request by setting a checkout step to return to and throwing
	 * an exception.
	 * @see self::_setCheckoutStep for available checkout steps to return to
	 * @param  string $errorMessage
	 * @param  string $returnStep Step of checkout to send the user to
	 * @throws EbayEnterprise_CreditCard_Exception Always
	 */
	protected function _failPaymentAuth($errorMessage, $returnStep='payment')
	{
		$this->_setCheckoutStep($returnStep);
		throw Mage::exception('EbayEnterprise_CreditCard', $this->_helper->__($errorMessage));
	}
	/**
	 * Get the API SDK for the payment auth request.
	 * @param  Varien_Object $payment
	 * @return Api\IBidirectionalApi
	 */
	protected function _getApi(Varien_Object $payment)
	{
		$config = $this->_helper->getConfigModel();
		return $this->_coreHelper->getSdkApi(
			$config->apiService,
			$config->apiOperation,
			array($this->_helper->getTenderTypeForCcType($payment->getCcType()))
		);
	}
	/**
	 * Make the API request and handle any exceptions.
	 * @param  ApiIBidirectionalApi $api
	 * @return self
	 */
	protected function _sendAuthRequest(Api\IBidirectionalApi $api)
	{
		try {
			$api->send();
		} catch (Payload\Exception\InvalidPayload $e) {
			// Invalid payloads cannot be valid - log the error and fail the auth
			$this->_logger->logWarn('[%s] Credit card auth payload invalid: %s', array(__CLASS__, $e->getMessage()));
			$this->_failPaymentAuth(self::CREDITCARD_AUTH_FAILED_MESSAGE);
		} catch (Api\Exception\NetworkError $e) {
			// Can't accept an auth request that could not be made successfully - log
			// the error and fail the auth.
			$this->_logger->logWarn('[%s] Credit card auth request failed: %s', array(__CLASS__, $e->getMessage()));
			$this->_failPaymentAuth(self::CREDITCARD_AUTH_FAILED_MESSAGE);
		}
		return $this;
	}
	/**
	 * Update payment objects with details of the auth request and response. Validate
	 * that a successful response was received.
	 * @param  ApiIBidirectionalApi $api
	 * @param  Varien_Object        $payment
	 * @return self
	 */
	protected function _handleApiResponse(Api\IBidirectionalApi $api, Varien_Object $payment)
	{
		$request = $api->getRequestBody();
		$response = $api->getResponseBody();
		return $this->_updatePaymentsWithAuthData($payment, $request, $response)->_validateResponse($response);
	}
}
