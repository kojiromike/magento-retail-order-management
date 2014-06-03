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
class EbayEnterprise_Eb2cProduct_Model_Pim_Collector
{
	const CUTOFF_DATE_PATH = 'eb2cproduct/pim_export_feed/cutoff_date';
	/**
	 * lower limit of product change dates that are applicable.
	 * @var string
	 */
	protected $_cutoffDate;
	/**
	 * date/time the export started.
	 * @var string
	 */
	protected $_startDate;
	/**
	 * gather a collection of products and build the feed.
	 * @return self
	 */
	public function runExport()
	{
		$date = $this->_getNewZendDate();
		$this->_startDate = $date->toString('c');
		Mage::helper('ebayenterprise_magelog')->logInfo(
			'[%s] Starting PIM Export with cutoff date "%s"',
			array(__class__, $this->_startDate)
		);
		$this->_loadConfig();
		$products = $this->_getExportableProducts();
		$pim = Mage::getModel('eb2cproduct/pim');
		$entityIds = $products->getColumnValues('entity_id');
		Mage::helper('ebayenterprise_magelog')->logDebug(
			"[%s] Exportable Entity Ids:\n%s",
			array(__class__, json_encode($entityIds))
		);
		if (count($entityIds)) {
			try {
				$pim->buildFeed($entityIds);
			} catch (EbayEnterprise_Eb2cCore_Exception_InvalidXml $e) {
				Mage::helper('ebayenterprise_magelog')->logCrit(
					"[%s] Error building PIM Export:\n%s",
					array(__CLASS__, $e)
				);
			}
		}
		$this->_updateCutoffDate();
		Mage::helper('ebayenterprise_magelog')->logInfo(
			'[%s] Finished PIM Export',
			array(__class__)
		);
		return $this;
	}
	/**
	 * load data from the config used to
	 * @return self
	 */
	protected function _loadConfig()
	{
		$config = Mage::helper('eb2cproduct')->getConfigModel();
		$this->_cutoffDate = $config->pimExportFeedCutoffDate;
		return $this;
	}
	/**
	 * get a collection of products to be exported.
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _getExportableProducts()
	{
		$collection = Mage::getResourceModel('catalog/product_collection');
		$collection->addAttributeToSelect('entity_id');
		if ($this->_cutoffDate) {
			$collection->addFieldToFilter('updated_at', array('gteq' => $this->_cutoffDate));
		}
		return $collection;
	}
	/**
	 * update the cutoff time>>
	 * @return self
	 */
	protected function _updateCutoffDate()
	{
		Mage::helper('ebayenterprise_magelog')->logDebug(
			'[%s] Updateding cutoff date to "%s"',
			array(__class__, $this->_startDate)
		);
		$config = Mage::getModel('core/config_data');
		$config->addData(array(
			'path' => self::CUTOFF_DATE_PATH,
			'value' => $this->_startDate,
			'scope' => 'default',
			'scope_id' => 0,
		));
		$config->save();
		return $this;
	}

	/**
	 * abstracting getting an instance of Zend_Date
	 * @return Zend_Date
	 * @codeCoverageIgnore
	 */
	protected function _getNewZendDate()
	{
		return new Zend_Date();
	}
}
