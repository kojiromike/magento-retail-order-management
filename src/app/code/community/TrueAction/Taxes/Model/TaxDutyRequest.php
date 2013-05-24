<?php
class TrueAction_Taxes_Model_TaxDutyRequest
{
	protected static $_apiUrlFormat = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s%s.%s';
	protected $_env                 = 'developer';
	protected $_region              = 'na';
	protected $_version             = 'v1.10';
	protected $_service             = 'taxes';
	protected $_operation           = 'quote';
	protected $_responseFormat      = 'xml';

	protected function _construct()
	{
		$this->setApiUrl(sprintf(
			self::$_apiUrlFormat,
			$this->_env,
			$this->_region,
			$this->_version,
			$this->getStoreId(),

		));
	}
}