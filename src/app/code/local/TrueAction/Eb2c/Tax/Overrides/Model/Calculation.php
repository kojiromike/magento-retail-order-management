<?php
/**
 * replacement for the magento tax caclulation model that uses the eb2c api to
 * determine the tax/duty rates.
 */
class TrueAction_Eb2c_Tax_Overrides_Model_Calculation extends Mage_Tax_Model_Calculation
{
	/**
	 * generate a request object to use when calculating taxes and duties.
	 * @param  Mage_Sale_Model_Quote_Addres $shippingAddress
	 * @param  Mage_Sale_Model_Quote_Addres $billingAddress
	 * @param  string                       $customerTaxClass
	 * @param  int|Mage_Core_Model_Store    $store
	 * @return TrueAction_Eb2c_Tax_Model_TaxDutyRequest
	 */
	public function getRateRequest(
		Mage_Sale_Model_Quote_Addres $shippingAddress = null,
		Mage_Sale_Model_Quote_Addres $billingAddress = null,
		$customerTaxClass = '',
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
