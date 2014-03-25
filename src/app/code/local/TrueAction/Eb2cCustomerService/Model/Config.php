<?php

class TrueAction_Eb2cCustomerService_Model_Config
	extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_xml_ns' => 'eb2ccore/customer_service/api/xml_ns',
		'csr_user' => 'eb2ccore/customer_service/csr_user',
		'is_csr_login_enabled' => 'eb2ccore/customer_service/enable_csr_tool',
		'xsd_file_token_validation' => 'eb2ccore/customer_service/api/xsd/file',
	);
}