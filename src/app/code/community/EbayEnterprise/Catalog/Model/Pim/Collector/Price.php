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
class EbayEnterprise_Catalog_Model_Pim_Collector_Price
{
	const STORE_CODE_PATH = 'eb2ccore/general/store_id';
	/**
	 * collect and partition data into batches.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function gatherBatches(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$cutoffDate = $event->getCutoffDate();
		$container = $event->getContainer();
		foreach ($this->_getWebsites() as $website) {
			$allStores = Mage::helper('eb2ccore/languages')->getWebsiteStores($website);
			// filter the collection by websites and get product ids.
			$container->addBatch(
				$this->_getCollection($cutoffDate, $website),
				$this->_selectStores($website, $allStores, false),
				$event->getFeedTypeConfig(),
				current($this->_selectStores($website, $allStores))
			);
		}
		return $this;
	}
	/**
	 * filter $collection by the cutoff date.
	 * @param  string                    $cutoffDate
	 * @param  Mage_Core_Model_Website   $website
	 * @return Varien_Data_Collection_Db
	 */
	protected function _getCollection($cutoffDate, Mage_Core_Model_Website $website)
	{
		$collection =	Mage::getResourceModel('catalog/product_collection');
		$collection->addAttributeToSelect('entity_id');
		if ($cutoffDate) {
			$collection->addFieldToFilter('updated_at', array('gteq' => $cutoffDate));
		}
		$collection->addWebsiteFilter(array($website->getId()));
		return $collection;
	}
	/**
	 * Get websites to process. Exclude the 'admin' website unless no other
	 * website exists.
	 * @return array list of website models
	 */
	protected function _getWebsites()
	{
		$websites = Mage::app()->getWebsites();
		if (count($websites) < 1) {
			$websites = Mage::app()->getWebsites(true);
		}
		return $websites;
	}
	/**
	 * Get only the stores that are not the default store.
	 * @param  mixed $website
	 * @param  array $allStores list of all stores for the website
	 * @param  bool  $default get only the default store if true.
	 * @return array of Mage_Core_Model_Store
	 */
	protected function _selectStores($website, array $allStores, $default=true)
	{
		$defaultId = $website->getDefaultStore()->getId();
		if ($default) {
			return array(isset($allStores[$defaultId]) ? $allStores[$defaultId] : Mage::app()->getStore());
		}
		return array_filter(array_replace($allStores, array($defaultId => null)));
	}
}
