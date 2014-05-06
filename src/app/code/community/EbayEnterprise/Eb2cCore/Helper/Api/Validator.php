<?php
/**
 * API response handler for testing the API connection. All methods should return
 * a JSON string with a message and success key.
 */
class EbayEnterprise_Eb2cCore_Helper_Api_Validator
{
	const INVALID_HOSTNAME = 'EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Hostname';
	const INVALID_STORE_ID = 'EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Store_Id';
	const INVALID_API_KEY = 'EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Api_Key';
	const NETWORK_TIMEOUT = 'EbayEnterprise_Eb2cCore_Api_Validator_Network_Timeout';
	const UNKNOWN_FAILURE = 'EbayEnterprise_Eb2cCore_Api_Validator_Unknown_Failure';
	const SUCCESS = 'EbayEnterprise_Eb2cCore_Api_Validator_Success';

	/**
	 * Return the JSON API test response for responses with a completely invalid
	 * hostname - no response at all.
	 * @return string
	 */
	public function returnInvalidHostnameResponse()
	{
		return json_encode(array(
			'message' => Mage::helper('eb2ccore')->__(static::INVALID_HOSTNAME),
			'success' => false
		));
	}
	/**
	 * Return the JSON response for unknown - 500 response or scenario in which
	 * the cause of the failure cannot be determined - errors.
	 * @return string
	 */
	public function returnUnknownErrorResponse()
	{
		return json_encode(array(
			'message' => Mage::helper('eb2ccore')->__(static::UNKNOWN_FAILURE),
			'success' => false
		));
	}
	/**
	 * Return the JSON response for client errors - 4XX range errors.
	 * @param  Zend_Http_Response $response
	 * @return string
	 */
	public function returnClientErrorResponse(Zend_Http_Response $response)
	{
		$status = $response->getStatus();
		switch ($status) {
			case 401:
				$message = static::INVALID_API_KEY;
				break;
			case 403:
				$message = static::INVALID_STORE_ID;
				break;
			case 408:
				$message = static::NETWORK_TIMEOUT;
				break;
			default:
				$message = static::UNKNOWN_FAILURE;
				break;
		}
		return json_encode(array(
			'message' => Mage::helper('eb2ccore')->__($message), 'success' => false
		));
	}
	/**
	 * Return the JSON API test response for successful requests.
	 * @return string
	 */
	public function returnSuccessResponse()
	{
		return json_encode(array(
			'message' => Mage::helper('eb2ccore')->__(static::SUCCESS), 'success' => true
		));
	}
}
