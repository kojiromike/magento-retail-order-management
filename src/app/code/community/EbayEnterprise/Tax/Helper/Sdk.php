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
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use eBayEnterprise\RetailOrderManagement\Payload\TaxDutyFee\ITaxDutyFeeQuoteRequest;
use eBayEnterprise\RetailOrderManagement\Payload\TaxDutyFee\ITaxDutyFeeQuoteResponse;

/**
 * Helper to take a quote object and make the TDF service call to get applicable
 * taxes.
 */
class EbayEnterprise_Tax_Helper_Sdk
{
    const TAX_FAILED_MESSAGE = 'EbayEnterprise_Tax_Request_Failed';

    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_Tax_Helper_Data */
    protected $_taxHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_taxConfig;
    /** @var EbayEnterprise_Tax_Helper_Factory */
    protected $_taxFactory;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EBayEnterprise_MageLog_Helper_Context */
    protected $_logContext;

    public function __construct()
    {
        $this->_coreHelper = Mage::helper('eb2ccore');
        $this->_taxHelper = Mage::helper('ebayenterprise_tax');
        $this->_taxConfig = $this->_taxHelper->getConfigModel();
        $this->_taxFactory = Mage::helper('ebayenterprise_tax/factory');
        $this->_logger = Mage::helper('ebayenterprise_magelog');
        $this->_logContext = Mage::helper('ebayenterprise_magelog/context');
    }

    /**
     * Make an API request to the TDF service for the quote and return any
     * tax records in the response.
     *
     * @param Mage_Sales_Model_Order_Quote
     * @return EbayEnterprise_Tax_Model_Result
     * @throws EbayEnterprise_Tax_Exception_Collector_Exception If tax records could not be collected.
     */
    public function requestTaxesForQuote(Mage_Sales_Model_Quote $quote)
    {
        $api = $this->_getSdkApi();
        return $this->_prepareRequest($api, $quote)
            ->_sendApiRequest($api)
            ->_extractResponseResults($api, $quote);
    }

    /**
     * Get an API object for the SDK to make the TDF request.
     *
     * @return IBidirectionalApi
     */
    protected function _getSdkApi()
    {
        return $this->_coreHelper->getSdkApi(
            $this->_taxConfig->apiService,
            $this->_taxConfig->apiOperation
        );
    }

    /**
     * Prepare the API request with data from the quote - fill out and set
     * the request payload.
     *
     * @param IBidirectionalApi
     * @param Mage_Sales_Model_Order_Quote
     * @return self
     */
    protected function _prepareRequest(IBidirectionalApi $api, Mage_Sales_Model_Quote $quote)
    {
        try {
            $requestBody = $api->getRequestBody();
        } catch (UnsupportedOperation $e) {
            // If the SDK cannot handle sending requests to the tax/quote
            // service operation but is expected to, the SDK is likely broken.
            // As this would fall into the "human intervention required"
            // category of errors, log crit the exception.
            $this->_logger->critical(
                'Tax quote service request unsupported by SDK.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            // Throw a more generic, expected exception to prevent
            // this from being a blocking failure.
            throw $this->_failTaxCollection();
        }
        $taxRequest = $this->_taxFactory
            ->createRequestBuilderQuote($requestBody, $quote)
            ->getTaxRequest();
        $api->setRequestBody($taxRequest);
        return $this;
    }

    /**
     * Send the request for the TDF service and handle any responses or exceptions.
     *
     * @param IBidirectionalApi
     * @return self
     */
    protected function _sendApiRequest(IBidirectionalApi $api)
    {
        try {
            $api->send();
        // Generally, these catch statements will all add a log message for the
        // exception and throw a more generic exception that can be handled
        // (by Magento or the Tax module) in such a way as to not block checkout.
        } catch (NetworkError $e) {
            $this->_logger->warning(
                'Caught network error getting taxes, duties and fees. Will retry during next total collection.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            throw $this->_failTaxCollection();
        } catch (InvalidPayload $e) {
            $this->_logger->warning(
                'Tax request payload is invalid.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            throw $this->_failTaxCollection();
        } catch (UnsupportedOperation $e) {
            $this->_logger->critical(
                'Tax quote service response unsupported by SDK.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            throw $this->_failTaxCollection();
        } catch (UnsupportedHttpAction $e) {
            $this->_logger->critical(
                'Tax quote operation failed due to unsupported HTTP action in the SDK.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            throw $this->_failTaxCollection();
        } catch (Exception $e) {
            $this->_logger->warning(
                'Encountered unexepcted error attempting to request tax data. See the exception log.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            throw $this->_failTaxCollection();
        }
        return $this;
    }

    /**
     * Extract tax records from the API response body for the quote.
     *
     * @param IBidirectionalApi
     * @param Mage_Sales_Model_Order_Quote
     * @return EbayEnterprise_Tax_Model_Result
     */
    protected function _extractResponseResults(IBidirectionalApi $api, Mage_Sales_Model_Quote $quote)
    {
        try {
            $responseBody = $api->getResponseBody();
        } catch (UnsupportedOperation $e) {
            // This exception handling is probably not necessary but
            // is technically possible. If the sdk flow of
            // getRequest->setRequest->send->getResponse is followed,
            // which is is by the one public method of this class, this
            // exception should never be thrown in this instance. If it
            // were to be thrown at all by the SDK, it would have already
            // happened during the "send" step.
            $this->_logger->critical(
                'Tax quote service response unsupported by SDK.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            throw $this->_failTaxCollection();
        }
        $responseParser = $this->_taxFactory
            ->createResponseQuoteParser($responseBody, $quote);
        return $this->_taxFactory->createTaxResults(
            $responseParser->getTaxRecords(),
            $responseParser->getTaxDuties(),
            $responseParser->getTaxFees()
        );
    }

    /**
     * Create a fairly generic exception for the tax module indicating
     * that tax collection via the SDK has failed.
     *
     * @return EbayEnterprise_Tax_Exception_Collector_Exception
     */
    protected function _failTaxCollection()
    {
        return Mage::exception('EbayEnterprise_Tax_Exception_Collector', $this->_taxHelper->__(self::TAX_FAILED_MESSAGE));
    }
}
