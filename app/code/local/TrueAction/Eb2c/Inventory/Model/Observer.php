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
	 * Get checkout session model instance
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * remove inventory from shopping cart
	 *
	 * @param int $productId
	 * @return void
	 */
	protected function _removeItem($productId)
	{
		foreach ($this->_getCart()->getItems() as $item) {
			if ($item->getProduct()->getId() === $productId) {
				$this->_getCart()->getQuote()->removeItem($item->getItemId())->save();

				$this->_getCart()->getQuote()->addErrorInfo(
					'error',
					'cataloginventory',
					Mage_CatalogInventory_Helper_Data::ERROR_QTY,
					Mage::helper('cataloginventory')->__('Sorry, but this product is currently out of stock. Please remove this item to continue.')
				);
				break;
			}
		}
	}

	/**
	 * reduce quantity to what eb2c has available
	 *
	 * @param int $productId
	 * @param int $ebTwoCQty
	 * @return void
	 */
	protected function _updateItemQty($productId, $ebTwoCQty)
	{
		foreach ($this->_getCart()->getItems() as $item) {
			if ($item->getProduct()->getId() === $productId) {
				$cartData[$item->getItemId()]['qty'] = $ebTwoCQty;
				$this->_getCart()->updateItems($cartData)->save();
				$this->_getCart()->getQuote()->addErrorInfo(
					'error',
					'cataloginventory',
					Mage_CatalogInventory_Helper_Data::ERROR_QTY,
					Mage::helper('cataloginventory')->__('Sorry, but your quanity exceed what we have in stock for this product.')
				);
				break;
			}
		}
	}

	/**
	 * Check e2bc quanity, triggering checkout_cart_add_product_complete event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return void
	 */
	public function checkEb2cInventoryQuantity($observer)
	{
		$request = $observer->getEvent()->getRequest();
		$requestedQty = $request->getParam('qty') ? : 1;

		if ($product = $request->getParam('product')) {
			// We have a valid product, let's check Eb2c Quantity
			$ebTwoCAvailableStock = $this->_getQuantity()->requestQuantity($requestedQty);
			if ($ebTwoCAvailableStock < $requestedQty && $ebTwoCAvailableStock > 0) {
				// Inventory Quantity is less in eb2c than what user requested from magento front-end
				// then, remove item from cart, and then alert customers of the available stock number of this inventory
				$this->_updateItemQty($product, $ebTwoCAvailableStock);
				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, the requested quantity ' .
					$requestedQty . ' is greater than what we currently have in stock ' .
					$ebTwoCAvailableStock . '.'
				);
				//throw new Exception("Cannot add the item to shopping cart.");

			} elseif ($ebTwoCAvailableStock <= 0) {
				// Inventory Quantity is out of stock in ebTwoC
				// then, remove item from cart, and then alert customer the inventory is out of stock.
				$this->_removeItem($product);
				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, this product is out of stock.'
				);
				//throw new Exception("Cannot add the item to shopping cart.");
			}
		}
	}

	/**
	 * Check e2bc quanity, triggering sales_quote_add_item event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return void
	 */
	public function checkEb2cInventoryQtyAddNew($observer)
	{
		$quoteItem = $observer->getEvent()->getQuoteItem();
		$requestedQty = $quoteItem->getQty();
		$productId = $quoteItem->getProduct()->getId();
		$productSku = $quoteItem->getProduct()->getSku();

		if ($productId) {
			// We have a valid product, let's check Eb2c Quantity
			$ebTwoCAvailableStock = $this->_getQuantity()->requestQuantity($requestedQty, $productId, $productSku);
			if ($ebTwoCAvailableStock <= 0) {
				// Inventory Quantity is out of stock in ebTwoC
				// then, remove item from cart, and then alert customer the inventory is out of stock.
				$quoteItem->getQuote()->deleteItem($quoteItem);
			}
		}
	}
}
