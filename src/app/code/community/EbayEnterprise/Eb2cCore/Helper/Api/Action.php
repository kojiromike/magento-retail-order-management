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

class EbayEnterprise_Eb2cCore_Helper_Api_Action
{
    const EBAY_ENTERPRISE_EB2CCORE_REQUEST_FAILED = 'EbayEnterprise_Eb2ccore_Request_Failed';
    /**
     * @throws EbayEnterprise_Eb2cCore_Exception_Critical always
     */
    public function throwException()
    {
        throw new EbayEnterprise_Eb2cCore_Exception_Critical();
    }
    /**
     * return an empty string.
     * @return string
     */
    public function returnEmpty()
    {
        return '';
    }
    /**
     * return the response body
     * @param  Zend_Http_Response $response
     * @return string
     */
    public function returnBody(Zend_Http_Response $response)
    {
        return $response->getBody();
    }
    /**
     * display a general notice in the cart when a request is configured to
     * fail loudly.
     * @return string
     */
    public function displayDefaultMessage()
    {
        Mage::getSingleton('core/session')->addError(self::EBAY_ENTERPRISE_EB2CCORE_REQUEST_FAILED);
        return '';
    }
}
