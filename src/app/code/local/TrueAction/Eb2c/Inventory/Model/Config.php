<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Config extends TrueAction_Eb2c_Core_Model_Config_Abstract
{

	protected $_configPaths = array(
		'allocation_expired' => 'eb2c/inventory/allocation_expired',
		'developer_mode' => 'eb2c/inventory/developer_mode',
		'quantity_api_uri' => 'eb2c/inventory/quantity_api_uri',
		'inventory_detail_uri' => 'eb2c/inventory/inventory_detail_uri',
		'allocation_uri' => 'eb2c/inventory/allocation_uri',
		'rollback_allocation_uri' => 'eb2c/inventory/rollback_allocation_uri'
	);
}
