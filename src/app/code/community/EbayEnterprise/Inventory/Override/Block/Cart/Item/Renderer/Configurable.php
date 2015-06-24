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

class EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer_Configurable extends Mage_Checkout_Block_Cart_Item_Renderer_Configurable
{
    /**
     * Get an estimated delivery message for a quote item.
     *
     * @return string
     */
    public function getEddMessage()
    {
        /** @var EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer */
        $configurableItemRendererBlock = $this->getLayout()->createBlock('checkout/cart_item_renderer');
        $configurableItemRendererBlock->setItem($this->getItem());
        return $configurableItemRendererBlock->getEddMessage();
    }
}
