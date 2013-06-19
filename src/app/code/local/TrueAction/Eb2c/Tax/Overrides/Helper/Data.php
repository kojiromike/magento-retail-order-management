<?php
/**
 * replacement for the default magento tax helper.
 */
class TrueAction_Eb2c_Tax_Overrides_Helper_Data extends Mage_Tax_Helper_Data
{
	protected static $_apiUrlFormat = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	protected $_env                 = 'developer';
	protected $_region              = 'na';
	protected $_version             = 'v1.10';
	protected $_service             = 'taxes';
	protected $_operation           = 'quote';
	protected $_responseFormat      = 'xml';

	/**
	 * returns the completed uri.
	 * @return string
	 */
	public function getApiUrl()
	{
		return sprintf(
			self::$_apiUrlFormat,
			$this->_env,
			$this->_region,
			$this->_version,
			$this->getStoreId(),
			$this->_service,
			$this->_operation,
			$this->_responseFormat
		);
	}

	/**
	 * send a request and return the response
	 * @param  TrueAction_Eb2c_Tax_Model_Request $request
	 * @return TrueAction_Eb2c_Tax_Model_Response
	 */
	public function sendRequest(TrueAction_Eb2c_Tax_Model_Request $request)
	{
		$response = Mage::helper('eb2ccore')->apiCall(
			$request->getDocument(),
			$this->getApiUrl()
		);
		return new TrueAction_Eb2c_Tax_Model_Response(array('xml' => $response));
	}
}