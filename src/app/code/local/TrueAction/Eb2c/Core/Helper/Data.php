<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * take a request, api url, default signature 'post' and soap curl request to eb2c webservice.
	 *
	 * @param $request - TrueAction_Dom_Document
	 * @param $apiUri - string
	 * @param $signature - string
	 *
	 * @return $reponse - xml
	 */
	public function apiCall(TrueAction_Dom_Document $request, $apiUri, $signature='POST')
	{
		$client = new Varien_Http_Client($apiUri);
		if (!function_exists('curl_init')) {
			// curl isn't install in the server, let's use Socket
			$client->setAdapter(new Zend_Http_Client_Adapter_Socket());
		}
		$client->setRawData($request->saveXML(), 'text/xml');

		return $client->request($signature);
	}
}
