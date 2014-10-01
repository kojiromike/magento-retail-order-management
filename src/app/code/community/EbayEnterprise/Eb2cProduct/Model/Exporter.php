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

/**
 * gathers products and exports them as a feed.
 */
class EbayEnterprise_Eb2cProduct_Model_Exporter
{
	 // @var string lower limit of product change dates that are applicable.
	protected $_cutoffDate;
	// @var string base name for the events this model dispatches
	protected $_eventBase = 'ebayenterprise_product_export';
	 // @var string date/time the export started.
	protected $_startDate;
	// @var EbayEnterprise_Eb2cCore_Model_Config_Registry
	protected $_config;
	// @var array
	protected $_feedConfig;
	// @var EbayEnterprise_MageLog_Helper_Data
	protected $_logger;

	public function __construct()
	{
		$this->_config = Mage::helper('eb2cproduct')->getConfigModel();
		$this->_logger = Mage::helper('ebayenterprise_magelog');
	}

	/**
	 * build feed files.
	 * @return self
	 */
	public function runExport()
	{
		$this->_startDate = Mage::helper('eb2ccore')->getNewDateTime()->format('c');
		$this->_logger->logInfo(
			'[%s] Starting Ebay Enterprise Product Export with start date "%s"',
			array(__CLASS__, $this->_startDate)
		);
		$this->_loadConfig();
		$batches = $this->_gatherAllBatches();
		$this->_buildBatches($batches);
		$this->_logger->logInfo(
			'[%s] Ebay Enterprise Product Export finished',
			array(__CLASS__)
		);
		return $this;
	}
	/**
	 * Build the given batches into feed files.
	 * @param  array of EbayEnterprise_Eb2cProduct_Model_Pim_Batch $batches
	 * @return self
	 */
	protected function _buildBatches(array $batches)
	{
		try {
			foreach ($batches as $batch) {
				Mage::getModel('eb2cproduct/pim', array('batch' => $batch))->buildFeed();
			}
			$this->_updateCutoffDate();
		} catch (EbayEnterprise_Eb2cCore_Exception_InvalidXml $e) {
			$this->_logger->logCrit(
				"[%s] Error building export feeds:\n%s",
				array(__CLASS__, $e)
			);
		}
		return $this;
	}
	/**
	 * gather batches into a single array
	 * @return array of EbayEnterprise_Eb2cProduct_Model_Pim_Batch
	 */
	protected function _gatherAllBatches()
	{
		$container = Mage::getModel('eb2cproduct/pim_batch_container');
		foreach ($this->_feedConfig as $feedTypeKey => $feedTypeConfig) {
			Mage::dispatchEvent("{$this->_eventBase}_{$feedTypeKey}", array(
				'container' => $container,
				'feed_type' => $feedTypeKey,
				'feed_type_config' => $feedTypeConfig,
				'cutoff_date' => $this->_cutoffDate
			));
		}
		return $container->getBatches();
	}
	/**
	 * load data from the config used to
	 * @return self
	 */
	protected function _loadConfig()
	{
		$this->_cutoffDate = $this->_config->pimExportFeedCutoffDate;
		$this->_feedConfig = $this->_config->exportFeedConfig;
		return $this;
	}
	/**
	 * @return self
	 */
	protected function _updateCutoffDate()
	{
		$this->_logger->logDebug(
			'[%s] Updateing cutoff date to "%s"',
			array(__CLASS__, $this->_startDate)
		);
		$config = Mage::getModel('core/config_data');
		$config->addData(array(
			'path' => 'eb2cproduct/pim_export_feed/cutoff_date',
			'value' => $this->_startDate,
			'scope' => 'default',
			'scope_id' => 0,
		));
		$config->save();
		return $this;
	}
}
