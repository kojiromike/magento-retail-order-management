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
 * Methods for working with order detail items.
 */
class EbayEnterprise_Order_Helper_Detail_Item
{
    /**
     * Filter out any hidden gift items from the list of items.
     *
     * @param Varien_Object[]
     * @return Varien_Object[]
     */
    public function filterHiddenGiftItems(array $items = [])
    {
        return array_filter($items, function ($item) { return !$item->getIsHiddenGift(); });
    }
}
