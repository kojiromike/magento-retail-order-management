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
class EbayEnterprise_Eb2cCore_Model_Api
{
	/**
	 * If _timeout is not set via $this->setApiTimeout() for request(), and the configuration does not contain one,
	 * this our default value.  This value is taken from Zend_Http_Client's default.
	 */
	const DEFAULT_TIMEOUT = 10;
	/**
	 * Default method is post. Cannot be changed.
	 */
	const DEFAULT_METHOD = 'POST';
	/**
	 * Default Adapter may be changed by $this->setAdapter()
	 */
	const DEFAULT_ADAPTER = 'Zend_Http_Client_Adapter_Socket';
	/**
	 * path to the default configuration for handling status codes.
	 */
	const DEFAULT_HANDLER_CONFIG = 'eb2ccore/api/default_status_handlers/silent';
	/**
	 * path to the default configuration for handling status codes loudly.
	 */
	const DEFAULT_LOUD_HANDLER_CONFIG = 'eb2ccore/api/default_status_handlers/loud';
	/**
	 * path handler config
	 * @var string
	 */
	protected $_statusHandlerPath = '';
	/**
	 * @var int the status of the last response.
	 */
	protected $_status = 0;
	/**
	 * Call the API.
	 *
	 * @param DOMDocument $doc The document to send in the request body
	 * @param string $xsdName The basename of the xsd file to validate $doc (The dirname is in config.xml)
	 * @param string $uri The uri to send the request to
	 * @param int $timeout The amount of time in seconds after which the connection is terminated
	 * @param string $adapter The classname of a Zend_Http_Client_Adapter
	 * @param Zend_Http_Client $client
	 * @return string The response from the server
	 */
	public function request(DOMDocument $doc, $xsdName, $uri, $timeout=self::DEFAULT_TIMEOUT, $adapter=self::DEFAULT_ADAPTER, Zend_Http_Client $client=null)
	{
		$log = Mage::helper('ebayenterprise_magelog');
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$log = Mage::helper('ebayenterprise_magelog');
		$xmlStr = $doc->C14N();
		$log->logInfo("[ %s ] Validating request using XSD '%s':\n%s", array(__CLASS__, $xsdName, $xmlStr));
		$this->schemaValidate($doc, $xsdName);
		$log->logInfo("[ %s ] Sending request to %s", array(__CLASS__, $uri));
		$client = $this->_setupClient($client, $cfg->apiKey, $uri, $xmlStr, $adapter, $timeout);
		try {
			$response = $client->request(self::DEFAULT_METHOD);
			return $this->_processResponse($response, $uri);
		} catch (Zend_Http_Client_Exception $e) {
			return $this->_processException($e, $uri);
		}
	}
	/**
	 * Instantiate (if necessary) and set up the http client.
	 *
	 * @param Zend_Http_Client $client
	 * @param string $apiKey for authentication
	 * @param string $uri
	 * @param string $data raw data to send
	 * @param string $adapter the classname of a Zend_Http_Client_Adapter
	 * @param int $timeout
	 * @return Zend_Http_Client
	 */
	protected function _setupClient(Zend_Http_Client $client=null, $apiKey, $uri, $data, $adapter, $timeout)
	{
		$client = $client ?: new Varien_Http_Client();
		$client
			->setHeaders('apiKey', $apiKey)
			->setUri($uri)
			->setRawData($data)
			->setEncType('text/xml')
			->setConfig(array(
				'adapter' => $adapter,
				'timeout' => $timeout,
			));
		return $client;
	}
	/**
	 * log the response and return the result of the configured callback.
	 *
	 * @param Zend_Http_Response $response
	 * @param string             $uri
	 * @return string response body or empty string
	 */
	protected function _processResponse(Zend_Http_Response $response, $uri)
	{
		$this->_status = $response->getStatus();
		$log = Mage::helper('ebayenterprise_magelog');
		$msgPat = "[%s] Received response from %s with status %s:\n%s";
		$msgArgs = array(__CLASS__, $uri, $this->_status, $response->asString());
		$config = $this->_getHandlerConfig($this->_getHandlerKey($response));
		$logMethod = isset($config['logger']) ? $config['logger'] : 'logDebug';
		$log->$logMethod($msgPat, $msgArgs);
		$callbackConfig = isset($config['callback']) ? $config['callback'] : array();
		$callbackConfig['parameters'] = array($response);
		return Mage::helper('eb2ccore')->invokeCallBack($callbackConfig);
	}
	/**
	 * @see self::_status
	 */
	public function getStatus()
	{
		return $this->_status;
	}
	/**
	 * Validates the DOM against a validation schema,
	 * which should be a full path to an .xsd for this DOMDocument.
	 *
	 * @param DomDocument $doc The document to validate
	 * @param string $xsdName xsd file basename with which to validate the doc
	 * @throws EbayEnterprise_Eb2cCore_Exception_InvalidXml when the schema is invalid
	 * @return self
	 */
	public function schemaValidate(DOMDocument $doc, $xsdName)
	{
		$errors = array();
		set_error_handler(function ($errno, $errstr) use (&$errors) {
			$errors[] = "'$errstr' [Errno $errno]";
		});
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$isValid = $doc->schemaValidate(Mage::getBaseDir() . DS . $cfg->apiXsdPath . DS . $xsdName);
		restore_error_handler();
		if (!$isValid) {
			$msg = sprintf(
				"[ %s ] Schema validation failed, encountering the following errors:\n%s",
				__CLASS__,
				implode("\n", $errors)
			);
			throw new EbayEnterprise_Eb2cCore_Exception_InvalidXml($msg);
		}
		return $this;
	}
	/**
	 * handle the exception thrown by the zend client.
	 * @param  Zend_Http_Client_Exception $exception
	 * @param  string                     $uri
	 * @return string
	 */
	protected function _processException(Zend_Http_Client_Exception $exception, $uri)
	{
		$this->_status = 0;
		$log = Mage::helper('ebayenterprise_magelog');
		$config = $this->_getHandlerConfig($this->_getHandlerKey());
		$logMethod = isset($config['logger']) ? $config['logger'] : 'logDebug';
		$log->$logMethod(
			'[ %s ] Failed to send request to %s; API client error: %s',
			array(__CLASS__, $uri, $exception)
		);
		$callbackConfig = isset($config['callback']) ? $config['callback'] : array();
		return Mage::helper('eb2ccore')->invokeCallBack($callbackConfig);
	}

