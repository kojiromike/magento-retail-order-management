<?php
/**
 * represents an OrderItem.
 * requires the following data to be passed to the constructor
 * @param  int                            type
 * @param  TrueAction_Dom_Element         node
 */
class TrueAction_Eb2c_Tax_Model_Response_Quote extends Mage_Core_Model_Abstract
{
	const MERCHANDISE = 0;
	const SHIPPING    = 1;
	const DUTY        = 2;

	protected function _construct()
	{
		$tax = $this->getNode();
		if ($tax) {
			$xpath = new DOMXPath($tax->ownerDocument);
			$xpath->registerNamespace('a', $tax->namespaceURI);
			// get effective rate
			$this->setEffectiveRate((float)$xpath->evaluate('string(a:EffectiveRate)', $tax));
			// get taxable amount
			$this->setTaxableAmount((float)$xpath->evaluate('string(a:TaxableAmount)', $tax));
			// get taxexemptamunt
			$this->setExemptAmount((float)$xpath->evaluate('string(a:ExemptAmount)', $tax));
			// get nontaxableamount
			$this->setNonTaxableAmount((float)$xpath->evaluate('string(a:NonTaxableAmount)', $tax));
			// calculatedtax
			$this->setCalculatedTax((float)$xpath->evaluate('string(a:CalculatedTax)', $tax));
		}
	}
}