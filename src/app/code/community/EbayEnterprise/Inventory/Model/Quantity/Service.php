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

class EbayEnterprise_Inventory_Model_Quantity_Service implements EbayEnterprise_Inventory_Model_Quantity_IService
{
	const QUOTE_INSUFFICIENT_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Quote_Insufficient_Stock_Message';
	const ITEM_INSUFFICIENT_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Item_Insufficient_Stock_Message';
	const INSUFFICIENT_STOCK_EXCEPTION_MESSAGE = 'EbayEnterprise_Inventory_Insufficient_Stock_Exception_Message';
	const QUOTE_OUT_OF_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Quote_Out_Of_Stock_Message';
	const ITEM_OUT_OF_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Item_Out_Of_Stock_Message';
	const OUT_OF_STOCK_EXCEPTION_MESSAGE = 'EbayEnterprise_Inventory_Out_Of_Stock_Exception_Message';
	const INSUFFICIENT_STOCK_ERROR_CODE = 1;
	const OUT_OF_STOCK_ERROR_CODE = 2;
	const ERROR_INFO_SOURCE = 'ebayenterprise_inventory';
	const ERROR_INFO_TYPE = 'inventory';

	/** @var EbayEnterprise_Inventory_Model_Quantity_Collector */
	protected $_quantityCollector;
	/** @var EbayEnterprise_Inventory_Helper_Item_Selection */
	protected $_inventoryItemSelection;
	/** @var EbayEnterprise_Inventory_Helper_Data */
	protected $_inventoryHelper;
	/** @var EbayEnterprise_Inventory_Helper_Quantity */
	protected $_quantityHelper;

	/**
	 * $param array $args May contain:
	 *                    - quantity_collector => EbayEnterprise_Inventory_Model_Quantity_Collector
	 *                    - inventory_item_selection => EbayEnterprise_Inventory_Helper_Item_Selection
	 *                    - inventory_helper => EbayEnterprise_Inventory_Helper_Data
	 *                    - quantity_helper => EbayEnterprise_Inventory_Helper_Quantity
	 */
	public function __construct(array $args=[])
	{
		list(
			$this->_quantityCollector,
			$this->_inventoryItemSelection,
			$this->_inventoryHelper,
			$this->_quantityHelper
		) = $this->_checkTypes(
			$this->_nullCoalesce($args, 'quantity_collector', Mage::getModel('ebayenterprise_inventory/quantity_collector')),
			$this->_nullCoalesce($args, 'inventory_item_selection', Mage::helper('ebayenterprise_inventory/item_selection')),
			$this->_nullCoalesce($args, 'inventory_helper', Mage::helper('ebayenterprise_inventory')),
			$this->_nullCoalesce($args, 'quantity_helper', Mage::helper('ebayenterprise_inventory/quantity'))
		);
	}

	/**
	 * Enforce type checks on constructor init params.
	 *
	 * @param EbayEnterprise_Inventory_Model_Quantity_Collector
	 * @param EbayEnterprise_Inventory_Helper_Item_Selection
	 * @param EbayEnterprise_Inventory_Helper_Data
	 * @param EbayEnterprise_Inventory_Helper_Quantity
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Inventory_Model_Quantity_Collector $quantityCollector,
		EbayEnterprise_Inventory_Helper_Item_Selection $inventoryItemSelection,
		EbayEnterprise_Inventory_Helper_Data $inventoryHelper,
		EbayEnterprise_Inventory_Helper_Quantity $quantityHelper
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
	 * Check if a given item is currently available to be fulfilled.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @return bool
	 */
	public function isItemAvailable(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		$availableQuantity = $this->_getAvailableQuantityForItem($item);
		return $this->_calculateTotalQuantityRequested($item) <= $availableQuantity;
	}

	/**
	 * Check the inventory status of each item in the quote. Will add errors
	 * to the quote and items if any are not currently available at the
	 * requested quantity. Will throw an exception if any item not yet added
	 * to the quote should be prevented from being added.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @return self
	 * @throws EbayEnterprise_Inventory_Exception_Quantity_Unavailable_Exception If any items should not be added to the quote.
	 */
	public function checkQuoteInventory(Mage_Sales_Model_Quote $quote)
	{
		$inventoryItems = $this->_inventoryItemSelection->selectFrom($quote->getAllItems());
		if (empty($inventoryItems)) {
			$this->_quantityCollector->clearResults();
		}
		foreach ($inventoryItems as $item) {
			if (!$this->isItemAvailable($item)) {
				$this->_handleUnavailableItem($item);
			}
		}
		return $this;
	}

