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

class EbayEnterprise_Eb2cCore_Model_Indexer
{
	/**
	 * Reindex everything; basically does what the command line shell script does
	 */
	public function reindexAll()
	{
		/**
		 * @var EbayEnterprise_MageLog_Helper_Data $log
		 * @var Enterprise_Index_Model_Indexer $indexer
		 * @var Enterprise_Index_Model_Process $process
		 */
		$log = Mage::helper('ebayenterprise_magelog');
		$indexer = Mage::getModel('enterprise_index/indexer');
		$indexerCollection = $indexer->getProcessesCollection();

		Mage::dispatchEvent('shell_reindex_init_process');
		foreach ($indexerCollection as $process) {
				$idx = $process->getIndexer();
				if ($idx->isVisible() !== false) {
				try {
					$process->reindexEverything();
					Mage::dispatchEvent($process->getIndexerCode() . '_shell_reindex_after');
				} catch (Exception $e) {
					$log->logErr('[%s] %s', array(__CLASS__, $e->getMessage()));
				}
			}
		}
		Mage::dispatchEvent('shell_reindex_finalize_process');
	}
}
