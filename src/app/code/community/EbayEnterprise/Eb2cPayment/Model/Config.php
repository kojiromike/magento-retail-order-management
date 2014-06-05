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

class EbayEnterprise_Eb2cPayment_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_opt_paypal_do_authorization'     => 'eb2cpayment/api_opt_paypal_do_authorization',
		'api_opt_paypal_do_express_checkout'  => 'eb2cpayment/api_opt_paypal_do_express_checkout',
		'api_opt_paypal_do_void'              => 'eb2cpayment/api_opt_paypal_do_void',
		'api_opt_paypal_get_express_checkout' => 'eb2cpayment/api_opt_paypal_get_express_checkout',
		'api_opt_paypal_set_express_checkout' => 'eb2cpayment/api_opt_paypal_set_express_checkout',
		'api_opt_stored_value_balance'        => 'eb2cpayment/api_opt_stored_value_balance',
		'api_opt_stored_value_redeem'         => 'eb2cpayment/api_opt_stored_value_redeem',
		'api_opt_stored_value_redeem_void'    => 'eb2cpayment/api_opt_stored_value_redeem_void',
		'api_payment_xml_ns'                  => 'eb2cpayment/api_payment_xml_ns',
		'api_return_format'                   => 'eb2cpayment/api_return_format',
		'api_service'                         => 'eb2cpayment/api_service',
		'api_xml_ns'                          => 'eb2ccore/api/xml_namespace',
		'is_payment_enabled'                  => 'eb2ccore/eb2cpayment/enabled',
		'svc_bin_range_GS'                    => 'eb2ccore/payment/svc_bin_range/GS',
		'svc_bin_range_SP'                    => 'eb2ccore/payment/svc_bin_range/SP',
		'svc_bin_range_SV'                    => 'eb2ccore/payment/svc_bin_range/SV',
		'svc_bin_range_VL'                    => 'eb2ccore/payment/svc_bin_range/VL',
		'xsd_file_paypal_do_auth'             => 'eb2cpayment/xsd/paypal_do_auth_file',
		'xsd_file_paypal_do_express'          => 'eb2cpayment/xsd/paypal_express_do_file',
		'xsd_file_paypal_get_express'         => 'eb2cpayment/xsd/paypal_express_get_file',
		'xsd_file_paypal_set_express'         => 'eb2cpayment/xsd/paypal_express_set_file',
		'xsd_file_paypal_void_auth'           => 'eb2cpayment/xsd/paypal_void_auth_file',
		'xsd_file_stored_value_balance'       => 'eb2cpayment/xsd/stored_value_balance_file',
		'xsd_file_stored_value_redeem'        => 'eb2cpayment/xsd/stored_value_redeem_file',
		'xsd_file_stored_value_void_redeem'   => 'eb2cpayment/xsd/stored_value_redeem_void_file',
	);
}
