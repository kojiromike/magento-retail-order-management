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

use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\IShippingAddress;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemIterable;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemContainer;

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
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;

    /**
     * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
     * Override __construct here as the usual protected `_construct` is not called.
     *
     * @param array $initParams May contain:
     *                          -  'helper' => EbayEnterprise_PayPal_Helper_Data
     *                          -  'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
     *                          -  'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          -  'context' => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $initParams = array())
    {
        list($this->helper, $this->coreHelper, $this->logger, $this->logContext) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_paypal')),
            $this->nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
        $this->config = $this->helper->getConfigModel();
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param EbayEnterprise_PayPal_Helper_Data
     * @param EbayEnterprise_Eb2cCore_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_PayPal_Helper_Data $helper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Set Express Checkout Request/ Response
     *
     * @param  string
     * @param  string
     * @param  Mage_Sales_Model_Quote
     * @return array
     *
     * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function setExpressCheckout($returnUrl, $cancelUrl, Mage_Sales_Model_Quote $quote)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationSetExpressCheckout
        );

        $payload = $sdk->getRequestBody();
        $payload->setOrderId($quote->reserveOrderId()->getReservedOrderId())
            ->setReturnUrl($returnUrl)
            ->setCancelUrl($cancelUrl)
            ->setLocaleCode(Mage::app()->getLocale()->getDefaultLocale())
            ->setAmount($this->getTotal('grand_total', $quote))
            ->setCurrencyCode($quote->getQuoteCurrencyCode());
        if ($quote->getShippingAddress()->getId()) {
            $payload->setAddressOverride(true);
            $this->addShippingAddress($quote->getShippingAddress(), $payload);
        }
        $this->addLineItems($quote, $payload);
        $sdk->setRequestBody($payload);
        $this->logApiCall('set express', $sdk->getRequestBody()->serialize(), 'request');
        $reply = $this->sendRequest($sdk);
        if (!$reply->isSuccess() || is_null($reply->getToken())) {
            // Only set and do express have the error message in the reply.
            $logMessage =
                'SetExpressCheckout request failed with message ({reply_message}). See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(
                __CLASS__,
                ['reply_message' => $reply->getErrorMessage()]
            ));
            $e = Mage::exception('EbayEnterprise_PayPal', $this->helper->__(self::EBAYENTERPRISE_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        $this->logApiCall('set express', $reply->serialize(), 'response');
        return [
            'method' => EbayEnterprise_PayPal_Model_Method_Express::CODE,
            'token'  => $reply->getToken()
        ];
    }

    /**
     * Log the different requests consistently.
     *
     * @param string $type 'do authorization', 'do express', 'do void', 'get express', 'set express'
     * @param string $body the serialized xml body
     * @param string $direction 'request' or 'response'
     */
    protected function logApiCall($type, $body, $direction)
    {
        $logData = ['type' => $type, 'direction' => $direction];
        $logMessage = 'Processing PayPal {type} {direction}';
        $this->logger->info($logMessage, $this->logContext->getMetaData(__CLASS__, $logData));

        $logData = ['rom_request_body' => $body];
        $logMessage = 'Request Data';
        $this->logger->debug($logMessage, $this->logContext->getMetaData(__CLASS__, $logData));
    }

    /**
     * Get Express Checkout Request/ Response
     *
     * @param  Mage_Sales_Model_Quote
     * @param  string                 $token as from setExpressCheckout
     * @param  string
     * @return array
     *
     * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function getExpressCheckout($orderId, $token, $currencyCode)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationGetExpressCheckout
        );
        $payload = $sdk->getRequestBody();
        $payload->setOrderId($orderId)
            ->setToken($token)
            ->setCurrencyCode($currencyCode);
        $sdk->setRequestBody($payload);
        $this->logApiCall('get express', $sdk->getRequestBody()->serialize(), 'request');
        $reply = $this->sendRequest($sdk);
        if (!$reply->isSuccess()) {
            $logMessage = 'PayPal request failed. See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $e = Mage::exception('EbayEnterprise_PayPal', $this->helper->__(self::EBAYENTERPRISE_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        $this->logApiCall('get express', $reply->serialize(), 'response');
        return [
            'method'           => EbayEnterprise_PayPal_Model_Method_Express::CODE,
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
            'billing_address'  => [
                'street'      => $reply->getBillingLines(),
                'city'        => $reply->getBillingCity(),
                'region_code' => $reply->getBillingMainDivision(),
                'postcode'    => $reply->getBillingPostalCode(),
                'country_id'  => $reply->getBillingCountryCode(),
                'status'      => $reply->getBillingAddressStatus(),
            ],
            'shipping_address' => [
                'street'      => $reply->getShipToLines(),
                'city'        => $reply->getShipToCity(),
                'region_code' => $reply->getShipToMainDivision(),
                'postcode'    => $reply->getShipToPostalCode(),
                'country_id'  => $reply->getShipToCountryCode(),
                'status'      => $reply->getShippingAddressStatus(),
            ],
        ];
    }

    /**
     * Do Express Checkout Request/ Response
     *
     * @param  Mage_Sales_Model_Quote
     * @param  string $token as from setExpressCheckout
     * @param  string $payerId as from getExpressCheckout or from a PayPal redirected URL
     * @param  string $pickUpStoreId as from getExpressCheckout or from a PayPal redirected URL (optional)
     * @return array
     *
     * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function doExpressCheckout(Mage_Sales_Model_Quote $quote, $token, $payerId, $pickUpStoreId = null)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoExpressCheckout
        );
        $payload = $sdk->getRequestBody();
        $payload->setRequestId(
            $this->coreHelper->generateRequestId(
                self::PAYPAL_DOEXPRESS_REQUEST_ID_PREFIX
            )
        )
            ->setOrderId($quote->reserveOrderId()->getReservedOrderId())
            ->setToken($token)
            ->setPayerId($payerId)
            ->setCurrencyCode($quote->getQuoteCurrencyCode())
            ->setAmount($this->getTotal('grand_total', $quote));
        /** @var Mage_Sales_Model_Quote_Address $shippingAddress */
        $shippingAddress = $quote->getIsVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $this->addShippingAddress($shippingAddress, $payload);
        if ($pickUpStoreId) {
            $payload->setPickUpStoreId($pickUpStoreId);
        }
        $this->addLineItems($quote, $payload);
        $sdk->setRequestBody($payload);
        $this->logApiCall('do express', $sdk->getRequestBody()->serialize(), 'request');
        $reply = $this->sendRequest($sdk);
        if (!$reply->isSuccess()) {
            $logData = ['error_message' => $reply->getErrorMessage()];
            $logMessage = 'PayPal request failed with message "{error_message}". See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__, $logData));
            $e = Mage::exception('EbayEnterprise_PayPal', $this->helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        $this->logApiCall('do express', $reply->serialize(), 'response');
        return [
            'method'          => EbayEnterprise_PayPal_Model_Method_Express::CODE,
            'order_id'        => $reply->getOrderId(),
            'transaction_id'  => $reply->getTransactionId(),
            'response_code'   => $reply->getResponseCode(),
            'auth_request_id' => $payload->getRequestId(),
        ];
    }

    /**
     * Do Authorization Request/ Response
     *
     * @param  Mage_Sales_Model_Quote
     * @return array
     *
     * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function doAuthorization(Mage_Sales_Model_Quote $quote)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoAuthorization
        );
        $payload = $sdk->getRequestBody();
        $payload->setRequestId(
            $this->coreHelper->generateRequestId(
                self::PAYPAL_DOAUTHORIZATION_REQUEST_ID_PREFIX
            )
        )
            ->setOrderId($quote->reserveOrderId()->getReservedOrderId())
            ->setCurrencyCode($quote->getQuoteCurrencyCode())
            ->setAmount($this->getTotal('grand_total', $quote));
        $sdk->setRequestBody($payload);
        $this->logApiCall('do authorization', $sdk->getRequestBody()->serialize(), 'request');
        $reply = $this->sendRequest($sdk);
        $isSuccess = $reply->isSuccess();
        if (!$isSuccess) {
            $logMessage = 'PayPal request failed.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $e = Mage::exception('EbayEnterprise_PayPal', $this->helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        $this->logApiCall('do authorization', $reply->serialize(), 'response');
        return [
            'method'         => EbayEnterprise_PayPal_Model_Method_Express::CODE,
            'order_id'       => $reply->getOrderId(),
            'payment_status' => $reply->getPaymentStatus(),
            'pending_reason' => $reply->getPendingReason(),
            'reason_code'    => $reply->getReasonCode(),
            'is_authorized'  => $isSuccess,
        ];
    }

    /**
     * Do Void Request/Response
     *
     * @param  Mage_Sales_Model_Order
     * @return array
     *
     * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function doVoid(Mage_Sales_Model_Order $order)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoVoid
        );
        $payload = $sdk->getRequestBody();
        $payload->setOrderId($order->getIncrementId())
            ->setRequestId($this->coreHelper->generateRequestId(self::PAYPAL_DOVOID_REQUEST_ID_PREFIX))
            ->setCurrencyCode($order->getOrderCurrencyCode());
        $sdk->setRequestBody($payload);
        $this->logApiCall('do void', $sdk->getRequestBody()->serialize(), 'request');
        $reply = $this->sendRequest($sdk);
        $isVoided = $reply->isSuccess();
        if (!$reply->isSuccess()) {
            $logMessage = 'PayPal DoVoid failed. See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $e = Mage::exception('EbayEnterprise_PayPal', $this->helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        $this->logApiCall('do void', $reply->serialize(), 'response');
        return [
            'method'    => EbayEnterprise_PayPal_Model_Method_Express::CODE,
            'order_id'  => $reply->getOrderId(),
            'is_voided' => $isVoided
        ];
    }

    /**
     * Add an address to a payload
     *
     * @param  Mage_Sales_Model_Address_Abstract
     * @param  IShippingAddress
     * @return self
     */
    protected function addShippingAddress(Mage_Sales_Model_Quote_Address $shippingAddress, IShippingAddress $payload)
    {
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
     * @param  IBidirectionalApi
     * @return Payload
     *
     * @throws EbayEnterprise_PayPal_Exception
     * @throws EbayEnterprise_PayPal_Exception_Network
     */
    protected function sendRequest(IBidirectionalApi $sdk)
    {
        try {
            $sdk->send();
            $reply = $sdk->getResponseBody();
            return $reply;
        } catch (InvalidPayload $e) {
            $logMessage = 'PayPal payload invalid. See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
        } catch (NetworkError $e) {
            $logMessage = 'PayPal request encountered a network error. See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
        }
        $e = Mage::exception('EbayEnterprise_PayPal', $this->helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
        $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
        throw $e;
    }

    /**
     * Get the API SDK for the operation.
     *
     * @param  Varien_Object
     * @return IBidirectionalApi
     */
    protected function getSdk($operation)
    {
        return $this->coreHelper->getSdkApi($this->config->apiService, $operation);
    }

    /**
     * Generate ILineItem objects for each item and add to the container payload.
     *
     * @param Mage_Sales_Model_Quote
     * @param ILineItemContainer
     */
    protected function addLineItems(Mage_Sales_Model_Quote $quote, ILineItemContainer $container)
    {
        if ($this->canIncludeLineItems($quote)) {
            $this->processLineItems($quote, $container->getLineItems())
                ->processNegativeLineItems($quote, $container->getLineItems());
            $container->calculateLineItemsTotal();
            $container->setShippingTotal($this->getTotal('shipping', $quote));
            $container->setTaxTotal($this->getTotal('ebayenterprise_tax', $quote));
            $container->setCurrencyCode($quote->getQuoteCurrencyCode());
        }
    }

    /**
     * recursively process line items into payloads
     * @param  Mage_Sales_Model_Quote
     * @param  ILineItemIterable
     * @return self
     */
    protected function processLineItems(Mage_Sales_Model_Quote $quote, ILineItemIterable $lineItems)
    {
        $items = $quote->getAllItems();
        $currencyCode = $quote->getQuoteCurrencyCode();
        foreach ($items as $item) {
            $this->processItem($item, $lineItems, $currencyCode);
        }
        return $this;
    }

    /**
     * process specific amount types into negative-value line item
     * payloads
     * @param  Mage_Sales_Model_Quote
     * @param  ILineItemIterable
     * @return self
     */
    protected function processNegativeLineItems(Mage_Sales_Model_Quote $quote, ILineItemIterable $lineItems)
    {
        $negativeAmountTypes = array('discount', 'giftcardaccount', 'ebayenterprise_giftcard');
        $currencyCode = $quote->getQuoteCurrencyCode();
        foreach ($negativeAmountTypes as $totalType) {
            $totalAmount = $this->getTotal($totalType, $quote);
            if ($totalAmount) {
                // ensure all amounts are negative
                $totalAmount = -abs($totalAmount);
                $lineItem = $lineItems->getEmptyLineItem();
                $lineItem->setName($this->helper->__($totalType))
                    ->setSequenceNumber($totalType)
                    ->setQuantity(1)
                    ->setUnitAmount($totalAmount)
                    ->setCurrencyCode($currencyCode);
                $lineItems->offsetSet($lineItem, null);
            }
        }
        return $this;
    }

    /**
     * return true if the line items can be included in the message
     * @param  Mage_Sales_Model_Quote
     * @return bool
     */
    protected function canIncludeLineItems($quote)
    {
        $reductions = -$this->getTotal('discount', $quote);
        $reductions += $this->getTotal('giftcardaccount', $quote);
        $reductions += $this->getTotal('ebayenterprise_giftcard', $quote);
        // due to the way paypal verifies line items total and the need to send
        // discount/giftcard (admustment) amounts as negative line items, the LineItemsTotal
        // will not match what paypal is expecting when the adustment amounts add up to more
        // than the total amount for the line items.
        return $this->config->transferLines &&
            $this->getTotal('subtotal', $quote) >= $reductions;
    }

    /**
     * Process an item or, if the item has children that are calculated,
     * the item's children. Processing an item consists of building an ILineItem
     * payload to be added to the ILineItemIterable. The total for each line is
     * recursively summed and returned.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @param  ILineItemIterable
     * @param  string
     * @return self
     */
    protected function processItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        ILineItemIterable $lineItems,
        $currencyCode
    ) {
        // handle the possibility of getting a Mage_Sales_Model_Quote_Address_Item
        $item = $item->getQuoteItem() ?: $item;
        if ($item->getHasChildren() && $item->isChildrenCalculated()) {
            foreach ($item->getChildren() as $child) {
                $this->processItem($child, $lineItems, $currencyCode);
            }
        } else {
            $lineItem = $lineItems->getEmptyLineItem();
            $lineItem->setName($this->helper->__($item->getProduct()->getName()))
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
     * @param  string
     * @param  Mage_Sales_Model_Quote
     * @return float
     */
    protected function getTotal($type, Mage_Sales_Model_Quote $quote)
    {
        $totals = $quote->getTotals();
        return isset($totals[$type]) ? $totals[$type]->getValue() : 0.0;
    }
}
