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
			$xpath->registerNamespace('a', $this->getNamespaceUri);
			// get effective rate
			$this->setEffeciveRate($xpath->evaluate('a:EffectiveRate/text()', $tax));
			// get taxable amount
			$this->setTaxableAmount($xpath->evaluate('a:TaxableAmount/text()', $tax));
			// get taxexemptamunt
			$this->setExemptAmount($xpath->evaluate('a:ExemptAmount/text()', $tax));
			// get nontaxableamount
			$this->setNonTaxableAmount($xpath->evaluate('a:NonTaxableAmount/text()', $tax));
			// calculatedtax
			$this->setCalculatedTax($xpath->evaluate('a:CalculatedTax/text()', $tax));
		}
	}
}