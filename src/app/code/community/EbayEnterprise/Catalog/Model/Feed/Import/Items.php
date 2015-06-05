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

class EbayEnterprise_Catalog_Model_Feed_Import_Items implements EbayEnterprise_Catalog_Interface_Import_Items
{
    /**
     * @see EbayEnterprise_Catalog_Interface_Import_Items::buildCollection
     * @param  array $skus
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function buildCollection(array $skus = array())
    {
        return Mage::getResourceModel('ebayenterprise_catalog/feed_product_collection')
            ->addAttributeToSelect(array('*'))
            ->addAttributeToFilter(array(array('attribute' => 'sku', 'in' => $skus)))
            ->load();
    }
    /**
     * @see EbayEnterprise_Catalog_Interface_Import_Items::createNewItem
     * @param string $sku
     * @param array $additionalData optional
     * @return Mage_Catalog_Model_Product
     */
    public function createNewItem($sku, array $additionalData = array())
    {
        return Mage::helper('ebayenterprise_catalog')->createNewProduct($sku, $additionalData);
    }
}
