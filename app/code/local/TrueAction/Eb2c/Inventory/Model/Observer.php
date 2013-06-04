<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Observer
{
	protected $_quantity;

	/**
	 * Get Quantity instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Quantity
	 */
	protected function _getQuantity()
	{
		if (!$this->_quantity) {
			$this->_quantity = Mage::getModel('eb2c_inventory/quantity');
		}
		return $this->_quantity;
	}

	/**
	 * Retrieve shopping cart model object
	 *
	 * @return Mage_Checkout_Model_Cart
	 */
	protected function _getCart()
	{
		return Mage::getSingleton('checkout/cart');
	}

	/**
	 * Check e2bc quantity, triggering sales_quote_item_qty_set_after event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return void
	 */
	public function checkEb2cInventoryQuantity($observer)
	{
		$quoteItem = $observer->getEvent()->getItem();
		$requestedQty = $quoteItem->getQty();
		$productId = $quoteItem->getProductId();
		$productSku = $quoteItem->getSku();

		if ($productId) {
			// We have a valid product, let's check Eb2c Quantity
			$availableStock = $this->_getQuantity()->requestQuantity($requestedQty, $productId, $productSku);
			if ($availableStock < $requestedQty && $availableStock > 0) {
				// Inventory Quantity is less in eb2c than what user requested from magento front-end
				// then, remove item from cart, and then alert customers of the available stock number of this inventory
				// set cart item to eb2c available qty
				$quoteItem->setQty($availableStock);

				//recalc totals
				$quoteItem->getQuote()->collectTotals();

				//save the item
				$quoteItem->getQuote()->save();

				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, the requested quantity ' .
					$requestedQty . ' is greater than what we currently have in stock ' .
					$availableStock . '.'
				);

			} elseif ($availableStock <= 0) {
				// Inventory Quantity is out of stock in eb2c
				// then, remove item from cart, and then alert customer the inventory is out of stock.
				$quoteItem->getQuote()->deleteItem($quoteItem);
				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, this product is out of stock.'
				);
				// throwing an error to prevent the successful add to cart message
				throw new Exception('Cannot add the item to shopping cart.');
			}
		}
	}

	/**
	 * Check e2bc quantity, triggering sales_quote_add_item event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return void
	 */
	public function checkEb2cInventoryQtyAddNew($observer)
	{
		$quoteItem = $observer->getEvent()->getQuoteItem();
		$requestedQty = $quoteItem->getProduct()->getQty();
		$productId = $quoteItem->getProduct()->getId();
		$productSku = $quoteItem->getProduct()->getSku();

		if ($productId) {
			// We have a valid product, let's check Eb2c Quantity
			$availableStock = $this->_getQuantity()->requestQuantity($requestedQty, $productId, $productSku);
			if ($availableStock < $requestedQty && $availableStock > 0) {
				// Inventory Quantity is less in eb2c than what user requested from magento front-end
				// then, remove item from cart, and then alert customers of the available stock number of this inventory
				// set cart item to eb2c available qty
				$quoteItem->setQty($availableStock);

				//recalc totals
				$quoteItem->getQuote()->collectTotals();

				//save the item
				$quoteItem->getQuote()->save();

				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, the requested quantity ' .
					$requestedQty . ' is greater than what we currently have in stock ' .
					$availableStock . '.'
				);

			} elseif ($availableStock <= 0) {
				// Inventory Quantity is out of stock in eb2c
				// then, remove item from cart, and then alert customer the inventory is out of stock.
				$quoteItem->getQuote()->deleteItem($quoteItem);
				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, this product is out of stock.'
				);
				// throwing an error to prevent the successful add to cart message
				throw new Exception('Cannot add the item to shopping cart.');
			}
		}
	}
}
