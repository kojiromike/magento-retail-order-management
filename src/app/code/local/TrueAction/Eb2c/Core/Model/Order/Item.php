<?php
/**
 * Mage_Sales_Order_Item extended for Eb2c.
 *
 */
class TrueAction_Eb2c_Core_Model_Order_Item extends Mage_Sales_Model_Order_Item implements TrueAction_Eb2c_Core_Model_Item_Interface
{
	/**
	 * Return a dummy value to prove configuration, code and implementation skeleton are working.
	 */
	public function getBySku()
	{
		return 'All this does is prove this framework exists and can implement the Interface\n';
	}
}
