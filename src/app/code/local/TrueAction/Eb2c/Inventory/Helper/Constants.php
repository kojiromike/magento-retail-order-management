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
	const OPT_QTY = 'quantity/get';
	const OPT_INV_DETAILS = 'details/get';
	const OPT_ALLOCATION = 'allocations/create';
	const OPT_ROLLBACK_ALLOCATION = 'allocations/delete';
	const URI_FROMAT = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	const RETURN_FORMAT = 'xml';
}
