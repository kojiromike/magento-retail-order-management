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

class EbayEnterprise_Rom_Reset_Export_Cutoff_Date extends Mage_Shell_Abstract
{
	const PIM_EXPORT_FEED_CUTOFF_DATE_PATH = 'ebayenterprise_catalog/pim_export_feed/cutoff_date';

	/**
	 * The 'main' of a Mage Shell Script
	 *
	 * @see usageHelp
	 * @return int UNIX exit status
	 */
	public function run()
	{
		$args = array_keys($this->_args);

		if (count($args) !== 1) {
			echo $this->usageHelp();
			return 1;
		} elseif (count(array_intersect($args, array('help', '--help', '-h'))) > 0) {
			echo $this->usageHelp();
			return 0;
		} elseif (count(array_intersect($args, array('reset', '--reset', '-r'))) > 0) {
			Mage::getModel('core/config_data')
				->addData(
					array(
						'path' => self::PIM_EXPORT_FEED_CUTOFF_DATE_PATH,
						'value' => '',
						'scope' => 'default',
						'scope_id' => 0,
					)
				)
				->save();
				printf("cutoff date has been reset\n");
		} else {
				echo $this->usageHelp();
				return 1;
		}
	}

	/**
	 * @return string how to use this script
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return <<<USAGE

Usage: php -f $scriptName reset
  reset      Resets the export feed cutoff date so all products are exported.
  help       This help

USAGE;
	}
}

$reset = new EbayEnterprise_Rom_Reset_Export_Cutoff_Date();
exit($reset->run());
