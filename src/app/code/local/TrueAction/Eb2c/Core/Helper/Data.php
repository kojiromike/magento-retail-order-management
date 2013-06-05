<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Call the API.
	 *
	 * @param DOMDocument $xmlDoc The xml document to send in the request body
	 * @param string $apiUri The url of the request
	 * @param string $method The HTTP method of the request (only POST is supported right now)
	 *
	 * @return string The response from the server.
	 */
	public function apiCall(DOMDocument $xmlDoc, $apiUri, $method='POST')
	{
		$client = new Varien_Http_Client($apiUri);
		if (!function_exists('curl_init')) {
			// curl isn't install in the server, let's use Socket
			$client->setAdapter(new Zend_Http_Client_Adapter_Socket());
		}
		$client->setRawData($xmlDoc->saveXML(), 'text/xml');

		return $client->request($method);
	}
}
