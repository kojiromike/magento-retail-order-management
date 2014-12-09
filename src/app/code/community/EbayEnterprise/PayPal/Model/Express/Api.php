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

/**
 * Payment Method for PayPal payments through Retail Order Management.
 * @SuppressWarnings(TooManyMethods)
 */
class EbayEnterprise_Paypal_Model_Express_Api
{
	const EBAYENTERPRISE_PAYPAL_API_FAILED = 'EBAYENTERPRISE_PAYPAL_API_FAILED';

	const PAYPAL_SETEXPRESS_REQUEST_ID_PREFIX = 'PSE-';
	const PAYPAL_GETEXPRESS_REQUEST_ID_PREFIX = 'PSG-';
	const PAYPAL_DOEXPRESS_REQUEST_ID_PREFIX = 'PSD-';
	const PAYPAL_DOAUTHORIZATION_REQUEST_ID_PREFIX = 'PSA-';
	const PAYPAL_DOVOID_REQUEST_ID_PREFIX = 'PSV-';

	/** @var EbayEnterprise_PayPal_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/**
	 * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
	 * Override __construct here as the usual protected `_construct` is not called.
	 *
	 * @param array $initParams May contain:
	 *                          -  'helper' => EbayEnterprise_PayPal_Helper_Data
	 *                          -  'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 *                          -  'logger' => EbayEnterprise_MageLog_Helper_Data
	 */
	public function __construct(array $initParams = array())
	{
		$paypalHelper = Mage::helper('ebayenterprise_paypal');
		$coreHelper = Mage::helper('eb2ccore');
		$logHelper = Mage::helper('ebayenterprise_magelog');
		list($this->_helper, $this->_coreHelper, $this->_logger)
			= $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'helper', $paypalHelper),
			$this->_nullCoalesce($initParams, 'core_helper', $coreHelper),
			$this->_nullCoalesce($initParams, 'logger', $logHelper)
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param EbayEnterprise_PayPal_Helper_Data   $helper
	 * @param EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	 * @param EbayEnterprise_MageLog_Helper_Data  $logger
	 *
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_PayPal_Helper_Data $helper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_MageLog_Helper_Data $logger
	) {
		return array($helper, $coreHelper, $logger);
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 *
	 * @param  array      $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed      $default
	 *
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Set Express Checkout Request/ Response
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 *
	 * @return array of 'assignData' values
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function setExpressCheckout(
		$returnUrl, $cancelUrl, Mage_Sales_Model_Quote $quote
	) {
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationSetExpressCheckout
		);

		$payload = $sdk->getRequestBody();
		$payload->setOrderId($quote->reserveOrderId()->getReservedOrderId())
			->setReturnUrl($returnUrl)
			->setCancelUrl($cancelUrl)
			->setLocaleCode(Mage::app()->getLocale()->getDefaultLocale())
			->setAmount($this->_getTotal('grand_total', $quote))
			->setCurrencyCode($quote->getQuoteCurrencyCode());
		if ($quote->getShippingAddress()->getId()) {
			$payload->setAddressOverride(true);
			$this->_addShippingAddress($quote->getShippingAddress(), $payload);
		}
		$this->_addLineItems($quote, $payload);
		$sdk->setRequestBody($payload);
		$this->_sendRequest($sdk);
		$reply = $sdk->getResponseBody();
		if (!$reply->isSuccess() || is_null($reply->getToken())) {
			$this->_logger->logWarn(
				'[%s] SetExpressCheckout request failed: %s',
				array(__CLASS__, $reply->getErrorMessage())
			);
			throw Mage::exception(
				'EbayEnterprise_PayPal',
				$this->_helper->__(self::EBAYENTERPRISE_PAYPAL_API_FAILED)
			);
		}
		return array('token' => $reply->getToken());
	}

	/**
	 * Get Express Checkout Request/ Response
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @param string                 $token as from setExpressCheckout
	 *
	 * @return PayPalGetExpressCheckoutReply $reply
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function getExpressCheckout($orderId, $token, $currencyCode)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationGetExpressCheckout
		);
		$this->_logger->logDebug(
			'[%s] GetExpressCheckout started', array(__CLASS__)
		);
		$payload = $sdk->getRequestBody();
		$payload->setOrderId($orderId)
			->setToken($token)
			->setCurrencyCode($currencyCode);
		$sdk->setRequestBody($payload);
		$this->_sendRequest($sdk);
		$reply = $sdk->getResponseBody();
		if (!$reply->isSuccess()) {
			$this->_logger->logWarn(
				'[%s] PayPal request failed', array(__CLASS__)
			);
			throw Mage::exception(
				'EbayEnterprise_PayPal',
				$this->_helper->__(self::EBAYENTERPRISE_PAYPAL_API_FAILED)
			);
		}
		$this->_logger->logDebug(
			'[%s] GetExpressCheckout completed', array(__CLASS__)
		);
		return array(
			'order_id'         => $reply->getOrderId(),
			'country_id'       => $reply->getPayerCountry(),
			'email'            => $reply->getPayerEmail(),
			'firstname'        => $reply->getPayerFirstName(),
			'payer_id'         => $reply->getPayerId(),
			'lastname'         => $reply->getPayerLastName(),
			'middlename'       => $reply->getPayerMiddleName(),
			'suffix'           => $reply->getPayerNameHonorific(),
			'phone'            => $reply->getPayerPhone(),
			'status'           => $reply->getPayerStatus(),
			'response_code'    => $reply->getResponseCode(),
			'billing_address'  => array(
				'street'      => $reply->getBillingLines(),
				'city'        => $reply->getBillingCity(),
				'region_code' => $reply->getBillingMainDivision(),
				'postcode'    => $reply->getBillingPostalCode(),
				'country_id'  => $reply->getBillingCountryCode(),
				//				'reply' => $address->getAddressStatus(),
			),
			'shipping_address' => array(
				'street'      => $reply->getShipToLines(),
				'city'        => $reply->getShipToCity(),
				'region_code' => $reply->getShipToMainDivision(),
				'postcode'    => $reply->getShipToPostalCode(),
				'country_id'  => $reply->getShipToCountryCode(),
				//				'reply' => $address->getAddressStatus(),
			)
		);
	}

	/**
	 * convert an IPayPalddress payload into a mage address
	 * data array.
	 *
	 * @param  Payload\Payment\IPayPalddress $address
	 *
	 * @return array
	 */
	protected function _convertToAddressData(
		Payload\Payment\IPayPalAddress $address
	) {
		return array(
			'street'         => $address->getLines(),
			'city'           => $address->getCity(),
			'region_code'    => $address->getMainDivision(),
			'postcode'       => $address->getPostalCode(),
			'country_id'     => $address->getCountryCode(),
			'address_status' => $address->getAddressStatus(),
		);
	}

	/**
	 * Do Express Checkout Request/ Response
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @param string                 $token         as from setExpressCheckout
	 * @param string                 $payerId       as from getExpressCheckout or from a PayPal redirected URL
	 * @param string                 $pickUpStoreId as from getExpressCheckout or from a PayPal redirected URL
	 *
	 * @return PayPalGetExpressCheckoutReply $reply
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function doExpressCheckout(
		Mage_Sales_Model_Quote $quote, $token, $payerId, $pickUpStoreId = null
	) {
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationDoExpressCheckout
		);
		$payload = $sdk->getRequestBody();
		$payload->setRequestId(
			$this->_coreHelper->generateRequestId(
				self::PAYPAL_DOEXPRESS_REQUEST_ID_PREFIX
			)
		)
			->setOrderId($quote->reserveOrderId()->getReservedOrderId())
			->setToken($token)
			->setPayerId($payerId)
			->setCurrencyCode($quote->getQuoteCurrencyCode())
			->setAmount($this->_getTotal('grand_total', $quote));
		$this->_addShippingAddress($quote->getShippingAddress(), $payload);
		if ($pickUpStoreId) {
			$payload->setPickUpStoreId($pickUpStoreId);
		}
		$this->_addLineItems($quote, $payload);
		$sdk->setRequestBody($payload);
		$this->_sendRequest($sdk);
		$reply = $sdk->getResponseBody();
		if (!$reply->isSuccess()) {
			$this->_logger->logWarn(
				'[%s] PayPal request failed', array(__CLASS__)
			);
			throw Mage::exception(
				'EbayEnterprise_PayPal',
				$this->_helper->__($reply->getErrorMessage())
			);
		}
		return array(
			'order_id'       => $reply->getOrderId(),
			'transaction_id' => $reply->getTransactionId(),
			'response_code'  => $reply->getResponseCode(),
		);
	}

	/**
	 * Do Authorization Request/ Response
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 *
	 * @return PayPalAuthorizationReply $reply
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function doAuthorization(Mage_Sales_Model_Quote $quote)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationDoAuthorization
		);
		$payload = $sdk->getRequestBody();
		$payload->setRequestId(
			$this->_coreHelper->generateRequestId(
				self::PAYPAL_DOAUTHORIZATION_REQUEST_ID_PREFIX
			)
		)
			->setOrderId($quote->reserveOrderId()->getReservedOrderId())
			->setCurrencyCode($quote->getQuoteCurrencyCode())
			->setAmount($this->_getTotal('grand_total', $quote));
		$sdk->setRequestBody($payload);
		$this->_logger->logDebug(
			'[%s] Sending DoAuthorization', array(__CLASS__)
		);

		$this->_sendRequest($sdk);
		$reply = $sdk->getResponseBody();
		$this->_logger->logDebug(
			'[%s] Received DoAuthorization response', array(__CLASS__)
		);
		$isSuccess = $reply->isSuccess();
		if (!$isSuccess) {
			$this->_logger->logWarn(
				'[%s] PayPal request failed', array(__CLASS__)
			);
			throw Mage::exception(
				'EbayEnterprise_PayPal',
				$this->_helper->__($reply->getErrorMessage())
			);
		}
		return array(
			'order_id'       => $reply->getOrderId(),
			'payment_status' => $reply->getPaymentStatus(),
			'pending_reason' => $reply->getPendingReason(),
			'reason_code'    => $reply->getReasonCode(),
			'is_authorized'  => $isSuccess,
		);
	}

	/**
	 * Do Void Request/Response
	 *
	 * @param  Mage_Sales_Model_Order $order
	 *
	 * @return PayPalAuthorizationReply $reply
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function doVoid(Mage_Sales_Model_Order $order)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationDoVoid
		);
		$payload = $sdk->getRequestBody();
		$payload->setOrderId($order->getIncrementId())
			->setRequestId(
				$this->_coreHelper->generateRequestId(
					self::PAYPAL_DOVOID_REQUEST_ID_PREFIX
				)
			)
			->setCurrencyCode($order->getOrderCurrencyCode());
		$sdk->setRequestBody($payload);
		$this->_logger->logDebug('[%s] Sending DoVoid', array(__CLASS__));
		$this->_sendRequest($sdk);
		$reply = $sdk->getResponseBody();
		$this->_logger->logDebug(
			'[%s] Received DoVoid response', array(__CLASS__)
		);
		$isVoided = $reply->isSuccess();
		if (!$reply->isSuccess()) {
			$this->_logger->logWarn(
				'[%s] PayPal DoVoid failed', array(__CLASS__)
			);
			throw Mage::exception(
				'EbayEnterprise_PayPal',
				$this->_helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED)
			);
		}
		return array(
			'order_id'  => $reply->getOrderId(),
			'is_voided' => $isVoided
		);
	}

	/**
	 * Add an address to a payload
	 *
	 * @param Mage_Sales_Model_Address_Abstract $shippingAddress
	 * @param Payload\Payment\IShippingAddress  $payload
	 *
	 * @return self
	 */
	protected function _addShippingAddress(
		Mage_Sales_Model_Quote_Address $shippingAddress,
		Payload\Payment\IShippingAddress $payload
	) {
		$payload->setShipToLines(implode('\n', $shippingAddress->getStreet()));
		$payload->setShipToCity($shippingAddress->getCity());
		$payload->setShipToMainDivision($shippingAddress->getRegionCode());
		$payload->setShipToCountryCode($shippingAddress->getCountryId());
		$payload->setShipToPostalCode($shippingAddress->getPostcode());
		return $this;
	}

	/**
	 * Send the request via the sdk
	 *
	 * @param  ApiIBidirectionalApi $sdk
	 *
	 * @throws EbayEnterprise_PayPal_Exception
	 * @throws EbayEnterprise_PayPal_Exception_Network
	 * @return self
	 */
	protected function _sendRequest(Api\IBidirectionalApi $sdk)
	{
		try {
			$this->_logger->logDebug(
				"[%s] Sending request:\n%s",
				array(__CLASS__, $sdk->getRequestBody()->serialize())
			);
			$sdk->send();
			$reply = $sdk->getResponseBody();
			$this->_logger->logDebug(
				"[%s] Received reply:\n%s",
				array(__CLASS__, $reply->serialize())
			);
		} catch (Payload\Exception\InvalidPayload $e) {
			$this->_logger->logWarn(
				"[%s] PayPal payload invalid:\n%s", array(__CLASS__, $e)
			);
			throw Mage::exception(
				'EbayEnterprise_PayPal',
				$this->_helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED)
			);
		} catch (Api\Exception\NetworkError $e) {
			$this->_logger->logWarn(
				"[%s] PayPal request failed:\n%s", array(__CLASS__, $e)
			);
			throw Mage::exception(
				'EbayEnterprise_PayPal',
				$this->_helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED)
			);
		}
		return $this;
	}

	/**
	 * Get the API SDK for the operation.
	 *
	 * @param  Varien_Object $payment
	 *
	 * @return Api\IBidirectionalApi
	 */
	protected function _getSdk($operation)
	{
		$config = $this->_helper->getConfigModel();
		return $this->_coreHelper->getSdkApi($config->apiService, $operation);
	}

	/**
	 * Generate ILineItem objects for each item and add to the container payload.
	 *
	 * @param array                     $items
	 * @param PaymentILineItemContainer $container
	 */
	protected function _addLineItems(
		Mage_Sales_Model_Quote $quote,
		Payload\Payment\ILineItemContainer $container
	) {
		$items = (array)$quote->getAllItems();
		$lineItems = $container->getLineItems();
		$currencyCode = $quote->getQuoteCurrencyCode();
		foreach ($items as $item) {
			$this->_processItem($item, $lineItems, $currencyCode);
		}
		foreach (
			array('discount', 'giftcardaccount', 'ebayenterprise_giftcard') as
			$totalType
		) {
			$totalAmount = $this->_getTotal($totalType, $quote);
			if ($totalAmount) {
				$lineItem = $lineItems->getEmptyLineItem();
				$lineItem->setName($totalType)
					->setSequenceNumber($totalType)
					->setQuantity(1)
					->setUnitAmount($totalAmount)
					->setCurrencyCode($currencyCode);
				$lineItems->offsetSet($lineItem, null);
			}
		}
		$lineItems->calculateLineItemsTotal();
		$lineItems->setShippingTotal($this->_getTotal('shipping', $quote));
		$lineItems->setTaxTotal($this->_getTotal('tax', $quote));
		$lineItems->setCurrencyCode($quote->getQuoteCurrencyCode());
	}

	/**
	 * Process an item or, if the item has children that are calculated,
	 * the item's children. Processing an item consists of building an ILineItem
	 * payload to be added to the ILineItemIterable. The total for each line is
	 * recursively summed and returned.
	 *
	 * @param  Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param  PaymentILineItemIterable             $lineItems
	 * @param  string                               $currencyCode
	 *
	 * @return self
	 */
	protected function _processItem(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		Payload\Payment\ILineItemIterable $lineItems, $currencyCode
	) {
		// handle the possibility of getting a Mage_Sales_Model_Quote_Address_Item
		$item = $item->getQuoteItem() ?: $item;
		if ($item->getHasChildren() && $item->isChildrenCalculated()) {
			foreach ($item->getChildren() as $child) {
				$this->_processItem($child, $lineItems, $currencyCode);
			}
		} else {
			$lineItem = $lineItems->getEmptyLineItem();
			$lineItem->setName($item->getProduct()->getName())
				->setSequenceNumber($item->getId())
				->setQuantity($item->getQty())
				->setUnitAmount($item->getPrice())
				->setCurrencyCode($currencyCode);
			$lineItems->offsetSet($lineItem, null);
		}
		return $this;
	}

	/**
	 * Get the specified total amount for the quote.
	 *
	 * @param  string                 $type
	 * @param  Mage_Sales_Model_Quote $quote
	 *
	 * @return float
	 */
	protected function _getTotal($type, Mage_Sales_Model_Quote $quote)
	{
		$totals = $quote->getTotals();
		return isset($totals[$type]) ? $totals[$type]->getValue() : 0.0;
	}
}
