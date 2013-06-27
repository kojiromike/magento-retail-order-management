<?php
/**
 * @package    TrueAction_Eb2c
 */
class TrueAction_Eb2c_Order_Model_Config extends TrueAction_Eb2c_Core_Model_Config_Abstract
{
	const EB2C_ORDER_PATH = 'eb2c/order/';

	protected $_configPaths = array(
		'developer_mode' => self:EB2C_ORDER_PATH . 'developer_mode';
		'create_uri' => self::EB2C_ORDER_PATH . 'create_uri',
		'cancel_uri' => self::EBC_ORDER_PATH . 'cancel_uri',
		'gsi_payments_enabled' => self::EBC_ORDER_PATH . 'gsi_payments_enabled',
	);
}
