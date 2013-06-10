<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Helper_Constants extends Mage_Core_Helper_Abstract
{
	const XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';
	const ENV = 'developer';
	const REGION = 'na';
	const VERSION = 'v1.10';
	const SERVICE = 'inventory';
	const OPT_QTY = 'quantity';
	const OPT_INV_DETAILS = 'inventoryDetails';
	const OPT_ALLOCATION = 'allocations/create';
	const URI_FROMAT = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	const RETURN_FORMAT = 'xml';

	const DEV_MODE = 'eb2c/inventory/developerMode';
	const DEV_MODE_QTY_API_URI = 'eb2c/inventory/quantityApiUri';
	const DEV_MODE_DETAIL_API_URI = 'eb2c/inventory/inventoryDetailUri';
	const DEV_MODE_ALLOCATION_API_URI = 'eb2c/inventory/allocationUri';
}
