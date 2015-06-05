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

class EbayEnterprise_Amqp_Exchange_System_Config_ValidateController extends Mage_Adminhtml_Controller_Action
{
    const USERNAME_PARAM = 'username';
    const USERNAME_USE_DEFAULT_PARAM = 'username_use_default';
    const PASSWORD_PARAM = 'password';
    const PASSWORD_USE_DEFAULT_PARAM = 'password_use_default';
    const HOSTNAME_PARAM = 'host';
    const HOSTNAME_USE_DEFAULT_PARAM = 'host_use_default';
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
     * @param EbayEnterprise_Amqp_Helper_Api_Validator
     * @return mixed[]
     */
    protected function _checkTypes(
        EbayEnterprise_Eb2cCore_Helper_Validator $validatorHelper
    ) {
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
     * Get a new API validator with the given store scope.
     * @param mixed $store Any valid store identifier
     * @return EbayEnterprise_Amqp_Model_Api_Validator
     */
    protected function _getAmqpApiValidator($store)
    {
        return Mage::getModel('ebayenterprise_amqp/api_validator', array('store' => $store));
    }
    /**
     * Test connecting to the AMQP server
     * @return self
     */
    public function validateamqpAction()
    {
        $request = $this->getRequest();

        $configMap = Mage::getModel('ebayenterprise_amqp/config');
        $hostname = $this->_validatorHelper->getParamOrFallbackValue(
            $request,
            self::HOSTNAME_PARAM,
            self::HOSTNAME_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('hostname')
        );
        $username = $this->_validatorHelper->getParamOrFallbackValue(
            $request,
            self::USERNAME_PARAM,
            self::USERNAME_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('username')
        );
        $password = $this->_validatorHelper->getEncryptedParamOrFallbackValue(
            $request,
            self::PASSWORD_PARAM,
            self::PASSWORD_USE_DEFAULT_PARAM,
            $configMap->getPathForKey('password')
        );

        $validator = $this->_getAmqpApiValidator($this->_getSourceStore());
        $this->getResponse()->setHeader('Content-Type', 'text/json')
            ->setBody(json_encode($validator->testConnection($hostname, $username, $password)));
        return $this;
    }
    /**
     * Get a store context to use as the source of configuration.
     * @return Mage_Core_Model_Store
     */
    protected function _getSourceStore()
    {
        // this may return a Mage_Core_Model_Store or a Mage_Core_Model_Website
        $configSource = $this->_validatorHelper->getConfigSource($this->getRequest());
        // When given a website, get the default store for that website.
        // AMQP config is only at global and website level, so no *current*
        // possibility for the default store to have a different value than the website.
        if ($configSource instanceof Mage_Core_Model_Website) {
            return $configSource->getDefaultStore();
        }
        return $configSource;
    }
}
