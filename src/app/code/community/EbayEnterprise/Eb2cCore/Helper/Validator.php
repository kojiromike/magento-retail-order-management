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


class EbayEnterprise_Eb2cCore_Helper_Validator
{
	const API_HANDLER_PATH = 'eb2ccore/api/test_connection_status_handlers';
	const INVALID_API_SETTINGS_ERROR_MESSAGE = 'EbayEnterprise_Eb2cCore_Api_Validation_Invalid_Settings';

	const INVALID_SFTP_SETTINGS_ERROR_MESSAGE = 'EbayEnterprise_Eb2cCore_Sftp_Validation_Invalid_Settings';
	const INVALID_SFTP_CONNECTION = 'EbayEnterprise_Eb2cCore_Sftp_Invalid_Connection';
	const INVALID_SFTP_AUTHENTICATION_CONFIG = 'EbayEnterprise_Eb2cCore_Sftp_Invalid_Authentication_Configuration';
	const SFTP_CONNECTION_SUCCESS = 'EbayEnterprise_Eb2cCore_Sftp_Conection_Success';
	const SFTP_CONNECTION_FAILED = 'EbayEnterprise_Eb2cCore_Sftp_Connection_Failed';
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
	public function testApiConnection($storeId, $apiKey, $hostname)
	{
		// use the Eb2cAddress config go with the address validation request to be made
		$config = Mage::helper('eb2caddress')->getConfigModel();
		try {
			$this->_validateApiSettings($storeId, $apiKey, $hostname);
		} catch (EbayEnterprise_Eb2cCore_Exception_Api_Configuration $e) {
			return array(
				'message' => $e->getMessage(), 'success' => false
			);
		}
		// need to manually build the URI as the eb2ccore/helper method doesn't
		// allow specifying the store id and doesn't really make sense for it to
		$uri = $this->_getApiUri($hostname, $storeId, $config->apiMajorVersion, $config->apiMinorVersion);
		$xsd = $config->xsdFileAddressValidation;
		$adValRequest = $this->_buildTestRequest();

		$api = Mage::getModel('eb2ccore/api');
		$api->setStatusHandlerPath(self::API_HANDLER_PATH);
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
	 * Validate the store id, API key and hostname settings, ensuring that
	 * none are empty.
	 * @param  string $storeId
	 * @param  string $apiKey
	 * @param  string $hostname
	 * @return self
	 * @throws EbayEnterprise_Eb2cCore_Exception_Api_Configuration If any settings are empty
	 */
	protected function _validateApiSettings($storeId, $apiKey, $hostname)
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
				Mage::helper('eb2ccore')->__(self::INVALID_API_SETTINGS_ERROR_MESSAGE, implode(', ', $invalidSettings))
			);
		}
		return $this;
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
	 * @param  string $host
	 * @param  string $username
	 * @param  string $key
	 * @param  int $port
	 * @return self
	 * @throws EbayEnterprise_Eb2cCore_Exception_Api_Configuration If any settings are empty
	 */
	protected function _validateSftpSettings($host, $username, $key, $port)
	{
		$invalidSettings = array();
		if ($host === '') {
			$invalidSettings[] = 'Remote Host';
		}
		if ($username === '') {
			$invalidSettings[] = 'SFTP User Name';
		}
		if ($key === '') {
			$invalidSettings[] = 'Private Key';
		}
		// valid port would be int between 1 and 65535
		if ((int) $port < 1 || (int) $port > 65535) {
			$invalidSettings[] = 'Remote Port';
		}
		if (!empty($invalidSettings)) {
			throw new EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration(
				Mage::helper('eb2ccore')->__(self::INVALID_SFTP_SETTINGS_ERROR_MESSAGE, implode(', ', $invalidSettings))
			);
		}
		return $this;
	}
	/**
	 * Validate the SFTP configuration by first checking for any obviously invalid
	 * settings - e.g. missing/empty values - then by making a test connection
	 * using the given credential.
	 * @param string $host
	 * @param string $username
	 * @param string $privateKey
	 * @param int $port
	 *
	 * Ignore UnusedLocalVariable warning from the anon-function used as the
	 * error handler callback.
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function testSftpConnection($host, $username, $privateKey, $port)
	{
		try {
			$this->_validateSftpSettings($host, $username, $privateKey, $port);
		} catch (EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration $e) {
			return array('message' => $e->getMessage(), 'success' => false);
		}
		$configMap = Mage::getSingleton('eb2ccore/config');
		$sftp = Mage::helper('filetransfer')->getProtocolModel($configMap->getPathForKey('sftp_config'));
		$sftp->getConfigModel()->setHost($host)
			->setUsername($username)
			->setPrivateKey($privateKey)
			->setPort($port);
		$helper = Mage::helper('eb2ccore');
		$resp = array();
		// When the Net_SFTP instance failes to connect, it will trigger an error
		// instead of throwing an exception. Convert the error to an exception
		// so it can halt execution and be caught.
		set_error_handler(function ($errno, $errstr) {
			throw new EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration($errstr);
		}, E_USER_NOTICE);
		try {
			$sftp->connect()->login();
		} catch (EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration $e) {
			Mage::log($e->getMessage(), Zend_Log::INFO);
			$resp = array('message' => $helper->__(self::INVALID_SFTP_CONNECTION), 'success' => false);
		} catch (EbayEnterprise_FileTransfer_Exception_Authentication $e) {
			Mage::log($e->getMessage(), Zend_Log::INFO);
			$resp = array('message' => $helper->__(self::INVALID_SFTP_AUTHENTICATION_CONFIG), 'success' => false);
		}
		// Restore the error handler to get rid of the exception throwing one.
		restore_error_handler();
		// Final check, if the connection is logged in, everything went as planned
		// and the connection has been made. If not, and the reason for it has not
		// yet been dicovered by the exception handling above, just give a lame
		// indication that the connection could not be made.
		if ($sftp->isLoggedIn()) {
			$resp = array('message' => $helper->__(self::SFTP_CONNECTION_SUCCESS), 'success' => true);
		} elseif (empty($resp)) {
			$resp = array('message' => $helper->__(self::SFTP_CONNECTION_FAILED), 'success' => false);
		}
		return $resp;
	}
}
