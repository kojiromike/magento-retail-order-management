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

class EbayEnterprise_Eb2cPayment_Model_Resource_Paypal extends Mage_Core_Model_Resource_Db_Abstract
{
	public function _construct()
	{
		$this->_init('eb2cpayment/paypal', 'paypal_id');
	}
	/**
	 * Load paypal by quote_id
	 * @throws Mage_Core_Exception
	 * @param EbayEnterprise_Eb2cPayment_Model_Paypal $paypal
	 * @param int $quoteId
	 * @return EbayEnterprise_Eb2cPayment_Model_Mysql4_Paypal
	 */
	public function loadByQuoteId(EbayEnterprise_Eb2cPayment_Model_Paypal $paypal, $quoteId)
	{
		$this->load($paypal, $quoteId, 'quote_id');
		return $this;
	}
}
