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

class EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController extends Mage_Adminhtml_Controller_Action
{
    const HOSTNAME_PARAM = 'hostname';
    const HOSTNAME_USE_DEFAULT_PARAM = 'hostname_use_default';
    const API_KEY_PARAM = 'api_key';
    const API_KEY_USE_DEFAULT_PARAM = 'api_key_use_default';
    const STORE_ID_PARAM = 'store_id';
    const STORE_ID_USE_DEFAULT_PARAM = 'store_id_use_default';

    const SFTP_HOSTNAME_PARAM = 'host';
    const SFTP_HOSTNAME_USE_DEFAULT_PARAM = 'hst_use_default';
    const SFTP_USERNAME_PARAM = 'username';
    const SFTP_USERNAME_USE_DEFAULT_PARAM = 'username_use_default';
    const SFTP_PRIV_KEY_PARAM = 'ssh_key';
    const SFTP_PRIV_KEY_USE_DEFAULT_PARAM = 'ssh_key_use_default';
    const SFTP_PORT_PARAM = 'port';
    const SFTP_PORT_USE_DEFAULT_PARAM = 'port_use_default';

    /** @var EbayEnterprise_Eb2cCore_Helper_Validator */
    protected $_validatorHelper;

    /**
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $initParams May contain:
     *                          - 'validator_helper' => EbayEnterprise_Eb2cCore_Helper_Validator
     */
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $initParams = array())
    {
        parent::__construct($request, $response, $initParams);
        list($this->_validatorHelper) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'validator_helper', Mage::helper('eb2ccore/validator'))
        );
    }
    /**
     * Type checks for __construct's $initParams
     * @param EbayEnterprise_Eb2cCore_Helper_Validator $validatorHelper
     * @return mixed[]
     */
    protected function _checkTypes(EbayEnterprise_Eb2cCore_Helper_Validator $validatorHelper)
    {
        return array($validatorHelper);
    }
    /**
     * Get the value form the array if set, else the default value.
     * @param  mixed[] $arr
     * @param  string|int $field
     * @param  mixed $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }
    /**
     * Validate the API configuration by making a test address validation
     * request and ensuring the response that is returned is valid.
     * @return self
     */
    public function validateapiAction()
    {
        $request = $this->getRequest();

        $configMap = Mage::getModel('eb2ccore/config');
        $hostname = $this->_validatorHelper->getParamOrFallbackValue(
            $request,
            self::HOSTNAME_PARAM,
            self::HOSTNAME_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('api_hostname')
        );
        $storeId = $this->_validatorHelper->getParamOrFallbackValue(
            $request,
            self::STORE_ID_PARAM,
            self::STORE_ID_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('store_id')
        );
        $apiKey = $this->_validatorHelper->getEncryptedParamOrFallbackValue(
            $request,
            self::API_KEY_PARAM,
            self::API_KEY_USE_DEFAULT_PARAM,
            Mage::getSingleton('eb2ccore/config')->getPathForKey('api_key')
        );

        $this->getResponse()->setHeader('Content-Type', 'text/json')
            ->setBody(json_encode(Mage::helper('eb2ccore/validator')->testApiConnection($storeId, $apiKey, $hostname)));
        return $this;
    }
    /**
     * Validate the SFTP configurations and set the response body to a JSON
     * response including the success of the test connection and any messages to
     * be displayed to the user.
     * @return self
     */
    public function validatesftpAction()
    {
        $request = $this->getRequest();

        $configMap = Mage::getModel('eb2ccore/config');
        $host = $this->_validatorHelper->getParamOrFallbackValue(
            $request,
            self::SFTP_HOSTNAME_PARAM,
            self::SFTP_HOSTNAME_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('sftp_location')
        );
        $username = $this->_validatorHelper->getParamOrFallbackValue(
            $request,
            self::SFTP_USERNAME_PARAM,
            self::SFTP_USERNAME_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('sftp_username')
        );
        $port = $this->_validatorHelper->getParamOrFallbackValue(
            $request,
            self::SFTP_PORT_PARAM,
            self::SFTP_PORT_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('sftp_port')
        );
        $key = $this->_validatorHelper->getSftpPrivateKey(
            $request,
            self::SFTP_PRIV_KEY_PARAM,
            self::SFTP_PRIV_KEY_USE_DEFAULT_PARAM,
            Mage::getSingleton('eb2ccore/config')->getPathForKey('sftp_private_key')
        );

        $this->getResponse()->setHeader('Content-Type', 'text/json')
            ->setBody(json_encode(Mage::helper('eb2ccore/validator')->testSftpConnection($host, $username, $key, $port)));
        return $this;
    }
}
