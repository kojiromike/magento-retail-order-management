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

class EbayEnterprise_Eb2cPayment_Overrides_Model_Paypal_Info
	extends Mage_Paypal_Model_Info
{
	const DO_EXPRESS_REQUEST_ID = 'do_express_request_id';
	const DO_AUTH_REQUEST_ID = 'do_auth_request_id';
	/**
	 * Add mappings to the payment map for transferring PayPal
	 * do express and do auth request ids from the API model to the
	 * payment additional_info.
	 */
	public function __construct()
	{
		$this->_paymentMap[self::DO_EXPRESS_REQUEST_ID] = 'paypal_do_express_request_id';
		$this->_paymentMap[self::DO_AUTH_REQUEST_ID] = 'request_id';
	}
}
