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

class EbayEnterprise_Inventory_Override_Block_Checkout_Cart_Item_Renderer extends Enterprise_GiftCard_Block_Checkout_Cart_Item_Renderer
{
    const TEMPLATE = 'ebayenterprise_inventory/checkout/onepage/review/item.phtml';

    /**
     * @see Mage_Core_Block_Template::_toHtml()
     * Overriding this method in order to override the template
     * the enterprise giftcard module layout is overriding and setting on this block.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->isCartPage()) {
            $this->setTemplate(static::TEMPLATE);
        }
        return parent::_toHtml();
    }

    /**
     * Get an estimated delivery message for a quote item.
     *
     * @return string
     */
    public function getEddMessage()
    {
        if (!$this->isCartPage()) {
            /** @var EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer */
            $giftcardItemRendererBlock = $this->getLayout()->createBlock('checkout/cart_item_renderer');
            $giftcardItemRendererBlock->setItem($this->getItem());
            return $giftcardItemRendererBlock->getEddMessage();
        }
        return '';
    }

    /**
     * Determine if the current controller page is the cart page.
     *
     * @return bool
     */
    protected function isCartPage()
    {
        return $this->getRequest()->getControllerName() === 'cart';
    }
}
