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

class EbayEnterprise_Inventory_Override_Block_Multishipping_Overview extends Mage_Checkout_Block_Multishipping_Overview
{
    /**
     * @see Mage_Checkout_Block_Multishipping_Overview::_construct()
     * Overriding this class protected constructor method in order to
     * set the cart item renderer block to utilize the new item template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addItemRender(
            $this->_getRowItemType('default'),
            'checkout/cart_item_renderer',
            'ebayenterprise_inventory/checkout/multishipping/overview/item.phtml'
        );
    }

    /**
     * @see Mage_Checkout_Block_Multishipping_Overview::renderTotals()
     * Overriding this method in order to adjust the row span for the total columns.
     *
     * @param  mixed
     * @param  int | null
     * @return string
     */
    public function renderTotals($totals, $colspan = null)
    {
        if ($colspan === null) {
            $colspan = $this->helper('tax')->displayCartBothPrices() ? 6 : 4;
        }
        return parent::renderTotals($totals, $colspan);
    }
}
