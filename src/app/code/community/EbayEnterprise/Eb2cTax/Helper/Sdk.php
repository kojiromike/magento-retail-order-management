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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\ITax;

/**
 * helper for working with the sdk
 */
class EbayEnterprise_Eb2cTax_Helper_Sdk
{
	/**
	 * get a tax payload for the given tax quote
	 * @param  EbayEnterprise_Eb2cTax_Model_Response_Quote
	 * @param  ITax
	 * @return ITax
	 */
	public function getAsOrderTaxPayload(EbayEnterprise_Eb2cTax_Model_Response_Quote $taxQuote, ITax $taxPayload)
	{
		return $taxPayload
			->setSitus($taxQuote->getSitus())
			->setEffectiveRate($taxQuote->getEffectiveRate())
			->setCalculatedTax($taxQuote->getCalculatedTax())
			->setType($taxQuote->getTaxType())
			->setTaxability($taxQuote->getTaxability())
			->setJurisdiction($taxQuote->getJurisdiction())
			->setJurisdictionLevel($taxQuote->getJurisdictionLevel())
			->setJurisdictionId($taxQuote->getJurisdictionId())
			->setImposition($taxQuote->getImposition())
			->setImpositionType($taxQuote->getImpositionType())
			->setTaxableAmount($taxQuote->getTaxableAmount())
			->setSellerRegistrationId($taxQuote->getSellerRegistrationId());
	}
}
