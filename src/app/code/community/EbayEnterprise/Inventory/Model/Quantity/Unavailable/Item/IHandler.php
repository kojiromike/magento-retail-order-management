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

/**
 * Strategy for handling items in the cart that are no longer available.
 */
interface EbayEnterprise_Inventory_Model_Quantity_Unavailable_Item_IHandler
{
    /**
     * Deal with the unavailable item.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @param int
     * @return self
     * @throws EbayEnterprise_Inventory_Exception_Quantity_Unavailable If item should not be added to the quote
     */
    public function handleUnavailableItem(Mage_Sales_Model_Quote_Item $item, $quantityAvailable);
}
