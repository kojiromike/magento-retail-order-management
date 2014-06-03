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

class EbayEnterprise_Eb2cPayment_Model_Method_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'pbridge_active'              => 'payment/pbridge/active',
		'pbridge_merchant_code'       => 'payment/pbridge/merchantcode',
		'pbridge_merchant_key'        => 'payment/pbridge/merchantkey',
		'pbridge_gateway_url'         => 'payment/pbridge/gatewayurl',
		'pbridge_transfer_key'        => 'payment/pbridge/transferkey',
		'ebc_pbridge_active'          => 'payment/pbridge_eb2cpayment_cc/active',
		'ebc_pbridge_title'           => 'payment/pbridge_eb2cpayment_cc/title',
	);
}
