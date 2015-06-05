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

interface EbayEnterprise_Catalog_Interface_Import_Items
{
    /**
     * buildCollection must return a Varien_Data_Collection of objects that can be retrieved by SKU.
     * Its argument is an array of SKUs. If no matching SKUs are found, an empty collection is returned.
     * When called with no arguments, an empty collection is returned.
     * @param  array $skus
     * @return Varien_Data_Collection
     */
    public function buildCollection(array $skus = array());
    /**
     * instantiate new item object and apply dummy data to it. The dummy data
     * is coming from configuration.
     * @param string $sku
     * @param array $additionalData optional data that can be set magically using addData.
     * @return Mage_Core_Model_Abstract
     */
    public function createNewItem($sku, array $additionalData = array());
}
