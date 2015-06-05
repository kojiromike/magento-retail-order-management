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

/**
 * API response handler for testing the API connection. All methods should return
 * an array with a 'message' and 'success' flag.
 */
class EbayEnterprise_Eb2cCore_Helper_Api_Validator
{
    const INVALID_HOSTNAME = 'EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Hostname';
    const INVALID_STORE_ID = 'EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Store_Id';
    const INVALID_API_KEY = 'EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Api_Key';
    const NETWORK_TIMEOUT = 'EbayEnterprise_Eb2cCore_Api_Validator_Network_Timeout';
    const UNKNOWN_FAILURE = 'EbayEnterprise_Eb2cCore_Api_Validator_Unknown_Failure';
    const SUCCESS = 'EbayEnterprise_Eb2cCore_Api_Validator_Success';

    /**
     * Return the response data for responses with a completely invalid
     * hostname - no response at all.
     * @return array
     */
    public function returnInvalidHostnameResponse()
    {
        return array(
            'message' => Mage::helper('eb2ccore')->__(self::INVALID_HOSTNAME),
            'success' => false
        );
    }
    /**
     * Return the response data for unknown - 500 response or scenario in which
     * the cause of the failure cannot be determined - errors.
     * @return array
     */
    public function returnUnknownErrorResponse()
    {
        return array(
            'message' => Mage::helper('eb2ccore')->__(self::UNKNOWN_FAILURE),
            'success' => false
        );
    }
    /**
     * Return the response data for client errors - 4XX range errors.
     * @param  Zend_Http_Response $response
     * @return array
     */
    public function returnClientErrorResponse(Zend_Http_Response $response)
    {
        $status = $response->getStatus();
        switch ($status) {
            case 401:
                $message = self::INVALID_API_KEY;
                break;
            case 403:
                $message = self::INVALID_STORE_ID;
                break;
            case 408:
                $message = self::NETWORK_TIMEOUT;
                break;
            default:
                $message = self::UNKNOWN_FAILURE;
                break;
        }
        return array(
            'message' => Mage::helper('eb2ccore')->__($message), 'success' => false
        );
    }
    /**
     * Return the response data for successful requests.
     * @return array
     */
    public function returnSuccessResponse()
    {
        return array(
            'message' => Mage::helper('eb2ccore')->__(self::SUCCESS), 'success' => true
        );
    }
}
