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
			$jurisdiction  = $xpath->evaluate('string(a:Jurisdiction)', $tax);
			$imposition    = $xpath->evaluate('string(a:Imposition)', $tax);
			$effectiveRate = $xpath->evaluate('string(a:EffectiveRate)', $tax);
			$rateKey       = $jurisdiction && $imposition ?
				$jurisdiction . '-' . $imposition :
				$effectiveRate;
			$this->setRateKey($rateKey);
			// get situs
			$this->setSitus($xpath->evaluate('string(a:Situs)', $tax));
			// get effective rate
			$this->setEffectiveRate((float)$effectiveRate);
			// get taxable amount
			$this->setTaxableAmount((float)$xpath->evaluate('string(a:TaxableAmount)', $tax));
			// calculatedtax
			$this->setCalculatedTax((float)$xpath->evaluate('string(a:CalculatedTax)', $tax));
		}
	}
}