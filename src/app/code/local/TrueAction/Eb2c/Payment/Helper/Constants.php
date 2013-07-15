<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Payment_Helper_Constants extends Mage_Core_Helper_Abstract
{
	const XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';
	const SERVICE = 'payments';
	const OPT_STORED_VALUE_BALANCE = 'storevalue/balance/GS';
	const RETURN_FORMAT = 'xml';
}
