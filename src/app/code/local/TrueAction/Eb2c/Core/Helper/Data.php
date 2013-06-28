<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Core_Helper_Data extends Mage_Core_Helper_Abstract
{

	/**
	 * Service URI has the following format:
	 * https://{env}-{rr}.gsipartners.com/v{M}.{m}/stores/{storeid}/{service}/{operation}{/parameters}.{format}
	 * - env - GSI Environment to access
	 * - rr - Geographic region - na, eu, ap
	 * - M - major version of the API
	 * - m - minor version of the API
	 * - storeid - GSI assigned store identifier
	 * - service - API call service/subject area
	 * - operation - specific API call of the specified service
	 * - parameters - optionally any parameters needed by the call
	 * - format - extension of the requested response format. Currently only xml is supported
	 */
	const URI_FORMAT = 'https://%s-%s.gsipartners.com/v%s.%s/stores/%s/%s/%s%s.%s';

	/**
	 * If timeout is not specified for callApi, and the configuration does not contain one, this our failsafe value.
	 * This value is taken from Zend_Http_Client's default. That class provides no way to 'get' this value. Due to
	 * the class implementation, a timeout of 0 will make @fread use the php directive 'default_socket_timeout'.
	 */
	const FAILSAFE_TIMEOUT = 10;

	/**
	 * Call the API.
	 *
	 * @param DOMDocument $xmlDoc The xml document to send in the request body
	 * @param string $apiUri The url of the request
	 * @param string $timeout in seconds, for the request to complete
	 * @param string $method The HTTP method of the request (only POST is supported right now)
	 *
	 * @return string The response from the server.
	 */
	public function callApi(DOMDocument $xmlDoc, $apiUri, $timeout=0, $method='POST')
	{
		if( !$timeout ) {
			$timeout = Mage::getModel('eb2ccore/config_registry')
				->addConfigModel(Mage::getSingleton('eb2ccore/config'))
				->apiTimeout;
			if( !$timeout ) {
				$timeout = self::FAILSAFE_TIMEOUT;
			}
		}
		// setting default factory adapter to use socket just in case curl extension isn't install in the server
		// by default, curl will be used as the default adapter
		$client = new Varien_Http_Client($apiUri, array(
			'adapter' => 'Zend_Http_Client_Adapter_Socket',
			'timeout' => $timeout,
			));
		$client->setRawData($xmlDoc->saveXML())->setEncType('text/xml');
		$response = $client->request($method);
		$results = '';
		if ($response->isSuccessful()) {
			$results = $response->getBody();
		}
		return $results;
	}

	/**
	 * Get the API URI for the given service/request.
	 * @param string $service
	 * @param string $operation
	 * @param array $params
	 * @param string $format
	 */
	public function getApiUri($service, $operation, $params = array(), $format = 'xml')
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		return sprintf(
			self::URI_FORMAT,
			$config->apiEnvironment,
			$config->apiRegion,
			$config->apiMajorVersion,
			$config->apiMinorVersion,
			$config->storeId,
			$service,
			$operation,
			(!empty($params)) ? '/' . implode('/', $params) : '',
			$format);
	}

}
