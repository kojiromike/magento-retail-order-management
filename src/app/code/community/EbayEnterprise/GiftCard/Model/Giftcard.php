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

use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Model representing a gift card that has been applied to an order.
 */
class EbayEnterprise_GiftCard_Model_Giftcard implements EbayEnterprise_GiftCard_Model_IGiftcard
{
    const BALANCE_REQUST_ID_PREFIX = 'GCB-';
    const REDEEM_REQUST_ID_PREFIX = 'GCR-';
    const VOID_REQUST_ID_PREFIX = 'GCV-';
    const REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed';
    const BALANCE_REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed_Balance';
    const REDEEM_REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed_Redeem';
    const VOID_REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed_Void';

    /**
     * Externalization of gift card data - number, pin, tender type, etc. When
     * persisting gift card data in the session, only the memo object will be
     * put into the session and gift card models will be restored or reconstructed
     * using memo data from the session.
     *
     * @var EbayEnterprise_GiftCard_Model_Giftcard_Memo
     */
    protected $memo;
    /** @var EbayEnterprise_GiftCard_Helper_Data **/
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data **/
    protected $coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data **/
    protected $logger;
    /** @var LoggerInterface **/
    protected $apiLogger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /** @var EbayEnterprise_GiftCard_Model_Mask */
    protected $mask;
    /** @var string */
    protected $tenderType;
    /** @var EbayEnterprise_GiftCard_Helper_Tendertype */
    protected $tenderTypeHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;

