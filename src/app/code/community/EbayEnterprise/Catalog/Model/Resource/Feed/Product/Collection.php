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


class EbayEnterprise_Catalog_Model_Resource_Feed_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    /**
     * Substitute the product sku for entity_id as all products processed from the
     * feeds will have a sku. This makes looking up a product by SKU more
     * reasonable and allows for newly created items to be looked up after being
     * added to the collection but before the collection has been saved.
     * @param  Varien_Object $item
     * @return string
     */
    protected function _getItemId(Varien_Object $item)
    {
        return $item->getSku();
    }
}
