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

class EbayEnterprise_Inventory_Model_Quantity
{
    /**
     * @param array $args Must contain:
     *                    - sku => string
     *                    - item_id => int
     *                    - quantity => int
     */
    public function __construct(array $args)
    {
        // All primitive types, so a the usual _checkTypes
        // pattern won't add much value.
        $this->_sku = $args['sku'];
        $this->_itemId = $args['item_id'];
        $this->_quantity = (int) $args['quantity'];
    }

    /**
     * Get the SKU of the item the quantity applies to.
     *
     * @return string
     */
    public function getSku()
    {
        return $this->_sku;
    }

    /**
     * @param string
     * @return self
     */
    public function setSku($sku)
    {
        $this->_sku = $sku;
        return $this;
    }

    /**
     * Get the quote item id the quantity applies to.
     *
     * @return string
     */
    public function getItemId()
    {
        return $this->_itemId;
    }

    /**
     * @param string
     * @return self
     */
    public function setItemId($itemId)
    {
        $this->_itemId = $itemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuantity()
    {
        return $this->_quantity;
    }

    /**
     * @param string
     * @return self
     */
    public function setQuantity($quantity)
    {
        $this->_quantity = $quantity;
        return $this;
    }
}
