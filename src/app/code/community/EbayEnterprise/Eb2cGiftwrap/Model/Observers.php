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

class EbayEnterprise_Eb2cGiftwrap_Model_Observers
{
	/**
	 * Listen to the 'ebayenterprise_feed_dom_loaded' event
	 * @see EbayEnterprise_Catalog_Model_Feed_Abstract::processFile
	 * process a dom document
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function processDom(Varien_Event_Observer $observer)
	{
		Varien_Profiler::start(__METHOD__);
		$event = $observer->getEvent();
		$fileDetail = $event->getFileDetail();
		$importConfig = Mage::getModel('eb2cgiftwrap/feed_import_config');
		$importData = $importConfig->getImportConfigData();
		$feedConfig = $fileDetail['core_feed']->getFeedConfig();

		// only process the import if the event type is in the allowabled event type configuration for this feed
		if (in_array($feedConfig['event_type'], explode(',', $importData['allowable_event_type']))) {
			$fileDetail['doc'] = $event->getDoc();
			Mage::getModel('ebayenterprise_catalog/feed_file', $fileDetail)->process(
				$importConfig, Mage::getModel('eb2cgiftwrap/feed_import_items')
			);
		}
		Varien_Profiler::stop(__METHOD__);
		return $this;
	}
}
