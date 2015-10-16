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

class EbayEnterprise_Inventory_Helper_Quantity_Sdk
{
    const INVENTORY_QUANTITY_FAILED_MESSAGE = 'EbayEnterprise_Inventory_Quantity_Request_Failed';

    /** @var EbayEnterprise_Inventory_Helper_Quantity_Factory */
    protected $_inventoryQuantityFactory;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $_inventoryHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_inventoryConfig;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_logContext;

    public function __construct()
    {
        $this->_inventoryQuantityFactory = Mage::helper('ebayenterprise_inventory/quantity_factory');
        $this->_inventoryHelper = Mage::helper('ebayenterprise_inventory');
        $this->_inventoryConfig = $this->_inventoryHelper->getConfigModel();
        $this->_coreHelper = Mage::helper('eb2ccore');
        $this->_logger = Mage::helper('ebayenterprise_magelog');
        $this->_logContext = Mage::helper('ebayenterprise_magelog/context');
    }

    /**
     * Make a quantity service request for the items.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Results
     */
    public function requestQuantityForItems(array $items)
    {
        $api = $this->_getSdkApi();
        return $this->_prepareRequest($api, $items)
            ->_sendApiRequest($api)
            ->_extractResponseResults($api, $items);
    }

    /**
     * Get an API object for the SDK to make the TDF request.
     *
     * @return IBidirectionalApi
     */
    protected function _getSdkApi()
    {
        return $this->_coreHelper->getSdkApi(
            $this->_inventoryConfig->apiService,
            $this->_inventoryConfig->quantityApiOperation
        );
    }

    /**
     * Prepare the API request with data from the quote - fill out and set
     * the request payload.
     *
     * @param IBidirectionalApi
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @return self
     */
    protected function _prepareRequest(IBidirectionalApi $api, array $items)
    {
        try {
            $requestBody = $api->getRequestBody();
        } catch (UnsupportedOperation $e) {
            // If the SDK cannot handle sending requests to the inventory/quantity
            // service operation but is expected to, the SDK is likely broken.
            // As this would fall into the "human intervention required"
            // category of errors, log crit the exception.
            $this->_logger->critical(
                'Inventory quantity service request unsupported by SDK.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            // Throw a more generic, expected exception to prevent
            // this from being a blocking failure.
            throw $this->_failQuantityCollection();
        }
        $quantityRequest = $this->_inventoryQuantityFactory
            ->createRequestBuilder($requestBody, $items)
            ->getRequest();
        $api->setRequestBody($quantityRequest);
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
        $logger = $this->_logger;
        $logContext = $this->_logContext;
        try {
            $api->send();
        // Generally, these catch statements will all add a log message for the
        // exception and throw a more generic exception that can be handled
        // (by Magento or the Inventory module) in such a way as to not block checkout.
        } catch (InvalidPayload $e) {
            $logger->warning(
                'Invalid payload for inventory quantity. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $this->_failQuantityCollection();
        } catch (NetworkError $e) {
            $logger->warning(
                'Caught network error getting inventory quantity. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $this->_failQuantityCollection();
        } catch (UnsupportedOperation $e) {
            $logger->critical(
                'The inventory quantity operation is unsupported in the current configuration. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $this->_failQuantityCollection();
        } catch (UnsupportedHttpAction $e) {
            $logger->critical(
                'Inventory quantity operation is configured with an unsupported HTTP action. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $this->_failQuantityCollection();
        } catch (Exception $e) {
            $logger->warning(
                'Inventory quantity operation failed with unexpected exception. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $this->_failQuantityCollection();
        }
        return $this;
    }

    /**
     * Extract quantity results from the API response body.
     *
     * @param IBidirectionalApi
     * @param Mage_Sales_Model_Order_Quote_Item[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Results
     */
    protected function _extractResponseResults(IBidirectionalApi $api, array $items)
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
                'Inventory quantity service response unsupported by SDK.',
                $this->_logContext->getMetaData(__CLASS__, [], $e)
            );
            throw $this->_failQuantityCollection();
        }
        $responseParser = $this->_inventoryQuantityFactory
            ->createResponseParser($responseBody);
        return $this->_inventoryQuantityFactory->createQuantityResults(
            $responseParser->getQuantityResults(),
            $items
        );
    }

    /**
     * Create a fairly generic exception for the inventory module indicating
     * that quantity collection via the SDK has failed.
     *
     * @return EbayEnterprise_Inventory_Exception_Quantity_Collector_Exception
     */
    protected function _failQuantityCollection()
    {
        return Mage::exception('EbayEnterprise_Inventory_Exception_Quantity_Collector', $this->_inventoryHelper->__(self::INVENTORY_QUANTITY_FAILED_MESSAGE));
    }
}
