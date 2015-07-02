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

use eBayEnterprise\RetailOrderManagement\Api\HttpApi;
use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;

class EbayEnterprise_Inventory_Model_Details
{
    /** @var EbayEnterprise_Eb2cCore_Model_Session */
    protected $coreSession;
    /** @var EbayEnterprise_Inventory_Model_Session */
    protected $invSession;
    /** @var EbayEnterprise_Inventory_Model_Item_Selection_Interface */
    protected $selection;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Inventory_Helper_Details_Response */
    protected $responseHelper;
    /** @var EbayEnterprise_Inventory_Helper_Details_Factory */
    protected $factory;

    public function __construct(array $init = [])
    {
        list(
            $this->factory,
            $this->logger,
            $this->logContext,
            $this->selection,
            $this->coreHelper,
            $this->helper,
            $this->responseHelper,
        ) = $this->checkTypes(
            $this->nullCoalesce('factory', $init, Mage::helper('ebayenterprise_inventory/details_factory')),
            $this->nullCoalesce('logger', $init, Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce('logger_context', $init, Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce('selection', $init, Mage::helper('ebayenterprise_inventory/item_selection')),
            $this->nullCoalesce('core_helper', $init, Mage::helper('eb2ccore')),
            $this->nullCoalesce('helper', $init, Mage::helper('ebayenterprise_inventory')),
            $this->nullCoalesce('response_helper', $init, Mage::helper('ebayenterprise_inventory/details_response'))
        );
    }

    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Details_Factory $factory,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $loggerContext,
        EbayEnterprise_Inventory_Model_Item_Selection_Interface $selection,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_Inventory_Helper_Data $helper,
        EbayEnterprise_Inventory_Helper_Details_Response $responseHelper
    ) {
        return func_get_args();
    }

    /**
     * attempt to fetch fulfillment availaibility of the items
     * in the quote
     *
     * @param Mage_Sales_Model_Quote
     * @return EbayEnterprise_Inventory_Model_Details_Result
     */
    public function fetch(Mage_Sales_Model_Quote $quote)
    {
        $coreSession = $this->getCoreSession();
        $invSession = $this->getInventorySession();
        $result = $invSession->getInventoryDetailsResult();
        $isDetailsRequired = $coreSession->isDetailsUpdateRequired();
        if ($isDetailsRequired && $this->canFetchDetails($quote)) {
            $result = $this->tryOperation($quote);
            $invSession->setInventoryDetailsResult($result);
            // reset the flag to prevent running the request unnecessarily
            $coreSession->resetDetailsUpdateRequired();
        }
        return $result;
    }

    /**
     * retrieve the session to check for changes in the state of the
     * quote
     *
     * @return EbayEnterprise_Eb2cCore_Model_Session
     */
    protected function getCoreSession()
    {
        if (!$this->coreSession) {
            $this->coreSession = Mage::getSingleton('eb2ccore/session');
        }
        return $this->coreSession;
    }

    /**
     * Determine if there is enough data to send the request.
     *
     * @return bool
     */
    protected function canFetchDetails(Mage_Sales_Model_Quote $quote)
    {
        return $quote->getItemsCount()
            && $this->hasUsableAddress($quote);
    }

    /**
     * retrieve the session for storing the results
     *
     * @return EbayEnterprise_Eb2cCore_Model_Session
     */
    protected function getInventorySession()
    {
        if (!$this->invSession) {
            $this->invSession = Mage::getSingleton('ebayenterprise_inventory/session');
        }
        return $this->invSession;
    }

    /**
     * Fill in default values.
     *
     * @param  string
     * @param  array
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce($key, array $arr, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * attempt to do an inventory detail operation
     *
     * @param Mage_Sales_Model_Quote
     * @return EbayEnterprise_Inventory_Model_Details_Result
     */
    protected function tryOperation(Mage_Sales_Model_Quote $quote)
    {
        try {
            $api = $this->prepareApi();
            $request = $this->prepareRequest($api, $quote);
            if (count($request->getItems())) {
                $this->logger->debug(
                    'Trying inventory details operation',
                    $this->logContext->getMetaData(__CLASS__)
                );
                $api->send();
            }
            return $this->prepareResult($api);
        } catch (InvalidPayload $e) {
            $this->logger->warning(
                'Unable to perform inventory details operation due to an invalid payload.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (NetworkError $e) {
            $this->logger
                ->warning(
                    'Unable to perform inventory details operation due to a network error.',
                    $this->logContext->getMetaData(__CLASS__, [], $e)
                );
        } catch (UnsupportedOperation $e) {
            $this->logger
                ->critical(
                    'Inventory details operation is unsupported in current configuration.',
                    $this->logContext->getMetaData(__CLASS__, [], $e)
                );
        } catch (UnsupportedHttpAction $e) {
            $this->logger
                ->critical(
                    'Unable to perform inventory details operation due to protocol error.',
                    $this->logContext->getMetaData(__CLASS__, [], $e)
                );
        }
        // if we got here there was a problem
        throw Mage::exception(
            'EbayEnterprise_Inventory_Exception_Details_Operation',
            'Failed to fetch inventory details'
        );
    }

    /**
     * Get and configure the api
     */
    protected function prepareApi()
    {
        $config = $this->helper->getConfigModel();
        $api = $this->coreHelper->getSdkApi(
            $config->apiService,
            $config->apiDetailsOperation
        );
        return $api;
    }

    /**
     * fill out the request payload to send
     */
    protected function prepareRequest(HttpApi $api, Mage_Sales_Model_Quote $quote)
    {
        $request = $api->getRequestBody();
        $builder = $this->factory->createRequestBuilder($request);
        $alternateAddress = $this->getAlternateAddress($quote);
        foreach ($this->selectAddresses($quote) as $address) {
            $isAddressValid = $this->isValidPhysicalAddress($address);
            if (!$isAddressValid && !$alternateAddress) {
                continue;
            } elseif (!$isAddressValid) {
                $address = $alternateAddress;
            }
            $items = $this->selection->selectFrom($address->getAllItems());
            $builder->addItemPayloads($items, $address);
        }
        $api->setRequestBody($request);
        return $request;
    }

    /**
     * get the addresses to use for the request
     */
    protected function selectAddresses(Mage_Sales_Model_Quote $quote)
    {
        $addresses = $quote->getAllShippingAddresses();
        $addresses[] = $quote->getBillingAddress();
        return $addresses;
    }

    /**
     * attempt to get the customer's default shipping address if the
     * given address cannot be used. otherwise return the original
     * address
     *
     * @param Mage_Customer_Model_Address_Abstract
     * @param Mage_Customer_Model_Address_Abstract
     * @return Mage_Customer_Model_Address_Abstract|null
     */
    protected function getUsableAddress(
        Mage_Customer_Model_Address_Abstract $address,
        Mage_Customer_Model_Address_Abstract $alternateAddress = null
    ) {
        if ($this->isValidPhysicalAddress($address)) {
            return $address;
        } elseif ($alternateAddress && $this->isValidPhysicalAddress($alternateAddress)) {
            return $alternateAddress;
        }
        return null;
    }

    /**
     * get an alternate address to use if the address an item is attached to
     * does not have enough data for the payload
     *
     * @param Mage_Sales_Model_Quote
     * @return Mage_Customer_Model_Address_Abstract|null
     */
    protected function getAlternateAddress(Mage_Sales_Model_Quote $quote)
    {
        $address = $quote->getCustomer()->getDefaultShippingAddress();
        return $address ?: null;
    }

    /**
     * determine if the quote has a usable shipping address
     *
     * @param  Mage_Sales_Model_Quote
     * @return bool
     */
    protected function hasUsableAddress(Mage_Sales_Model_Quote $quote)
    {
        $usableAddress = $this->getUsableAddress(
            $quote->getShippingAddress(),
            $this->getAlternateAddress($quote)
        );
        return $usableAddress && $this->isValidPhysicalAddress($usableAddress);
    }

    /**
     * Check for the item to have shipping origin data set.
     *
     * @return bool
     */
    protected function isValidPhysicalAddress(Mage_Customer_Model_Address_Abstract $address)
    {
        return $address->getStreet1()
            && $address->getCity()
            && $address->getCountryId();
    }

    /**
     * process the data from the response into simpler objects
     *
     * @param HttpApi
     * @return EbayEnterprise_Inventory_Model_Details_Result
     */
    protected function prepareResult(HttpApi $api)
    {
        return $this->responseHelper->exportResultData($api->getResponseBody());
    }
}
