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

class EbayEnterprise_Eb2cInventory_Overrides_Model_Cart extends Mage_Checkout_Model_Cart
{
	const EBAY_ENTERPRISE_EB2CINVENTORY_PRODUCT_NOT_EXIST = 'EbayEnterprise_Eb2cinventory_Product_Not_Exist';
	const EBAY_ENTERPRISE_EB2CINVENTORY_QUOTE_ITEM_NOT_EXIST = 'EbayEnterprise_Eb2cinventory_Quote_Item_Not_Exist';

	/**
	 * Overriding Add product to shopping cart (quote)
	 *
	 * @param int|Mage_Catalog_Model_Product $productInfo
	 * @param mixed $requestInfo
	 * @throws Mage_Core_Exception
	 * @return Mage_Checkout_Model_Cart
	 */
	public function addProduct($productInfo, $requestInfo=null)
	{
		$product = $this->_getProduct($productInfo);
		$request = $this->_getProductRequest($requestInfo);

		$productId = $product->getId();

		// Removing Magento built-in inventory check, to prevent clashing with eb2c quantity check event.
		if ((int) $productId > 0) {
			try {
				$result = $this->getQuote()->addProduct($product, $request);
			} catch (Mage_Core_Exception $e) {
				$this->getCheckoutSession()->setUseNotice(false);
				$result = $e->getMessage();
			}
			/**
			 * String we can get if prepare process has error
			 */

			if (is_string($result)) {
				$redirectUrl = ($product->hasOptionsValidationFail())
					? $product->getUrlModel()->getUrl(
						$product,
						array('_query' => array('startcustomization' => 1))
					)
					: $product->getProductUrl();
				$this->getCheckoutSession()->setRedirectUrl($redirectUrl);
				if ($this->getCheckoutSession()->getUseNotice() === null || trim($this->getCheckoutSession()->getUseNotice()) === '') {
					$this->getCheckoutSession()->setUseNotice(true);
				}
				throw Mage::exception('Mage_Core', $result);
			}
		} else {
			throw Mage::exception('Mage_Core',
				Mage::helper('checkout')->__(self::EBAY_ENTERPRISE_EB2CINVENTORY_PRODUCT_NOT_EXIST)
			);
		}

		Mage::dispatchEvent('checkout_cart_product_add_after', array('quote_item' => $result, 'product' => $product));
		$this->getCheckoutSession()->setLastAddedProductId($productId);
		return $this;
	}

	/**
	 * Overriding Update item in shopping cart (quote)
	 * $requestInfo - either qty (int) or buyRequest in form of array or Varien_Object
	 * $updatingParams - information on how to perform update, passed to Quote->updateItem() method
	 *
	 * @param int $itemId
	 * @param int|array|Varien_Object $requestInfo
	 * @param null|array|Varien_Object $updatingParams
	 * @throws Mage_Core_Exception
	 * @return Mage_Sales_Model_Quote_Item|string
	 * @see Mage_Sales_Model_Quote::updateItem()
	 */
	public function updateItem($itemId, $requestInfo=null, $updatingParams=null)
	{
		try {
			$item = $this->getQuote()->getItemById($itemId);
			if (!$item) {
				throw Mage::exception('Mage_Core',
					Mage::helper('checkout')->__(self::EBAY_ENTERPRISE_EB2CINVENTORY_QUOTE_ITEM_NOT_EXIST)
				);
			}
			$productId = $item->getProduct()->getId();
			$product = $this->_getProduct($productId);
			$request = $this->_getProductRequest($requestInfo);

			// removing Magento built-in inventory check, to prevent clashing with eb2c quantity check event.
			$result = $this->getQuote()->updateItem($itemId, $request, $updatingParams);
		} catch (Mage_Core_Exception $e) {
			$this->getCheckoutSession()->setUseNotice(false);
			$result = $e->getMessage();
		}

		/**
		 * We can get string if updating process had some errors
		 */
		if (is_string($result)) {
			if ($this->getCheckoutSession()->getUseNotice() === null || trim($this->getCheckoutSession()->getUseNotice()) === '') {
				$this->getCheckoutSession()->setUseNotice(true);
			}
			throw Mage::exception('Mage_Core', $result);
		}
		Mage::dispatchEvent('checkout_cart_product_update_after', array(
			'quote_item' => $result,
			'product' => $product
		));
		$this->getCheckoutSession()->setLastAddedProductId($productId);
		return $result;
	}
}
