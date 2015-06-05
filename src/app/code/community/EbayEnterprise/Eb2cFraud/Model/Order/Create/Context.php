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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderContext;

class EbayEnterprise_Eb2cFraud_Model_Order_Create_Context
{
    /** @var EbayEnterprise_Eb2cFraud_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_Eb2cFraud_Helper_Http */
    protected $_httpHelper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var Mage_Core_Model_Cookie */
    protected $_cookie;

    /**
     * inject dependencies
     * @param array
     */
    public function __construct(array $args = [])
    {
        list($this->_helper, $this->_httpHelper, $this->_coreHelper, $this->_cookie) =
            $this->_checkTypes(
                $this->_nullCoalesce('helper', $args, Mage::helper('eb2cfraud')),
                $this->_nullCoalesce('http_helper', $args, Mage::helper('eb2cfraud/http')),
                $this->_nullCoalesce('core_helper', $args, Mage::helper('eb2ccore')),
                $this->_nullCoalesce('cookie', $args, Mage::getSingleton('core/cookie'))
            );
    }

    /**
     * ensure correct types
     * @param  EbayEnterprise_Eb2cFraud_Helper_Data
     * @param  EbayEnterprise_Eb2cFraud_Helper_Http
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @param  Mage_Core_Model_Cookie
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Eb2cFraud_Helper_Data $helper,
        EbayEnterprise_Eb2cFraud_Helper_Http $httpHelper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        Mage_Core_Model_Cookie $cookie
    ) {
        return [$helper, $httpHelper, $coreHelper, $cookie];
    }

    /**
     * return $ar[$key] if it exists otherwise return $default
     * @param  string
     * @param  array
     * @param  mixed
     * @return mixed
     */
    protected function _nullCoalesce($key, array $ar, $default)
    {
        return isset($ar[$key]) ? $ar[$key] : $default;
    }

    /**
     * Add fraud context information to the order create request.
     * @param Mage_Sales_Model_Order
     * @param IOrderContext
     */
    public function addContextInfoToPayload(IOrderContext $orderContext)
    {
        $sessionInfo = new Varien_Object($this->_helper->getSessionInfo());
        $orderContext
            ->setHostname($this->_httpHelper->getRemoteHost())
            ->setIpAddress($this->_httpHelper->getRemoteAddr())
            ->setUserAgent($this->_httpHelper->getHttpUserAgent())
            ->setConnection($this->_httpHelper->getHttpConnection())
            ->setCookies($this->_httpHelper->getCookiesString())
            ->setJavascriptData($this->_httpHelper->getJavaScriptFraudData())
            ->setContentTypes($this->_httpHelper->getHttpAccept())
            ->setEncoding($this->_httpHelper->getHttpAcceptEncoding())
            ->setLanguage($this->_httpHelper->getHttpAcceptLanguage())
            ->setCharSet($this->_httpHelper->getHttpAcceptCharset())
            ->setTdlOrderTimestamp(new DateTime())
            // customer session data
            ->setSessionId($sessionInfo->getEncryptedSessionId())
            ->setReferrer($sessionInfo->getOrderSource())
            ->setTimeOnFile($sessionInfo->getTimeOnFile())
            ->setRtcTransactionResponseCode($sessionInfo->getRtcTransactionResponseCode())
            ->setRtcReasonCode($sessionInfo->getRtcReasonCode())
            ->setAuthorizationAttempts($sessionInfo->getAuthorizationAttempts());
        $this->_setTimeData($sessionInfo, $orderContext);
        return $this;
    }

    /**
     * conditional set time fields on the payload
     * @param Varien_Object
     * @param IOrderContext
     */
    protected function _setTimeData(Varien_Object $sessionInfo, IOrderContext $orderContext)
    {
        $lastLogin = $sessionInfo->getLastLogin();
        if ($lastLogin) {
            $orderContext->setLastLogin($lastLogin);
        }
        $timeSpentOnSite = $sessionInfo->getTimeSpentOnSite();
        if ($timeSpentOnSite) {
            $orderContext->setTimeSpentOnSite($timeSpentOnSite);
        }
    }
}
