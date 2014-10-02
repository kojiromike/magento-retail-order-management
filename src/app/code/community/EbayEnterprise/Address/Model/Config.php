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

class EbayEnterprise_Address_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'address_format_address_only' => 'eb2ccore/ebayenterprise_address/suggestion_templates/address_only_html',
		'address_format_full'         => 'eb2ccore/ebayenterprise_address/suggestion_templates/full_html',
		'api_namespace'               => 'eb2ccore/api/xml_namespace',
		'is_validation_enabled'       => 'eb2ccore/ebayenterprise_address/enabled',
		'max_address_suggestions'     => 'eb2ccore/ebayenterprise_address/max_suggestions',
		'xsd_file_address_validation' => 'eb2ccore/ebayenterprise_address/xsd/file',
	);
}
