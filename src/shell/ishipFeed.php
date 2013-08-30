<?php
require_once 'abstract.php';

/**
 * Magento Eb2cProduct Item iShip Shell Script
 *
 */
class Mage_Shell_Item_Iship_Feed extends Mage_Shell_Abstract
{
	/**
	 * Running Eb2cProduct Item iShip Feed Script
	 *
	 */
	public function run()
	{
		Mage::log('Starting Eb2cProduct Item iShip Feed.', Zend_Log::DEBUG);
		$iship = Mage::getModel('eb2cproduct/feed_item_iship');
		$iship->processFeeds();
		Mage::log('Ending Eb2cProduct Item iShip Feed', Zend_Log::DEBUG);
	}

	/**
	 * Retrieve Usage Help Message
	 *
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return <<<USAGE
Usage:  php -f $scriptName -- [options]
  help          This help

USAGE;
	}
}

$shell = new Mage_Shell_Item_Iship_Feed();
$shell->run();