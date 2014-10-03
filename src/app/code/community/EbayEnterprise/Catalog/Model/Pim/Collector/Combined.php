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
 * gathers products and exports them as a single feed.
 */
class EbayEnterprise_Catalog_Model_Pim_Collector_Combined
{
	/**
	 * generate a batch of all product ids for the instance in a single
	 * batch.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function gatherAllAsOneBatch(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$container = $event->getContainer();
		$cutoffDate = $event->getCutoffDate();
		$config = $event->getFeedTypeConfig();
		if (method_exists($container, 'addBatch')) {
			$allStores = Mage::helper('eb2ccore/languages')->getStores();
			$defaultStoreId = Mage::helper('ebayenterprise_catalog')->getDefaultStoreViewId();
			$container->addBatch(
				$this->_getExportableProducts($cutoffDate),
				array_filter(array_replace($allStores, array($defaultStoreId => null))),
				$config,
				isset($allStores[$defaultStoreId]) ? $allStores[$defaultStoreId] : Mage::app()->getStore()
			);
		}
		return $this;
	}
	/**
	 * get a collection of products to be exported.
	 * @param  string $cutoffDate
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _getExportableProducts($cutoffDate)
	{
		$collection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect('entity_id');
		if ($cutoffDate) {
			$collection->addFieldToFilter('updated_at', array('gteq' => $cutoffDate));
		}
		return $collection;
	}
}
