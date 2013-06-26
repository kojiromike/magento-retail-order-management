<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Overrides_Model_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
	/**
	 * Overriding Check if is possible subtract value from item qty
	 *
	 * @return bool
	 */
	public function canSubtractQty()
	{
		// disabling product stock decreasing
		return false;
	}
}
