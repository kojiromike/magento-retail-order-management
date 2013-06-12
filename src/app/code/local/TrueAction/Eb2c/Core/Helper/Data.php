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
		// setting default factory adapter to use socket just in case curl extension isn't install in the server
		// by default, curl will be used as the default adapter
		$client = new Varien_Http_Client($apiUri, array('adapter' => 'Zend_Http_Client_Adapter_Socket'));
		$client->setRawData($xmlDoc->saveXML())->setEncType('text/xml');
		$response = $client->request($method);
		$results = '';
		if ($response->isSuccessful()) {
			$results = $response->getBody();
		}
		return $results;
	}
}