    /**
     * @param array $initParams May contain:
     *                          - 'helper' => EbayEnterprise_GiftCard_Helper_Data
     *                          - 'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
     *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
     *                          - 'api_logger' => LoggerInterface
     *                          - 'mask' => EbayEnterprise_GiftCard_Model_Mask
     *                          - 'config' => EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function __construct(array $initParams = [])
    {
        list(
            $this->tenderTypeHelper,
            $this->helper,
            $this->coreHelper,
            $this->logger,
            $this->context,
            $this->apiLogger,
            $this->mask,
            $this->memo,
            $this->config
        ) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'tender_type_helper', Mage::helper('ebayenterprise_giftcard/tendertype')),
            $this->nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_giftcard')),
            $this->nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($initParams, 'api_logger', new NullLogger),
            $this->nullCoalesce($initParams, 'mask', Mage::getModel('ebayenterprise_giftcard/mask')),
            $this->nullCoalesce($initParams, 'memo', Mage::getModel('ebayenterprise_giftcard/giftcard_memo')),
            $this->nullCoalesce($initParams, 'config', Mage::helper('ebayenterprise_giftcard')->getConfigModel())
        );
    }

    /**
     * Type checks for self::__construct $initParams.
     * @param  EbayEnterprise_Giftcard_Helper_Tendertype
     * @param  EbayEnterprise_Giftcard_Helper_Data
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @param  LoggerInterface
     * @param  EbayEnterprise_GiftCard_Model_Mask
     * @param  EbayEnterprise_GiftCard_Model_Giftcard_Memo
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return mixed[]
     */
    protected function checkTypes(
        EbayEnterprise_Giftcard_Helper_Tendertype $tenderTypeHelper,
        EbayEnterprise_Giftcard_Helper_Data $helper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context,
        LoggerInterface $apiLogger,
        EbayEnterprise_GiftCard_Model_Mask $mask,
        EbayEnterprise_GiftCard_Model_Giftcard_Memo $memo,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array      $arr
     * @param  string|int $field Valid array key
     * @param  mixed      $default
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    public function setOrderId($orderId)
    {
        $this->memo->setOrderId($orderId);
        return $this;
    }

    public function getOrderId()
    {
        return $this->memo->getOrderId();
    }

    /**
     * Set the card number of the gift card. If the card number is changed after
     * receiving a tokenized card number, the tokenized card number will be
     * cleared out as it is not expected to be relevant to the new card number.
     * @param string $cardNumber
     * @return self
     */
    public function setCardNumber($cardNumber)
    {
        // Tokenized number is no longer valid after changing the non-tokenized number.
        if ($this->memo->getCardNumber() !== $cardNumber) {
            $this->memo->setTokenizedCardNumber(null);
        }
        $this->memo->setCardNumber($cardNumber);
        return $this;
    }

    public function getCardNumber()
    {
        return $this->memo->getCardNumber();
    }

    /**
     * get the tender type for the giftcard. perform a lookup
     * using the tendertype service if not yet set.
     *
     * @see EbayEnterprise_GiftCard_Helper_Tendertype::lookupTenderType
     * @return string
     */
    public function getTenderType()
    {
        if (!$this->memo->getTenderType()) {
            $this->memo->setTenderType($this->tenderTypeHelper->lookupTenderType(
                $this->getCardNumber(),
                $this->getBalanceCurrencyCode(),
                $this->getPanIsToken()
            ));
        }
        return $this->memo->getTenderType();
    }

    public function setTokenizedCardNumber($tokenizedCardNumber)
    {
        $this->memo->setTokenizedCardNumber($tokenizedCardNumber);
        return $this;
    }

    public function getTokenizedCardNumber()
    {
        return $this->memo->getTokenizedCardNumber();
    }

    public function setPin($pin)
    {
        $this->memo->setPin($pin);
        return $this;
    }

    public function getPin()
    {
        return $this->memo->getPin();
    }

    public function setPanIsToken($isToken)
    {
        $this->memo->setPanIsToken($isToken);
        return $this;
    }

    public function getPanIsToken()
    {
        return $this->memo->getPanIsToken();
    }

    public function setBalanceRequestId($requestId)
    {
        $this->memo->setBalanceRequestId($requestId);
        return $this;
    }

    public function getBalanceRequestId()
    {
        return $this->memo->getBalanceRequestId();
    }

    public function setRedeemRequestId($requestId)
    {
        $this->memo->setRedeemRequestId($requestId);
        return $this;
    }

    public function getRedeemRequestId()
    {
        return $this->memo->getRedeemRequestId();
    }

    public function setRedeemVoidRequestId($requestId)
    {
        $this->memo->setRedeemVoidRequestId($requestId);
        return $this;
    }

    public function getRedeemVoidRequestId()
    {
        return $this->memo->getRedeemVoidRequestId();
    }

    public function setAmountToRedeem($amount)
    {
        $this->memo->setAmountToRedeem($amount);
        return $this;
    }

    public function getAmountToRedeem()
    {
        return $this->memo->getAmountToRedeem();
    }

    public function setAmountRedeemed($amount)
    {
        $this->memo->setAmountRedeemed($amount);
        return $this;
    }

    public function getAmountRedeemed()
    {
        return $this->memo->getAmountRedeemed();
    }

    public function setRedeemCurrencyCode($currencyCode)
    {
        $this->memo->setRedeemCurrencyCode($currencyCode);
        return $this;
    }

    public function getRedeemCurrencyCode()
    {
        // if no currency code has been set, default to the current store's currency code
        if (is_null($this->memo->getRedeemCurrencyCode())) {
            $this->memo->setRedeemCurrencyCode(Mage::app()->getStore()->getCurrentCurrencyCode());
        }
        return $this->memo->getRedeemCurrencyCode();
    }

    public function setBalanceAmount($amount)
    {
        $this->memo->setBalanceAmount($amount);
        return $this;
    }

    public function getBalanceAmount()
    {
        return $this->memo->getBalanceAmount();
    }

    public function setBalanceCurrencyCode($currencyCode)
    {
        $this->memo->setBalanceCurrencyCode($currencyCode);
        return $this;
    }

    public function getBalanceCurrencyCode()
    {
        // if no currency code has been set, default to the current store's currency code
        if (is_null($this->memo->getBalanceCurrencyCode())) {
            $this->memo->setBalanceCurrencyCode(Mage::app()->getStore()->getCurrentCurrencyCode());
        }
        return $this->memo->getBalanceCurrencyCode();
    }

    public function setIsRedeemed($isRedeemed)
    {
        $this->memo->setIsRedeemed($isRedeemed);
        return $this;
    }

    public function getIsRedeemed()
    {
        return $this->memo->getIsRedeemed();
    }

    public function setRedeemedAt(DateTime $redeemedAt)
    {
        $this->memo->setRedeemedAt($redeemedAt);
        return $this;
    }

    public function getRedeemedAt()
    {
        return $this->memo->getRedeemedAt();
    }

    /**
     * Restore data from an external memo to the gift card.
     *
     * Intended as a means of restoring state to the gift card from data
     * in the session or otherwise externalized.
     *
     * @param EbayEnterprise_GiftCard_Model_Giftcard_Memo
     * @return self
     */
    public function restoreFromMemo(EbayEnterprise_GiftCard_Model_Giftcard_Memo $memo)
    {
        $this->memo = $memo;
        return $this;
    }

    /**
     * Get the memo used to encapsulate gift card data for storage.
     *
     * @return EbayEnterprise_GiftCard_Model_Giftcard_Memo
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * Log the different requests consistently.
     *
     * @param string $type 'balance', 'redeem', 'void'
     * @param string $body the serialized xml body
     * @param string $direction 'request' or 'response'
     */
    protected function logApiCall($type, $direction)
    {
        $logData = ['type' => $type, 'direction' => $direction];
        $logMessage = 'Processing gift card {type} {direction}.';
        $this->logger->info($logMessage, $this->context->getMetaData(__CLASS__, $logData));
    }


    public function checkBalance()
    {
        $api = $this->getApi($this->config->apiOperationBalance);
        $this->prepareApiForBalanceCheck($api);
        $this->logApiCall('balance', 'request');
        $this->logStoredValuePayload($api, true, 'Sending StoredValueBalanceRequest.');
        $this->sendRequest($api);
        $this->logApiCall('balance', 'response');
        $this->logStoredValuePayload($api, false, 'Received StoredValueBalanceReply response.');
        $this->handleBalanceResponse($api);
        return $this;
    }

    public function redeem()
    {
        $api = $this->getApi($this->config->apiOperationRedeem);
        $this->prepareApiForRedeem($api);
        $this->logApiCall('redeem', 'request');
        $this->logStoredValuePayload($api, true, 'Sending StoredValueRedeemRequest.');
        $this->sendRequest($api);
        $this->logApiCall('redeem', 'response');
        $this->logStoredValuePayload($api, false, 'Received StoredValueRedeemReply response.');
        $this->handleRedeemResponse($api);
        return $this;
    }

    public function void()
    {
        $api = $this->getApi($this->config->apiOperationVoid);
        $this->prepareApiForVoid($api);
        $this->logApiCall('void', 'request');
        $this->logStoredValuePayload($api, true, 'Sending StoredValueRedeemVoidRequest.');
        $this->sendRequest($api);
        $this->logApiCall('void', 'response');
        $this->logStoredValuePayload($api, false, 'Received StoredValueRedeemVoidReply response.');
        $this->handleVoidResponse($api);
        return $this;
    }

    /**
     * Get a new SDK Api instance for an API call.
     * @param  string $operation
     * @return IBidirectionalApi
     */
    protected function getApi($operation)
    {
        return $this->coreHelper->getSdkApi(
            $this->config->apiService,
            $operation,
            [$this->getTenderType()],
            // Use a special logger just for the SDK logging, prevents
            // the SDK from logging any PII.
            $this->apiLogger
        );
    }

    /**
     * Prepare an API instance for a balance request - fill out and set the
     * request payload with gift card data.
     * @param  IBidirectionalApi $api
     * @return self
     */
    protected function prepareApiForBalanceCheck(IBidirectionalApi $api)
    {
        $this->setBalanceRequestId($this->coreHelper->generateRequestId(self::BALANCE_REQUST_ID_PREFIX));
        $payload = $api->getRequestBody();
        $payload
            ->setRequestId($this->getBalanceRequestId())
            ->setPin($this->getPin())
            ->setCurrencyCode($this->getBalanceCurrencyCode());
        $this->setPayloadAccountUniqueId($payload);
        $api->setRequestBody($payload);
        return $this;
    }

    /**
     * Prepare an API instance for a balance request - fill out and set the
     * request payload with gift card data.
     * @param  IBidirectionalApi $api
     * @return self
     */
    protected function prepareApiForRedeem(IBidirectionalApi $api)
    {
        $this->setRedeemRequestId($this->coreHelper->generateRequestId(self::REDEEM_REQUST_ID_PREFIX));
        $payload = $api->getRequestBody();
        $payload
            ->setRequestId($this->getRedeemRequestId())
            ->setPin($this->getPin())
            ->setAmount($this->getAmountToRedeem())
            ->setCurrencyCode($this->getRedeemCurrencyCode());
        $this->setPayloadPaymentContext($payload);
        $api->setRequestBody($payload);
        return $this;
    }

    /**
     * Prepare an API instance for a balance request - fill out and set the
     * request payload with gift card data.
     * @param  IBidirectionalApi $api
     * @return self
     */
    protected function prepareApiForVoid(IBidirectionalApi $api)
    {
        $this->setRedeemVoidRequestId($this->coreHelper->generateRequestId(self::VOID_REQUST_ID_PREFIX));
        $payload = $api->getRequestBody();
        $payload
            ->setRequestId($this->getRedeemVoidRequestId())
            ->setPin($this->getPin())
            ->setAmount($this->getAmountRedeemed())
            ->setCurrencyCode($this->getRedeemCurrencyCode());
        $this->setPayloadPaymentContext($payload);
        $api->setRequestBody($payload);
        return $this;
    }

    /**
     * Set the payment context on the payload - consists of an order id and
     * a paymend account unique id.
     * @param Payload\Payment\IPaymentContext $payload
     * @return self
     */
    protected function setPayloadPaymentContext(Payload\Payment\IPaymentContext $payload)
    {
        $payload->setOrderId($this->getOrderId());
        return $this->setPayloadAccountUniqueId($payload);
    }

    /**
     * Set payment account unique id fields on the payload.
     * @param Payload\Payment\IPaymentAccountUniqueId $payload
     * @return self
     */
    protected function setPayloadAccountUniqueId(Payload\Payment\IPaymentAccountUniqueId $payload)
    {
        $tokenizedCardNumber = $this->getTokenizedCardNumber();
        $hasTokenizedNumber = !is_null($tokenizedCardNumber);
        $payload
            ->setCardNumber($hasTokenizedNumber ? $tokenizedCardNumber : $this->getCardNumber())
            ->setPanIsToken($hasTokenizedNumber);
        return $this;
    }

    /**
     * Send the request via the SDK
     * @param  IBidirectionalApi $api
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If request cannot be made successfully
     */
    protected function sendRequest(IBidirectionalApi $api)
    {
        $logger = $this->logger;
        $logContext = $this->context;
        try {
            $api->send();
        } catch (InvalidPayload $e) {
            $logMessage = 'Invalid payload for stored value request. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw Mage::exception('EbayEnterprise_GiftCard', $this->helper->__(self::REQUEST_FAILED_MESSAGE));
        } catch (NetworkError $e) {
            $logMessage = 'Caught a network error sending stored value request. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw Mage::exception('EbayEnterprise_GiftCard_Exception_Network', $this->helper->__(self::REQUEST_FAILED_MESSAGE));
        } catch (UnsupportedOperation $e) {
            $logMessage = 'The stored value card operation is unsupported in the current configuration. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        } catch (UnsupportedHttpAction $e) {
            $logMessage = 'The stored value card operation is configured with an unsupported HTTP action. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        } catch (Exception $e) {
            $logMessage = 'Encountered unexpected exception from stored value card operation. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        return $this;
    }

    /**
     * Check for the balance response to be successful. If it was, update the
     * gift card with response data. If not, thrown an exception, indicating the
     * request failed.
     * @param  IBidirectionalApi $api
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If the reply is not successful.
     */
    protected function handleBalanceResponse(IBidirectionalApi $api)
    {
        $response = $api->getResponseBody();
        if (!$response->isSuccessful()) {
            throw Mage::exception('EbayEnterprise_GiftCard', self::BALANCE_REQUEST_FAILED_MESSAGE);
        }
        return $this->extractPayloadAccountUniqueId($response)
            ->setBalanceAmount($response->getBalanceAmount())
            ->setBalanceCurrencyCode($response->getCurrencyCode());
    }

    /**
     * Check for the gift card to have been redeemed. If it was, update the
     * gift card with response data. If not, thrown an exception, indicating the
     * request failed.
     * @param  IBidirectionalApi $api
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If the card was not redeemed
     */
    protected function handleRedeemResponse(IBidirectionalApi $api)
    {
        $response = $api->getResponseBody();
        if (!$response->wasRedeemed()) {
            // redeem failed so empty out any redeem amount
            $this->setAmountRedeemed(0.00);
            throw Mage::exception('EbayEnterprise_GiftCard', self::REDEEM_REQUEST_FAILED_MESSAGE);
        }
        return $this->extractPayloadAccountUniqueId($response)
            ->setBalanceAmount($response->getBalanceAmount())
            ->setBalanceCurrencyCode($response->getBalanceCurrencyCode())
            ->setAmountRedeemed($response->getAmountRedeemed())
            ->setRedeemCurrencyCode($response->getCurrencyCodeRedeemed())
            ->setRedeemedAt(new DateTime)
            ->setIsRedeemed(true);
    }

    /**
     * Check for the gift card redeem to have been voided. If it was, update the
     * gift card with response data. If not, thrown an exception, indicating the
     * request failed.
     * @param  IBidirectionalApi $api
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If the redeem was not voided
     */
    protected function handleVoidResponse(IBidirectionalApi $api)
    {
        $response = $api->getResponseBody();
        if (!$response->wasVoided()) {
            throw Mage::exception('EbayEnterprise_GiftCard', self::VOID_REQUEST_FAILED_MESSAGE);
        }
        return $this->extractPayloadAccountUniqueId($response)
            // after voiding a redemption, new balance will be the current
            // balance plus the amount voided (which is the same as the
            // amount originally redeemed)
            ->setBalanceAmount($this->getBalanceAmount() + $this->getAmountRedeemed())
            // redeem has been voided, so amount redeemed drops back to 0 and
            // card should no longer be considered to have been redeemed
            ->setAmountRedeemed(0.00)
            ->setIsRedeemed(false);
    }

    /**
     * Extract payment context form the payload - ignoring order id in the reply
     * as Magento is currently the master source for order ids when creating orders.
     * @param  Payload\Payment\IPaymentContext $payload
     * @return self
     */
    protected function extractPayloadPaymentContext(Payload\Payment\IPaymentContext $payload)
    {
        return $this->extractPayloadAccountUniqueId($payload);
    }

    /**
     * Update the gift card with account unique id data from the payload.
     * @param  Payload\Payment\IPaymentAccountUniqueId $payload
     * @return self
     */
    protected function extractPayloadAccountUniqueId(Payload\Payment\IPaymentAccountUniqueId $payload)
    {
        if ($payload->getPanIsToken()) {
            $this->setTokenizedCardNumber($payload->getCardNumber());
        } else {
            $this->setCardNumber($payload->getCardNumber());
        }
        return $this;
    }

    /**
     * Log request and response of stored value various service API calls.
     *
     * @param IBidirectionalApi
     * @param bool
     * @param string
     */
    protected function logStoredValuePayload(IBidirectionalApi $api, $isRequest, $logMessage)
    {
        /** @var string */
        $method = 'getRequestBody';
        /** @var string */
        $metaDataKey = 'rom_request_body';
        if (!$isRequest) {
            $method = 'getResponseBody';
            $metaDataKey = 'rom_response_body';
        }
        /** @var string */
        $cleanedXml = $this->mask->maskXmlNodes($api->$method()->serialize());
        $this->logger->debug($logMessage, $this->context->getMetaData(__CLASS__, [$metaDataKey => $cleanedXml]));
        return $this;
    }
}
