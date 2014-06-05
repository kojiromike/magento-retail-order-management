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


class EbayEnterprise_Eb2cCustomerService_Model_Config
	extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_xml_ns' => 'eb2ccore/customer_service/api/xml_ns',
		'csr_user' => 'eb2ccore/customer_service/csr_user',
		'is_csr_login_enabled' => 'eb2ccore/customer_service/enable_csr_tool',
		'xsd_file_token_validation' => 'eb2ccore/customer_service/api/xsd/file',
	);
}
