<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Core_Model_Api extends Mage_Core_Helper_Abstract
{
	private $_adapter = null;		// Set to self::DEFAULT_ADAPTER if not set by $this->setAdapter()
	private $_httpClient = null;	// new Varien_Http_Client, or set by $this->setHttpClient()
	private $_timeout = 0;			// 0 forces evaluation of config, or set by $this->setTimeout()
	private $_uri = null;			// Must be set by setUri(), or nothing much will happen

	/**
	 * If _timeout is not set via $this->setApiTimeout() for callApi(), and the configuration does not contain one, 
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
	 * Call the API.
	 *
	 * @param DOMDocument $doc The document to send in the request body
	 * @return string The response from the server.
	 */
	public function call(DOMDocument $doc)
	{
		// setting default factory adapter to use socket just in case curl extension isn't install in the server
		// by default, curl will be used as the default adapter
		$client = $this->_getHttpClient();
		$client->setUri($this->_getUri());
		$client->setConfig( array(
					'adapter' => $this->_getAdapter(),
					'timeout', $this->_getTimeout()
					));
		$client->setRawData($doc->saveXML())->setEncType('text/xml');
		$response = $client->request(self::DEFAULT_METHOD);
		$results = '';
		if ($response->isSuccessful()) {
			$results = $response->getBody();
		}
		return $results;
	}


	/**
	 * Sets the URI call() will pass to http client
	 *
	 */
	public function setUri( $uriIn )
	{
		$this->_uri = $uriIn;
		return $this;
	}

	/**
	 * Return current URI
	 */
	public function getUri()
	{
		return $this->_getUri();
	}

	/**
	 * Set the default timeout value for subsquent invocation of callApi().  *
	 */
	public function setTimeout( $timeoutIn ) 
	{
		$timeout = (int)$timeoutIn;
		if( !$timeout ) {
			$timeout = Mage::getModel('eb2ccore/config_registry')
				->addConfigModel(Mage::getSingleton('eb2ccore/config'))
				->apiTimeout;
			if( !$timeout ) {
				$timeout = self::DEFAULT_TIMEOUT;
			}
		}
		$this->_timeout = $timeout;
		return $this;
	}

	/**
	 * Public method that caller can use to see if timeout has been set at all
	 */
	public function getTimeout()
	{
		return $this->_getTimeout();
	}

	/**
	 * Set the http client if you do not wish to use the default.
	 */
	public function setHttpClient($clientIn)
	{
		if( !method_exists($clientIn, 'setUri')
			|| !method_exists($clientIn, 'setConfig')
			|| !method_exists($clientIn, 'setRawData')
			|| !method_exists($clientIn, 'request') )
		{
			Mage::throwException('Invalid Http_Client specified');
		// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		$this->_httpClient = $clientIn;
		return $this;
	}

	/**
	 * Gets the current Http Client
	 */
	public function getHttpClient()
	{
		return $this->_getHttpClient();
	}

	/**
	 * Set the Adapter used if you don't want the default
	 */
	public function setAdapter($adapterIn)
	{
		$this->_adapter = $adapterIn;
		return $this;
	}

	/**
	 * Current adapter in use
	 */
	public function getAdapter()
	{
		return $this->_getAdapter();
	}

	
	/**
	 * Private methods used by call() to get the adapter
	 */
	private function _getAdapter()
	{
		if( !$this->_adapter ) {
			$this->setAdapter(self::DEFAULT_ADAPTER);
		}
		return $this->_adapter;
	}

	/**
	 * Private methods used by call() to get the http client.
	 */
	private function _getHttpClient()
	{
		if( !$this->_httpClient ) {
			$this->setHttpClient(new Varien_Http_Client());
		}
		return $this->_httpClient;
	}


	/**
	 * Private method used by callApi to get the timeout value.
	 */
	private function _getTimeout()
	{
		if( !$this->_timeout ) {
			$this->setTimeout(0);
		}
		return $this->_timeout;
	}


	/** 
	 * Private method used by call() to set the URI
	 */
	private function _getUri()
	{
		return $this->_uri;
	}
}
