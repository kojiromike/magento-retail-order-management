<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	const EB2C_INVENTORY_XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';

	/**
	 * getXmlNs method
	 */
	public function getXmlNs()
	{
		return self::EB2C_INVENTORY_XMLNS;
	}
}
