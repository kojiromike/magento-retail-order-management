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
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAllocationReply;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAllocationRequest;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAllocatedItem;

class EbayEnterprise_Inventory_Model_Allocation_Allocator
{
    /** @var EbayEnterprise_Inventory_Model_Session */
    protected $invSession;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Reservation */
    protected $reservation;

    public function __construct(array $init = [])
    {
        list(
            $this->helper,
            $this->coreHelper,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce('helper', $init, Mage::helper('ebayenterprise_inventory')),
            $this->nullCoalesce('core_helper', $init, Mage::helper('eb2ccore')),
            $this->nullCoalesce('logger', $init, Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce('log_context', $init, Mage::helper('ebayenterprise_magelog/context'))
        );
        $this->reservation = Mage::getModel('ebayenterprise_inventory/allocation_reservation');
    }

    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Data $helper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $loggerContext
    ) {
        return func_get_args();
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
     * attempt to fetch fulfillment availability of the items
     * in the quote
     *
     * @param Mage_Sales_Model_Quote
     * @return EbayEnterprise_Inventory_Model_Allocation_Result|null Null if there are no items to allocate.
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Failure_Exception If the allocation fails.
     */
    public function reserveItemsForQuote(Mage_Sales_Model_Quote $quote)
    {
        try {
            $selector = $this->createItemSelector($quote);
            return $this->doOperation($selector);
        } catch (EbayEnterprise_Inventory_Exception_Allocation_Item_Selector_Exception $e) {
            $this->logger->debug(
                'Quote unsuitable for inventory allocation',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        }
        return null;
    }

    /**
     * create selection object that selects workable items from the quote.
     *
     * @param Mage_Sales_Model_Quote
     * @return EbayEnterprise_Inventory_Model_Allocation_Item_Selector
     */
    protected function createItemSelector(Mage_Sales_Model_Quote $quote)
    {
        return Mage::getModel(
            'ebayenterprise_inventory/allocation_item_selector',
            ['quote' => $quote]
        );
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
     * attempt to do an inventory allocation operation
     *
     * @param EbayEnterprise_Inventory_Model_Allocation_Item_Selector
     * @return EbayEnterprise_Inventory_Model_Allocation_Result
     */
    protected function doOperation(EbayEnterprise_Inventory_Model_Allocation_Item_Selector $selector)
    {
        $api = $this->prepareApi();
        return $this->prepareRequest($api, $selector)
            ->makeRequest($api)
            ->prepareResult($api);
    }

    /**
     * Make the API request, handling exceptions if they arise.
     *
     * @param IBidirectionalApi
     * @return self
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Failure_Exception If request fails.
     */
    protected function makeRequest(IBidirectionalApi $api)
    {
        $logger = $this->logger;
        $logContext = $this->logContext;

        try {
            $api->send();
            return $this;
        } catch (InvalidPayload $e) {
            $logger->warning(
                'Invalid payload for inventory allocation. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
        } catch (NetworkError $e) {
            $logger->warning(
                'Caught a network error sending the inventory allocation request. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
        } catch (UnsupportedOperation $e) {
            $logger->critical(
                'The inventory allocation operation is unsupported in the current configuration. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
        } catch (UnsupportedHttpAction $e) {
            $logger->critical(
                'The inventory allocation operation is configured with an unsupported HTTP action. See exception log for more details.',
                $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
        }
        $this->handleAllocationFailure();
    }

    /**
     * Throw an exception when an allocation fails.
     *
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Failure_Exception
     */
    protected function handleAllocationFailure()
    {
        throw Mage::exception(
            'EbayEnterprise_Inventory_Exception_Allocation_Failure',
            'Failed to allocate inventory'
        );
    }

    /**
     * configure and get the API
     *
     * @param string
     * @return IBidirectionalApi
     */
    public function prepareApi()
    {
        $config = $this->helper->getConfigModel();
        $api = $this->coreHelper->getSdkApi(
            $config->apiService,
            $config->apiAllocationCreateOperation
        );
        return $api;
    }

    /**
     * fill out the request payload to send
     *
     * @param IBidirectionalApi
     * @param EbayEnterprise_Inventory_Model_Allocation_Item_Selector
     * @return self
     */
    protected function prepareRequest(
        IBidirectionalApi $api,
        EbayEnterprise_Inventory_Model_Allocation_Item_Selector $selector
    ) {
        $this->logger->debug(
            'Building inventory allocation request reservation id {reservation_id}',
            $this->logContext->getMetaData(__CLASS__, ['reservation_id' => $this->reservation->getId()])
        );
        try {
            $request = $api->getRequestBody();
            $builder = $this->createRequestBuilder($request, $selector, $this->reservation);
            $builder->buildOutRequest();
            $api->setRequestBody($request);
            return $this;
        } catch (UnsupportedOperation $e) {
            $this->logger->critical(
                'The inventory allocation operation is unsupported in the current configuration. See exception log for more details.',
                $this->logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()])
            );
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
        }
        $this->handleAllocationFailure();
    }

    /**
     * create an object to build out the request
     *
     * @param IAllocationRequest
     * @param EbayEnterprise_Inventory_Model_Allocation_Item_Selector
     * @param EbayEnterprise_Inventory_Model_Allocation_Reservation
     * @return EbayEnterprise_Inventory_Model_Allocation_Creator_Request_Builder
     */
    protected function createRequestBuilder(
        IAllocationRequest $request,
        EbayEnterprise_Inventory_Model_Allocation_Item_Selector $selector,
        EbayEnterprise_Inventory_Model_Allocation_Reservation $reservation
    ) {
        return Mage::getModel(
            'ebayenterprise_inventory/allocation_creator_request_builder',
            ['request' => $request, 'selected_items' => $selector, 'reservation' => $reservation]
        );
    }

    /**
     * process the data from the response into simpler objects
     *
     * @param IBidirectionalApi
     * @return EbayEnterprise_Inventory_Model_Allocation_Result
     */
    protected function prepareResult(IBidirectionalApi $api)
    {
        $result = $this->exportResultData($api->getResponseBody());
        return $result;
    }

    /**
     * parse and store the data from the response into the session
     *
     * @param IAllocationReply
     * @return EbayEnterprise_Inventory_Model_Allocation_Result
     */
    public function exportResultData(IAllocationReply $reply)
    {
        return $this->buildResultFromReply($reply);
    }

    /**
     * build a result object with the data from the reply
     *
     * @param IAllocationReply
     * @return EbayEnterprise_Inventory_Model_Allocation_Result
     */
    protected function buildResultFromReply(IAllocationReply $reply)
    {
        return $this->createResult(
            $this->reservation,
            array_map(
                $this->createAllocationFactoryCallback(),
                iterator_to_array($reply->getAllocatedItems())
            )
        );
    }

    /**
     * factory method to build the model to contain allocation
     * results
     *
     * @param Reservation
     * @param array
     * @return
     */
    protected function createResult($reservation, array $allocations = [])
    {
        return Mage::getModel(
            'ebayenterprise_inventory/allocation_result',
            [
                'allocations' => $allocations,
                'reservation' => $reservation,
            ]
        );
    }

    /**
     * wrap the allocation model factory method in an anonymous function
     * to allow public invocation.
     *
     * @return callable
     */
    protected function createAllocationFactoryCallback()
    {
        return function ($payload) {
            return $this->createAllocationFromPayload($payload);
        };
    }

    /**
     * create an Allocation object from the payload.
     *
     * @param IAllocatedItem
     * @return EbayEnterprise_Inventory_Model_Allocation
     */
    protected function createAllocationFromPayload(IAllocatedItem $payload)
    {
        return [
            'item_id' => $payload->getLineId(),
            'sku' => $payload->getItemId(),
            'quantity_allocated' => $payload->getAmountAllocated(),
            'reservation' => $this->reservation
        ];
    }
}
