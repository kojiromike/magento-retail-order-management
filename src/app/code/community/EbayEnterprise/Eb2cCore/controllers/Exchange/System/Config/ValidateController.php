<?php

class EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController
	extends Mage_Adminhtml_Controller_Action
{
	const HOSTNAME_PARAM = 'hostname';
	const HOSTNAME_USE_DEFAULT_PARAM = 'hostname_use_default';
	const API_KEY_PARAM = 'api_key';
	const API_KEY_USE_DEFAULT_PARAM = 'api_key_use_default';
	const STORE_ID_PARAM = 'store_id';
	const STORE_ID_USE_DEAFULT_PARAM = 'store_id_use_default';
	const API_HANDLER_PATH = 'eb2ccore/api/test_connection_status_handlers';
	const INVALID_SETTINGS_ERROR_MESSAGE = 'EbayEnterprise_Eb2cCore_Api_Validation_Invalid_Settings';
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
			$request, static::HOSTNAME_PARAM, static::HOSTNAME_USE_DEFAULT_PARAM, $configMap->getPathForKey('api_hostname')
		);
		$storeId = $this->_getParamOrFallbackValue(
			$request, static::STORE_ID_PARAM, static::STORE_ID_USE_DEAFULT_PARAM, $configMap->getPathForKey('store_id')
		);
		$apiKey = $this->_getApiKey($request);

		$this->getResponse()->setHeader('Content-Type', 'text/json')
			->setBody($this->_getValidationResponse($storeId, $apiKey, $hostname));
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
		$key = $request->getParam(static::API_KEY_PARAM);
		$useDefault = $request->getParam(static::API_KEY_USE_DEFAULT_PARAM);
		if (is_null($key) || preg_match('/^\*+$/', $key) || $useDefault) {
			$configSource = $this->_getConfigSource($request, $useDefault);
			$key = $configSource->getConfig(Mage::getSingleton('eb2ccore/config')->getPathForKey('api_key'));
			if ($configSource instanceof Mage_Core_Model_Website) {
				$key = Mage::helper('core')->decrypt($key);
			}
		}
		return trim($key);
	}
	/**
	 * Get the value from the request or via the config fallback.
	 * @param  Zend_Controller_Request_Abstract $request
	 * @param  string $param Name of the param that may contain the value
	 * @param  string $useFallbackParam  Name of the param indicating if the value should fallback
	 * @param  string $configKey Core config registry key to get a fallback value for
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
	 * @param boolean $useFallback Should the config value fallback to parent value
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
	/**
	 * Get the API test message by making a test request, using AdVal with a
	 * hard-coded address, and using the response to determine if the configuration
	 * is correct or not. Appropriate response messages for API response types
	 * handled by custom API status handlers.
	 * @param  string $storeId
	 * @param  string $apiKey
	 * @param  string $hostname
	 * @return string
	 */
	protected function _getValidationResponse($storeId, $apiKey, $hostname)
	{
		// use the Eb2cAddress config go with the address validation request to be made
		$config = Mage::helper('eb2caddress')->getConfigModel();
		try {
			$this->_validateSettings($storeId, $apiKey, $hostname);
		} catch (EbayEnterprise_Eb2cCore_Exception_Api_Configuration $e) {
			return json_encode(array(
				'message' => $e->getMessage(), 'success' => false
			));
		}
		// need to manually build the URI as the eb2ccore/helper method doesn't
		// allow specifying the store id and doesn't really make sense for it to
		$uri = $this->_getApiUri($hostname, $storeId, $config->apiMajorVersion, $config->apiMinorVersion);
		$xsd = $config->xsdFileAddressValidation;
		$adValRequest = $this->_buildTestRequest();

		$api = Mage::getModel('eb2ccore/api');
		$api->setStatusHandlerPath(static::API_HANDLER_PATH);
		$response = $api->request($adValRequest, $xsd, $uri, $api::DEFAULT_TIMEOUT, $api::DEFAULT_ADAPTER, null, $apiKey);
		return $response;
	}
	/**
	 * Get the URI of the API endpoint for the AdVal request using the provided
	 * hostname, store id, and version.
	 * @param  string $hostname
	 * @param  string $storeId
	 * @param  string $major
	 * @param  string $minor
	 * @return string
	 */
	private function _getApiUri($hostname, $storeId, $major, $minor)
	{
		return sprintf(EbayEnterprise_Eb2cCore_Helper_Data::URI_FORMAT,
			$hostname, $major, $minor, $storeId,
			EbayEnterprise_Eb2cAddress_Model_Validation_Request::API_SERVICE,
			EbayEnterprise_Eb2cAddress_Model_Validation_Request::API_OPERATION,
			'', 'xml'
		);
	}
	/**
	 * Create an address validation request message using a hardcoded address.
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _buildTestRequest()
	{
		$address = Mage::getModel('customer/address', array(
			'street' => array('935 1st Ave'), 'city' => 'King of Prussia', 'region_id' => '51',
			'country_id' => 'US', 'postcode' => '19406'
		));
		$request = Mage::getModel('eb2caddress/validation_request', array('address' => $address));
		return $request->getMessage();
	}
	/**
	 * Validate the store id, API key and hostname settings, ensuring that
	 * none are empty.
	 * @param  string $storeId
	 * @param  string $apiKey
	 * @param  string $hostname
	 * @return self
	 * @throws EbayEnterprise_Eb2cCore_Exception_Api_Configuration If any settings are empty
	 */
	protected function _validateSettings($storeId, $apiKey, $hostname)
	{
		$invalidSettings = array();
		if ($storeId === '') {
			$invalidSettings[] = 'Store Id';
		}
		if ($apiKey === '') {
			$invalidSettings[] = 'API Key';
		}
		if ($hostname === '') {
			$invalidSettings[] = 'API Hostname';
		}
		if (!empty($invalidSettings)) {
			throw new EbayEnterprise_Eb2cCore_Exception_Api_Configuration(
				Mage::helper('eb2ccore')->__(static::INVALID_SETTINGS_ERROR_MESSAGE, implode(', ', $invalidSettings))
			);
		}
		return $this;
	}
}
