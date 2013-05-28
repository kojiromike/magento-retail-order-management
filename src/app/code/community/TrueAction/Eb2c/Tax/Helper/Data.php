<?php
/**
 * replacement for the default magento tax helper.
 */
class TrueAction_Eb2c_Tax_Helper_Data extends Mage_Tax_Helper_Data
{
	public function getShippingPrice($price, $includingTax = null, $shippingAddress = null, $ctc = null, $store = null)
	{
		return 500.00;
	}
}