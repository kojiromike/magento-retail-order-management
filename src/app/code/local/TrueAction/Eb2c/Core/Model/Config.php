<?php

class TrueAction_Eb2c_Core_Model_Config
	extends TrueAction_Eb2c_Core_Model_Config_Abstract
{

	protected $_configPaths = array(
		'catalog_id' => 'eb2ccore/general/catalog_id',
		'client_id' => 'eb2ccore/general/client_id',
		'store_id' => 'eb2ccore/general/store_id',
		'sftp_username' => 'eb2ccore/general/sftp_username',
		'sftp_password' => 'eb2ccore/general/sftp_password',
		'sftp_location' => 'eb2ccore/general/sftp_location',
		'api_key' => 'eb2ccore/general/api_key',
		'api_timeout' => 'eb2ccore/general/api_timeout',
		'test_mode' => 'eb2ccore/general/test_mode',
		'api_environment' => 'eb2ccore/api/environment',
		'api_region' => 'eb2ccore/api/region',
		'api_major_version' => 'eb2ccore/api/major_version',
		'api_minor_version' => 'eb2ccore/api/minor_version',
		'service_order_timeout' => 'eb2ccore/service/order/timeout',
		'service_payment_timeout' => 'eb2ccore/service/payment/timeout',
	);

}
