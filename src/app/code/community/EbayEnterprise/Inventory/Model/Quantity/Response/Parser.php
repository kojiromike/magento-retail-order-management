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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityReply;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IReplyQuantityItem;

class EbayEnterprise_Inventory_Model_Quantity_Response_Parser
{
	/** @var IQuantityReply */
	protected $_quantityResponse;
	/** @var EbayEnterprise_Inventory_Helper_Quantity_Factory */
	protected $_quantityFactory;
	/** @var EbayEnterprise_Inventory_Model_Quantity[] */
	protected $_quantityResults;

	/**
	 * @param array $args Must contain:
	 *                    - quantity_response => IQuantityReply
	 *                    May contain:
	 *                    - quantity_factory => EbayEnterprise_Inventory_Helper_Quantity_Factory
	 */
	public function __construct(array $args=[])
	{
		list(
			$this->_quantityResponse,
			$this->_quantityFactory
		) = $this->_checkTypes(
			$args['quantity_response'],
			$this->_nullCoalesce($args, 'quantity_factory', Mage::helper('ebayenterprise_inventory/quantity_factory'))
		);
	}

	/**
	 * Enforce type checks on constructor init params.
	 *
	 * @param IQuantityRequest
	 * @param EbayEnterprise_Inventory_Helper_Quantity_Factory
	 * @return array
	 */
	protected function _checkTypes(
		IQuantityReply $quantityResponse,
		EbayEnterprise_Inventory_Helper_Quantity_Factory $quantityFactory
	) {
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
	 * Get quantity models created from the response payload.
	 *
	 * @return EbayEnterprise_Inventory_Model_Quantity[]
	 */
	public function getQuantityResults()
	{
		if (is_null($this->_quantityResults)) {
			$this->_quantityResults = $this->_extractQuantityResults();
		}
		return $this->_quantityResults;
	}

	/**
	 * Extract quantity data from the response payload.
	 *
	 * @return EbayEnterprise_Inventory_Model_Quantity[]
	 */
	protected function _extractQuantityResults()
	{
		return $this->_extractItems($this->_quantityResponse->getQuantityItems());
	}

	/**
	 * Extract data from the iterable of quantity items.
	 *
	 * @param Iterator
	 * @return EbayEnterprise_Inventory_Model_Quantity[]
	 */
	protected function _extractItems(Iterator $quantityItems)
	{
		$results = [];
		foreach ($quantityItems as $itemPayload) {
			$results[] = $this->_extractItem($itemPayload);
		}
		return $results;
	}

	/**
	 * Create a new quantity model from the response quantity item.
	 *
	 * @param IReplyQuantityItem
	 * @return EbayEnterprise_Inventory_Model_Quantity
	 */
	protected function _extractItem(IReplyQuantityItem $item)
	{
		return $this->_quantityFactory->createQuantity($item->getItemId(), $item->getLineId(), $item->getQuantity());
	}
}
