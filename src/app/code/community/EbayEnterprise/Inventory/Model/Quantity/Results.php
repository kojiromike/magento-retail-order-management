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
 * Collection of quantity data retrieved from the ROM inventory quantity
 * service. Model will be stored in the session to persist inventory quantity
 * data across requests.
 */
class EbayEnterprise_Inventory_Model_Quantity_Results
{
	/** @var EbayEnterprise_Inventory_Model_Quantity[] */
	protected $_results;
	/** @var EbayEnterprise_Inventory_Model_Quantity[] */
	protected $_resultIndexBySku;
	/** @var EbayEnterprise_Inventory_Model_Quantity[] */
	protected $_resultIndexByItemId;
	/** @var DateTime */
	protected $_expirationTime;
	/** @var array Key value pairs of sku => quantity reflecting quote data when the quantity result was requested. */
	protected $_skuQuantityData;

	/**
	 * @param array $args Must contain:
	 *                    - quantities => EbayEnterprise_Inventory_Model_Quantity[]
	 *                    - expiration_time => DateTime When the results expire
	 *                    - sku_quantity_data => array key/value pairs of skus and the requested quantity when the results were collected
	 */
	public function __construct(array $args=[])
	{
		list(
			$this->_results,
			$this->_expirationTime,
			$this->_skuQuantityData
		) = $this->_checkTypes(
			(array) $args['quantities'],
			$args['expiration_time'],
			$args['sku_quantity_data']
		);
		$this->_createIndexes();
	}

	/**
	 * Enforce type checks on constructor init params.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param DateTime
	 * @param array
	 * @return array
	 */
	public function _checkTypes(array $results, DateTime $expirationTime, array $skuQuantityData)
	{
		return func_get_args();
	}

	/**
	 * Fill in default values.
	 *
	 * @param string
	 * @param array
	 * @param mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $key, $default)
	{
		return isset($arr[$key]) ? $arr[$key] : $default;
	}

	/**
	 * Get a quantity record by sku.
	 *
	 * @param string
	 * @return EbayEnterprise_Inventory_Model_Quantity|null
	 */
	public function getQuantityBySku($sku)
	{
		return $this->_nullCoalesce($this->_resultIndexBySku, $sku, null);
	}

	/**
	 * Get a quantity record by item id.
	 *
	 * @param int
	 * @return EbayEnterprise_Inventory_Model_Quantity|null
	 */
	public function getQuantityByItemId($itemId)
	{
		return $this->_nullCoalesce($this->_resultIndexByItemId, $itemId, null);
	}

	/**
	 * Check if the results have expired. Results that have expired should
	 * not be used.
	 *
	 * @return bool
	 */
	public function isExpired()
	{
		return $this->_expirationTime < date_create();
	}

	/**
	 * Check that the sku quantity data the results were created from match
	 * the given sku quantity data.
	 *
	 * Sku quantity data consts of key => value pairs of skus => total requested quantity.
	 * This is used to determine if a set of results apply to new, potentially
	 * updated quote item data. Results should only apply to a quote if the
	 * sku quantity data of the current quote matches the sku quantity data the
	 * results were collected for.
	 *
	 * @see EbayEnterprise_Inventory_Helper_Quantity::calculateTotalQuantitiesBySku for generating the required mapping from an array of items.
	 *
	 * @param array
	 * @return bool
	 */
	public function checkResultsApplyToItems(array $skuQuantityData)
	{
		// Using equality operator instead of identical operator so array order
		// won't matter as long as keys and values are the same.
		return $this->_skuQuantityData == $skuQuantityData;
	}

	/**
	 * Create copies of the results, indexed by sku and item id.
	 *
	 * @return self
	 */
	protected function _createIndexes()
	{
		foreach ($this->_results as $quantityResult) {
			$this->_resultIndexBySku[$quantityResult->getSku()] = $quantityResult;
			$this->_resultIndexByItemId[$quantityResult->getItemId()] = $quantityResult;
		}
		return $this;
	}
}
