<?php

use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\TenderType\ILookupReply;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\TenderType\ILookupRequest;
use PSR\Log\NullLogger;

/**
 * performs gift card tender type lookups using the Retail Order Management
 * API
 *
 */
class EbayEnterprise_GiftCard_Model_Tendertype_Lookup
{
    /** @var string */
    protected $cardNumber;
    /** @var string */
    protected $currencyCode;
    /** @var string */
    protected $panIsToken;
    /** @var IBidirectionalApi */
    protected $api;
    /** @var string */
    protected $tenderClass;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_GiftCard_Model_Mask */
    protected $logMask;

    public function __construct(array $init = [])
    {
        list(
            $this->cardNumber,
            $this->currencyCode,
            $this->api,
            $this->panIsToken,
            $this->tenderClass,
            $this->logMask,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $init['card_number'],
            $init['currency_code'],
            $init['api'],
            $this->nullCoalesce($init, 'pan_is_token', false),
            $this->nullCoalesce($init, 'tender_class', ILookupRequest::CLASS_STOREDVALUE),
            $this->nullCoalesce($init, 'log_mask', Mage::getModel('ebayenterprise_giftcard/mask')),
            $this->nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($init, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    protected function checkTypes(
        $cardNumber,
        $currencyCode,
        IBidirectionalApi $api,
        $panIsToken,
        $tenderClass,
        EbayEnterprise_GiftCard_Model_Mask $logMask,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * fetch the tender type for the given card account
     *
     * @param string
     * @return string
     * @throws EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed_Exception
     *         if the tender type cannot be retrieved for the account
     */
    public function getTenderType()
    {
        try {
            $this->prepareApiForSend();
            $this->api->send();
            return $this->processResponse(
                $this->api->getResponseBody()
            );
        } catch (EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed_Exception $e) {
            $this->logger->error(
                'The service reported the tender type lookup as unsuccessful.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (InvalidPayload $e) {
            $this->logger->warning(
                'Either the request or the response for the tender type lookup contains invalid data.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (NetworkError $e) {
            $this->logger->warning(
                'There was a network error when attempting to fetch the tender type',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (UnsupportedOperation $e) {
            $this->logger->critical(
                'The tender type lookup operation is unsupported in the current configuration.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (UnsupportedHttpAction $e) {
            $this->logger->critical(
                'The tender type lookup is configured with an unsupported HTTP action',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        }
        // we only care if we were able to get the tender type or not, so
        // boil all errors down to a single exception
        throw $this->createUnsuccessfulOperationException();
    }

    protected function createUnsuccessfulOperationException()
    {
        return Mage::exception(
            'EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed',
            'Failed to lookup the tender type.'
        );
    }

    /**
     * extract the tender type string from the response
     *
     * @param ILookupReply
     * @return string
     * @throws EbayEnterprise_GiftCard_Exception_InvalidCardNumber_Exception
     *         if the response indicates the service was unable to get the tender
     *         type for the given account.
     */
    protected function processResponse(ILookupReply $response)
    {
        if (!$response->isSuccessful()) {
            throw $this->createLookupFailedException(
                $response->getResponseCode(),
                $response->getResponseMessage()
            );
        }
        return $response->getTenderType();
    }

    /**
     * create and exception for when we get a response with a
     * failure code.
     *
     * @param string
     * @param string
     * @return EbayEnterprise_GiftCard_Exception_InvalidCardNumber_Exception
     */
    protected function createLookupFailedException($responseCode, $responseMessage)
    {
        return Mage::exception(
            'EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed',
            "Tender type lookup responded with code $responseCode: $responseMessage"
        );
    }

    /**
     * prepare to send the request
     *
     * @param ILookupRequest
     * @param string
     * @param string
     * @return ILookupRequest
     */
    protected function prepareApiForSend()
    {
        $request = $this->api->getRequestBody();
        $this->buildOutRequest($request);
        $this->logRequest($request, 'Gift card tender type request');
        $this->api->setRequestBody($request);
        return $request;
    }

    /**
     * fill out the request
     *
     * @param ILookupRequest
     */
    protected function buildOutRequest(ILookupRequest $request)
    {
        $request->setCardNumber($this->cardNumber)
            ->setPanIsToken($this->panIsToken)
            ->setTenderClass($this->tenderClass)
            ->setCurrencyCode($this->currencyCode);
    }

    /**
     * Log the api call with sensitive information masked.
     *
     * @param ILookupRequest
     * @param string
     * @return self
     */
    protected function logRequest(ILookupRequest $request, $logMessage)
    {
        $metaData = [
            'rom_request_body' => $this->logMask->maskXmlNodes($request->serialize())
        ];
        $this->logger->debug(
            $logMessage,
            $this->logContext->getMetaData(__CLASS__, $metaData)
        );
        return $this;
    }
}
