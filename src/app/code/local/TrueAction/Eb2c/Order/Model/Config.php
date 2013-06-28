<?php
/**
 * @package    TrueAction_Eb2c
 */
class TrueAction_Eb2c_Order_Model_Config extends TrueAction_Eb2c_Core_Model_Config_Abstract
{
	protected $_configPaths = array(
		'developer_mode' => 'eb2c/order/developer_mode',
		'create_uri' => 'eb2c/order/create_uri',
		'cancel_uri' => 'eb2c/order/cancel_uri',
		'gsi_payments_enabled' => 'eb2c/order/gsi_payments_enabled',
	);
}
