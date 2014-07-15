<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * represents a tax quote from the response.
 * requires the following data to be passed to the constructor
 * @param  int                        type
 * @param  EbayEnterprise_Dom_Element node
 * @param bool calculationError
 */
class EbayEnterprise_Eb2cTax_Model_Response_Quote extends Mage_Core_Model_Abstract
{
	const MERCHANDISE = 0;
	const SHIPPING    = 1;
	const DUTY        = 2;

	protected function _construct()
	{
		$this->_init('eb2ctax/response_quote');
		/*
		 * Subtlety here: if 'node' and 'error' are present, this has been called
		 * by a Tax Response we wish to write. If not, we are in a read mode.
		 */
		$tax = $this->getNode();

		/*
		 * If we have an error, we are in write Mode. If we have no tax node, we
		 * are working with the specific Tax Type (i.e., Merch, Ship, Duty) that had the error.
		 * Since no Tax Data is returned, we create an all-zero response. OrderCreate will
		 * test for presence of taxability (an attribute required by XSD) and will suppress
		 * sending the empty record, but will set TaxHeader Error.
		 */
		if($this->hasCalculationError() && $this->getCalculationError() == true && !$tax) {
			$this->addData(array(
				'code'               => 'CalculationError',
				'tax_type'           => '',
				'taxability'         => '',
				'jurisdiction'       => '',
				'jurisdiction_id'    => '',
				'jurisdiction_level' => '',
				'imposition'         => '',
				'imposition_type'    => '',
				'situs'              => '',
				'effective_rate'     => (float)0.00,
				'taxable_amount'     => (float)0.00,
				'calculated_tax'     => (float)0.00,
			));
		}
		if ($tax) {
			/*
			 * Here again, if we have $tax, we are in write mode. If we have CalculationError,
			 * we'll need to set it. It's possible to receive a CalculationError for an item earlier
			 * and then have a subsequent request correct the error.
			 */
			if($this->hasCalculationError()) {
				$this->setTaxHeaderError($this->getCalculationError());
			}
			$xpath = new DOMXPath($tax->ownerDocument);
			$xpath->registerNamespace('a', $tax->namespaceURI);
			$jurisdiction = $xpath->evaluate('string(a:Jurisdiction)', $tax);
			$imposition = $xpath->evaluate('string(a:Imposition)', $tax);
			$effectiveRate = $xpath->evaluate('number(a:EffectiveRate)', $tax);

			$code              = $jurisdiction && $imposition ?
				$jurisdiction . '-' . $imposition :
				$effectiveRate;

			$this->addData(array(
				'code'               => $code,
				'tax_type'           => $xpath->evaluate('string(@taxType)', $tax),
				'taxability'         => $xpath->evaluate('string(@taxability)', $tax),
				'jurisdiction'       => $jurisdiction,
				'jurisdiction_id'    => $xpath->evaluate('string(a:Jurisdiction/@jurisdictionId)', $tax),
				'jurisdiction_level' => $xpath->evaluate('string(a:Jurisdiction/@jurisdictionLevel)', $tax),
				'imposition'         => $imposition,
				'imposition_type'    => $xpath->evaluate('string(a:Imposition/@impositionType)', $tax),
				'situs'              => $xpath->evaluate('string(a:Situs)', $tax),
				'effective_rate'     => $effectiveRate,
				'taxable_amount'     => $xpath->evaluate('number(a:TaxableAmount)', $tax),
				'calculated_tax'     => $xpath->evaluate('number(a:CalculatedTax)', $tax),
			));
		}
		$this->unsCalculationError(); // Only needed upon receipt of response! Do *not* remember this!
	}
}
