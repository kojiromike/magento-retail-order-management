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
 * grouping of data needed to build an export feed file.
 */
class EbayEnterprise_Catalog_Model_Pim_Batch
{
	const COLLECTION_KEY = 'product_id_collection';
	const STORES_KEY = 'store_views';
	const FT_CONFIG_KEY = 'feed_type_config';
	const DEFAULT_STORE_KEY = 'default_store';

	/** @var array template so there's no need for an isset check on each key. */
	protected static $_defaultArgs = array(
		self::COLLECTION_KEY => null,
		self::STORES_KEY => null,
		self::FT_CONFIG_KEY => null,
		self::DEFAULT_STORE_KEY => null,
	);

	/** @var Varien_Data_Collection A collection of items with a entity_id field */
	protected $_productIdCollection;
	/** @var array A list of Mage_Core_Model_Store models */
	protected $_storeViews;
	/** @var array config data for a single feed */
	protected $_feedTypeConfig;

	/**
	 * Setup the batch with data.
	 * @param array $args array with the following keys
	 *   product_id_collection @see $_productIdCollection
	 *   store_views           @see $_storeViews
	 *   feed_type_config      @see $_feedTypeConfig
	 */
	public function __construct($args=array())
	{
		$args = array_replace(self::$_defaultArgs, $args);
		$this->_productIdCollection = $args[self::COLLECTION_KEY] instanceof Varien_Data_Collection ? $args[self::COLLECTION_KEY] : new Varien_Data_Collection();
		$this->_storeViews = is_array($args[self::STORES_KEY]) ? $args[self::STORES_KEY] : array();
		$this->_feedTypeConfig = is_array($args[self::FT_CONFIG_KEY]) ? $args[self::FT_CONFIG_KEY] : array();
		$this->_defaultStoreView = $args[self::DEFAULT_STORE_KEY] instanceof Mage_Core_Model_Store ? $args[self::DEFAULT_STORE_KEY] : Mage::app()->getStore();
	}
	/**
	 * @return array list of product id's
	 */
	public function getProductIds()
	{
		return $this->_productIdCollection->getColumnValues('entity_id');
	}
	/**
	 * get the list of stores for the batch.
	 * @see $_storeViews
	 */
	public function getStores()
	{
		return $this->_storeViews;
	}
	/**
	 * get the store to use as the default store for the batch.
	 * @see $_storeViews
	 */
	public function getDefaultStore()
	{
		return $this->_defaultStoreView;
	}
	/**
	 * get the config for the feed that will be generated from this batch.
	 * @see $_feedTypeConfig
	 */
	public function getFeedTypeConfig()
	{
		return $this->_feedTypeConfig;
	}
}
