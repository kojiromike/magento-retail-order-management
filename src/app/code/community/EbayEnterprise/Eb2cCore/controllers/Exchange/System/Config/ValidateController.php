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

class EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController
	extends Mage_Adminhtml_Controller_Action
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
	/**
	 * Validate the API configuration by making a test address validation
	 * request and ensuring the response that is returned is valid.
	 * @return self
	 */
	public function validateapiAction()
	{
		$request = $this->getRequest();

		$configMap = Mage::getModel('eb2ccore/config');
		$hostname = $this->_getParamOrFallbackValue(
			$request, self::HOSTNAME_PARAM, self::HOSTNAME_USE_DEFAULT_PARAM, $configMap->getPathForKey('api_hostname')
		);
		$storeId = $this->_getParamOrFallbackValue(
			$request, self::STORE_ID_PARAM, self::STORE_ID_USE_DEFAULT_PARAM, $configMap->getPathForKey('store_id')
		);
		$apiKey = $this->_getApiKey($request);

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
		$host = $this->_getParamOrFallbackValue(
			$request, self::SFTP_HOSTNAME_PARAM, self::SFTP_HOSTNAME_USE_DEFAULT_PARAM, $configMap->getPathForKey('sftp_location')
		);
		$username = $this->_getParamOrFallbackValue(
			$request, self::SFTP_USERNAME_PARAM, self::SFTP_USERNAME_USE_DEFAULT_PARAM, $configMap->getPathForKey('sftp_username')
		);
		$key = $this->_getSftpPrivateKey($request);
		$port = $this->_getParamOrFallbackValue(
			$request, self::SFTP_PORT_PARAM, self::SFTP_PORT_USE_DEFAULT_PARAM, $configMap->getPathForKey('sftp_port')
		);
		$this->getResponse()->setHeader('Content-Type', 'text/json')
			->setBody(json_encode(Mage::helper('eb2ccore/validator')->testSftpConnection($host, $username, $key, $port)));
		return $this;
	}
	/**
	 * Get the API key from the request or config. Request special handling
	 * for checking for the obscured value and properly decrypting the value
	 * when coming from website config.
	 * @param  Zend_Controller_Request_Abstract $request
	 * @return string
	 */
	protected function _getApiKey(Zend_Controller_Request_Abstract $request)
	{
		$key = $request->getParam(self::API_KEY_PARAM);
		$useDefault = $request->getParam(self::API_KEY_USE_DEFAULT_PARAM);
		if (is_null($key) || preg_match('/^\*+$/', $key) || $useDefault) {
			$configSource = $this->_getConfigSource($request, $useDefault);
			$key = $configSource->getConfig(Mage::getSingleton('eb2ccore/config')->getPathForKey('api_key'));
			// As there is a config/default node with a backend_model set for this
			// config value, Mage_Core_Model_Store->getConfig will auto-decrypt the
			// value when the config source is a Mage_Core_Model_Store. When the
			// source is a Mage_Core_Model_Website, this doesn't happen automatically
			// so the config value needs to be manually decrypted.
			if ($configSource instanceof Mage_Core_Model_Website) {
				$key = Mage::helper('core')->decrypt($key);
			}
		}
		return trim($key);
	}
	/**
	 * Get the private key value from config or the request. When from config,
	 * should be decrypted before being returned.
	 * @param  Zend_Controller_Request_Abstract $request
	 * @return string
	 */
	protected function _getSftpPrivateKey(Zend_Controller_Request_Abstract $request)
	{
		$key = $request->getParam(self::SFTP_PRIV_KEY_PARAM);
		$useFallback = $request->getParam(self::SFTP_PRIV_KEY_USE_DEFAULT_PARAM);
		// As the private key isn't re-filled out in the admin forms, the empty
		// value needs to be treated as falling back to the configured value, else
		// there would be no way to test an already saved key.
		if (!$key || $useFallback) {
			$configSource = $this->_getConfigSource($request, $useFallback);
			$key = Mage::getSingleton('eb2ccore/config')->getPathForKey('sftp_private_key');
			// As there is not config/default node with a backend_model set for this
			// config node, it will never be auto-magically decrypted by stores.
			// Hence, it always needs to be decrypted here, no matter what the config
			// source is.
			return Mage::helper('core')->decrypt($configSource->getConfig($key));
		}
		return $key;
	}
	/**
	 * Get the value from the request or via the config fallback.
	 * @param  Zend_Controller_Request_Abstract $request
	 * @param  string $param Name of the param that may contain the value
	 * @param  string $useFallbackParam  Name of the param indicating if the value should fallback
	 * @param  string $configPath Core config registry key to get a fallback value for
	 * @return string
	 */
	protected function _getParamOrFallbackValue(Zend_Controller_Request_Abstract $request, $param, $useFallbackParam, $configPath)
	{
		$paramValue = $request->getParam($param);
		$useFallback = $request->getParam($useFallbackParam);

		if (is_null($paramValue) || $useFallback) {
			return $this->_getConfigSource($request, $useFallback)
				->getConfig($configPath);
		}
		return trim($paramValue);
	}
	/**
	 * Get the source of configuration for the request. Should use the store
	 * or website specified in the request params. If neither is present, should
	 * use the default store.
	 * @param Zend_Controller_Request_Abstract $request
	 * @param bool $useFallback Should the config value fallback to parent value
	 * @return Mage_Core_Model_Store|Mage_Core_Model_Website
	 */
	protected function _getConfigSource($request, $useFallback=false)
	{
		$store = $request->getParam('store');
		$website = $request->getParam('website');

		if ($store) {
			$storeObj = Mage::app()->getStore($store);
			// specific store view should fall back to website store view is in
			return $useFallback ? $storeObj->getWebsite() : $storeObj;
		}
		if ($website) {
			$websiteObj = Mage::app()->getWebsite($website);
			// website should fall back to default store
			return $useFallback ? Mage::app()->getStore(null) : $websiteObj;
		}
		// default to default store
		return Mage::app()->getStore(null);
	}
}