	/**
	 * Get the quantity results for an item.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @return EbayEnterprise_Inventory_Model_Quantity|null
	 */
	protected function _getQuantityResultForItem(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		return $this->_quantityCollector
			->getQuantityResultsForQuote($item->getQuote())
			->getQuantityBySku($item->getSku());
	}

	/**
	 * Get the quantity available for the given item.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @return int
	 */
	protected function _getAvailableQuantityForItem(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		$quantityResult = $this->_getQuantityResultForItem($item);
		return $quantityResult ? $quantityResult->getQuantity() : 0;
	}

	/**
	 * Calculate the total quantity requested of a given item. All items in the
	 * quote with the same SKU as the given item will be counted toward the
	 * total quantity.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @return int
	 */
	protected function _calculateTotalQuantityRequested(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		$inventoryItems = $this->_inventoryItemSelection->selectFrom($item->getQuote()->getAllItems());
		return $this->_quantityHelper->calculateTotalQuantityRequested($item, $inventoryItems);
	}

	/**
	 * When an item is found to be currently unavailable, if it is already in
	 * the customer's cart - saved to the quote - add error info to the quote
	 * and item. If the item has not yet been added to the cart, throw an
	 * exception to prevent it from being added.
	 *
	 * @param Mage_Sales_Model_Quote_Item
	 * @return self
	 * @throws EbayEnterprise_Inventory_Exception_Quantity_Unavailable_Exception
	 */
	protected function _handleUnavailableItem(Mage_Sales_Model_Quote_Item $item)
	{
		$availableQuantity = $this->_getAvailableQuantityForItem($item);
		$itemIsOutOfStock = $availableQuantity === 0;
		// Items not already in the cart should not be added if unavailable.
		// Items without an id have not yet been saved and can not have been
		// added to the quote previously. Any item with an id, would have been
		// saved when initially added to the cart previously.
		if (!$item->getId()) {
			$message = $itemIsOutOfStock
				? self::OUT_OF_STOCK_EXCEPTION_MESSAGE
				: self::INSUFFICIENT_STOCK_EXCEPTION_MESSAGE;
			throw Mage::exception(
				'EbayEnterprise_Inventory_Exception_Quantity_Unavailable',
				$this->_inventoryHelper->__($message, $item->getName(), $availableQuantity)
			);
		}
		return $itemIsOutOfStock
			? $this->_addOutOutOfStockErrorInfo($item)
			: $this->_addInsufficientStockErrorInfoForItem($item, $availableQuantity);
	}

	/**
	 * Add error info to an item when it is not available for purchase.
	 *
	 * @param Mage_Sales_Model_Quote_Item
	 * @return self
	 */
	protected function _addOutOutOfStockErrorInfo(Mage_Sales_Model_Quote_Item $item)
	{
		return $this->_addErrorInfoForItem(
			$item,
			self::OUT_OF_STOCK_ERROR_CODE,
			$this->_inventoryHelper->__(self::ITEM_OUT_OF_STOCK_MESSAGE, $item->getName()),
			$this->_inventoryHelper->__(self::QUOTE_OUT_OF_STOCK_MESSAGE)
		);
	}

	/**
	 * Add error info to an item when it is not available for purchase.
	 *
	 * @param Mage_Sales_Model_Quote_Item
	 * @param int
	 * @return self
	 */
	protected function _addInsufficientStockErrorInfoForItem(Mage_Sales_Model_Quote_Item $item, $availableQuantity)
	{
		return $this->_addErrorInfoForItem(
			$item,
			self::INSUFFICIENT_STOCK_ERROR_CODE,
			$this->_inventoryHelper->__(self::ITEM_INSUFFICIENT_STOCK_MESSAGE, $item->getName(), $availableQuantity),
			$this->_inventoryHelper->__(self::QUOTE_INSUFFICIENT_STOCK_MESSAGE)
		);
	}

	/**
	 * Add error info to the item and quote the item belongs to with the
	 * provided error code and messages.
	 *
	 * @param Mage_Sales_Model_Quote_Item
	 * @param int
	 * @param string
	 * @param string
	 * @return self
	 */
	protected function _addErrorInfoForItem(
		Mage_Sales_Model_Quote_Item $item,
		$errorCode,
		$itemMessage,
		$quoteMessage
	) {
		$item->addErrorInfo(
			self::ERROR_INFO_SOURCE,
			$errorCode,
			$itemMessage
		);
		$parentItem = $item->getParentItem();
		if ($parentItem) {
			$parentItem->addErrorInfo(
				self::ERROR_INFO_SOURCE,
				$errorCode,
				$itemMessage
			);
		}
		$item->getQuote()->addErrorInfo(
			self::ERROR_INFO_TYPE,
			self::ERROR_INFO_SOURCE,
			$errorCode,
			$quoteMessage
		);
		return $this;
	}
}
