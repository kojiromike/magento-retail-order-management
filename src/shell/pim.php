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

require_once 'abstract.php';

/**
 * Eb2c Feed Shell
 */
class EbayEnterprise_Eb2c_Shell_Pim extends Mage_Shell_Abstract
{
	/**
	 * The 'main' of a Mage Shell Script
	 *
	 * @see usageHelp
	 */
	public function run()
	{
		Mage::getModel('eb2cproduct/pim_collector')->runExport();
	}
}

$feedProcessor = new EbayEnterprise_Eb2c_Shell_Pim();
exit($feedProcessor->run());
