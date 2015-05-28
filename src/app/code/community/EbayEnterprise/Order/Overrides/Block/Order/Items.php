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

class EbayEnterprise_Order_Overrides_Block_Order_Items extends EbayEnterprise_Order_Overrides_Block_Order_Abstract
{
	/** @var string */
	protected $_template = 'ebayenterprise_order/order/items.phtml';
	/**
	 * @see Mage_Sales_Block_Items_Abstract::$_itemRenders
	 * Renderer's with render type key
	 * block    => the block name
	 * template => the template file
	 * renderer => the block object
	 *
	 * @var array
	 */
	protected $_itemRenders = [];

	/**
	 * Initialize default item renderer
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->addItemRender('default', 'checkout/cart_item_renderer', 'checkout/cart/item/default.phtml');
	}

	/**
	 * @see Mage_Sales_Block_Items_Abstract::addItemRender()
	 * Add renderer for item product type
	 *
	 * @param   string
	 * @param   string
	 * @param   string
	 * @return  Mage_Checkout_Block_Cart_Abstract
	 */
	public function addItemRender($type, $block, $template)
	{
		$this->_itemRenders[$type] = [
			'block' => $block,
			'template' => $template,
			'renderer' => null,
		];
		return $this;
	}

	/**
	 * @see Mage_Sales_Block_Items_Abstract::getItemRenderer()
	 * Retrieve item renderer block
	 *
	 * @param  string
	 * @return Mage_Core_Block_Abstract
	 */
	public function getItemRenderer($type)
	{
		if (!isset($this->_itemRenders[$type])) {
			$type = 'default';
		}

		if (is_null($this->_itemRenders[$type]['renderer'])) {
			$this->_itemRenders[$type]['renderer'] = $this->getLayout()
				->createBlock($this->_itemRenders[$type]['block'])
				->setTemplate($this->_itemRenders[$type]['template'])
				->setRenderedBlock($this);
		}
		return $this->_itemRenders[$type]['renderer'];
	}

	/**
	 * @see Mage_Sales_Block_Items_Abstract::_prepareItem()
	 * Prepare item before output
	 *
	 * @param  Mage_Core_Block_Abstract
	 * @return Mage_Sales_Block_Items_Abstract
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function _prepareItem(Mage_Core_Block_Abstract $renderer)
	{
		return $this;
	}

	/**
	 * @see Mage_Sales_Block_Items_Abstract::_getItemType()
	 * Return product type for quote/order item
	 *
	 * @param  Varien_Object
	 * @return string
	 */
	protected function _getItemType(Varien_Object $item)
	{
		if ($item->getOrderItem()) {
			$type = $item->getOrderItem()->getProductType();
		} elseif ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
			$type = $item->getQuoteItem()->getProductType();
		} else {
			$type = $item->getProductType();
		}
		return $type;
	}

	/**
	 * @see Mage_Sales_Block_Items_Abstract::getItemHtml()
	 * Get item row HTML
	 *
	 * @param   Varien_Object
	 * @return  string
	 */
	public function getItemHtml(Varien_Object $item)
	{
		$type = $this->_getItemType($item);
		$block = $this->getItemRenderer($type)
			->setItem($item);
		$this->_prepareItem($block);
		return $block->toHtml();
	}

	/**
	 * Retrieve current rom_order model instance
	 *
	 * @return EbayEnterprise_Order_Model_Detail_Process_IResponse
	 */
	public function getOrder()
	{
		return Mage::registry('rom_order');
	}

	/**
	 * Calculate the grand order total.
	 *
	 * @return float
	 */
	public function getOrderTotal()
	{
		$order = $this->getOrder();
		return $order->getSubtotal() + $order->getShippingAmount() + $order->getDiscountAmount() + $order->getTaxAmount();
	}
}
