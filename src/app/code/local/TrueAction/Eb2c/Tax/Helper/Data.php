<?php
/**
 * replacement for the default magento tax helper.
 */
class TrueAction_Eb2c_Tax_Helper_Data extends Mage_Tax_Helper_Data
{
	protected static $_apiUrlFormat = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	protected $_env                 = 'developer';
	protected $_region              = 'na';
	protected $_version             = 'v1.10';
	protected $_service             = 'taxes';
	protected $_operation           = 'quote';
	protected $_responseFormat      = 'xml';

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

	public function sendRequest($request)
	{
		$doc = $request->getDocument();
		Mage::helper('eb2ccore')->apiCall($doc, $this->getApiUrl());
	}
}