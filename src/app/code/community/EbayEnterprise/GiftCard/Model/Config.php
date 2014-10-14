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

class EbayEnterprise_GiftCard_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_operation_balance' => 'ebayenterprise_giftcard/api/operations/balance',
		'api_operation_redeem' => 'ebayenterprise_giftcard/api/operations/redeem',
		'api_operation_void' => 'ebayenterprise_giftcard/api/operations/void',
		'api_service' => 'ebayenterprise_giftcard/api/service',
		'is_enabled' => 'ebayenterprise_giftcard/general/is_enabled',
		'bin_ranges' => 'ebayenterprise_giftcard/card_number_bin_ranges',
	);
}
