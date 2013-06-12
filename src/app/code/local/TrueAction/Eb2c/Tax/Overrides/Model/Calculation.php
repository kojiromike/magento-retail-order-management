<?php
/**
 * replacement for the magento tax caclulation model that uses the eb2c api to
 * determine the tax/duty rates.
 */
class TrueAction_Eb2c_Tax_Overrides_Model_Calculation extends Mage_Tax_Model_Calculation
{
	public function getRateRequest(
		$shippingAddress = null,
		$billingAddress = null,
		$customerTaxClass = null,
		$store = null
	) {
		return new TrueAction_Eb2c_Tax_Model_TaxDutyRequest(array(
			'shipping_address'   => $shippingAddress,
			'billing_address'    => $billingAddress,
			'customer_tax_class' => $customerTaxClass,
			'store'              => $store
		));
	}
}
