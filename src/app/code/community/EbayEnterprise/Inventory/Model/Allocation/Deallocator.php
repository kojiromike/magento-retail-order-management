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

class EbayEnterprise_Inventory_Model_Allocation_Deallocator
{
    /** @var EbayEnterprise_Inventory_Model_Session */
    protected $invSession;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Reservation */
    protected $reservation;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    public function __construct(array $init = [])
    {
        list(
            $this->coreHelper,
            $this->helper,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce('core_helper', $init, Mage::helper('eb2ccore')),
            $this->nullCoalesce('helper', $init, Mage::helper('ebayenterprise_inventory')),
            $this->nullCoalesce('logger', $init, Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce('log_context', $init, Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    protected function checkTypes(
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_Inventory_Helper_Data $helper,
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
     * attempt to undo an allocation
     *
     * @param EbayEnterprise_Inventory_Model_Allocation_Reservation
     */
    public function rollback(EbayEnterprise_Inventory_Model_Allocation_Reservation $reservation)
    {
        $api = $this->prepareApi();
        try {
            $this->prepareRequest($api, $reservation);
            $api->send();
            return;
        } catch (InvalidPayload $e) {
            $this->logger->warning(
                'The allocation rollback response payload is invalid.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (NetworkError $e) {
            $this->logger->warning(
                'Failed sending the allocation rollback request.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (UnsupportedOperation $e) {
            $this->logger->critical(
                'Allocation rollback is unsupported in the currently configured SDK.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        } catch (UnsupportedHttpAction $e) {
            $this->logger->critical(
                'Allocation rollback configured to use unsupported HTTP action.',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
        }
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
            $config->apiAllocationDeleteOperation
        );
        return $api;
    }

    /**
     * prepare the request to be sent
     *
     * return IBidirectionalApi
     */
    protected function prepareRequest(
        IBidirectionalApi $api,
        EbayEnterprise_Inventory_Model_Allocation_Reservation $reservation
    ) {
        $request = $api->getRequestBody();
        $request->setReservationId($reservation->getId())
            ->setRequestId(uniqid());
        $api->setRequestBody($request);
        return $this;
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
     * get the addresses to use for the request
     */
    protected function selectAddresses(Mage_Sales_Model_Quote $quote)
    {
        $addresses = $quote->getAllShippingAddresses();
        $addresses[] = $quote->getBillingAddress();
        return $addresses;
    }
}