	/**
	 * set the path to the config used when determining how to react a response.
	 *
	 * @param string $path
	 * @return self
	 */
	public function setStatusHandlerPath($path)
	{
		$this->_statusHandlerPath = $path;
		return $this;
	}
	/**
	 * @param  string $handlerKey
	 * @return array  the handler config mapped to the supplied status key.
	 */
	protected function _getHandlerConfig($handlerKey)
	{
		$path = $this->_statusHandlerPath ?: self::DEFAULT_HANDLER_CONFIG;
		$config = $this->_getMergedHandlerConfig(Mage::helper('eb2ccore')->getConfigData($path));
		return $config['status'][$handlerKey];
	}
	/**
	 * return a string that is a key to the handler config for the class of status codes.
	 * @param  Zend_Http_Response $response
	 * @return string
	 */
	protected function _getHandlerKey(Zend_Http_Response $response=null)
	{
		if ($response) {
			$code = $response->getStatus();
			if ($response->isSuccessful() || $response->isRedirect()) {
				return 'success';
			} elseif ($code < 500 && $code >= 400) {
				return 'client_error';
			} elseif ($code >= 500) {
				return 'server_error';
			}
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		return 'no_response';
	}
	/**
	 * return the merged contents of the default (silent) config and the loud config and the passed in array
	 * such that the loud config's values override the silent config's values and the passed config's values
	 * override the loud config's values.
	 * @param  array  $config
	 * @return array
	 */
	protected function _getMergedHandlerConfig(array $config=array())
	{
		$coreHelper = Mage::helper('eb2ccore');
		$defaultConfig = $coreHelper->getConfigData(self::DEFAULT_HANDLER_CONFIG);
		if (isset($config['alert_level']) && $config['alert_level'] === 'loud') {
			$defaultConfig = array_replace_recursive(
				$defaultConfig,
				$coreHelper->getConfigData(self::DEFAULT_LOUD_HANDLER_CONFIG)
			);
		}
		return array_replace_recursive($defaultConfig, $config);
	}
}
