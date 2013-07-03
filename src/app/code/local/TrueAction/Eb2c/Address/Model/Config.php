<?php

/**
 * Configuration model to be registered with the eb2c core config helper.
 */
class TrueAction_Eb2c_Address_Model_Config
	extends TrueAction_Eb2c_Core_Model_Config_Abstract
{
	protected $_configPaths = array(
		'max_address_suggestions' => 'eb2caddress/general/max_suggestions',
		'api_namespace' => 'eb2caddress/api/namespace_uri',
		'address_format' => 'eb2caddress/address_suggestion_templates/html'
	);
}
