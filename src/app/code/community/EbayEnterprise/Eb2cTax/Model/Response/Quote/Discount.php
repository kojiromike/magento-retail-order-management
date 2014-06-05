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
 * represents an OrderItem.
 * requires the following data to be passed to the constructor
 * @param  int                        type
 * @param  EbayEnterprise_Dom_Element node
 * @param  boolean                    calculationError
 */
class EbayEnterprise_Eb2cTax_Model_Response_Quote_Discount extends EbayEnterprise_Eb2cTax_Model_Response_Quote
{
	protected function _construct()
	{
		$discount = $this->getNode();
		if ($discount) {
			$xpath = new DOMXPath($discount->ownerDocument);
			$xpath->registerNamespace('a', $discount->namespaceURI);
			// get discount id
			$this->setDiscountId($xpath->evaluate('string(./../../@id)', $discount));
			$this->setCalculateDutyFlag($xpath->evaluate('boolean(./../../@caclulateDuty)', $discount));
			$this->setAmount($xpath->evaluate('number(./../../a:Amount)', $discount));
		}
		// load the rest of the tax data for the quote
		parent::_construct();
	}
}
