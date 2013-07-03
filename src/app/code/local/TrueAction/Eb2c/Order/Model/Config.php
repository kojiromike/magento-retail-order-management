<?php
/**
 * @package    TrueAction_Eb2c
 */
class TrueAction_Eb2c_Order_Model_Config extends TrueAction_Eb2c_Core_Model_Config_Abstract
{
	protected $_configPaths = array(
		'developer_mode'		=> 'eb2c/order/developer_mode',
		'developer_create_uri'	=> 'eb2c/order/developer_create_uri',
		'developer_cancel_uri'	=> 'eb2c/order/developer_cancel_uri',
		'eb2c_payments_enabled'	=> 'eb2c/order/payments_enabled',
	);
}
