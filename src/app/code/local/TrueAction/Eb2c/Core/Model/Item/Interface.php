<?php
/**
 * An interface to make item access consistent across various contexts including Orders, Quotes and Quote Addresses
 *
 */
interface TrueAction_Eb2c_Core_Model_Item_Interface
{
	public function getBySku();
}
