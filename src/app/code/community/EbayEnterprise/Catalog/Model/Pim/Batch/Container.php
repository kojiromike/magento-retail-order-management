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
 * Simple container class for batches.
 */
class EbayEnterprise_Catalog_Model_Pim_Batch_Container
{
    /** @var array list of batches */
    protected $_batches = array();
    /**
     * Create a batch and add to the list.
     * @param Varien_Data_Collection $productIdCol
     * @param array                  $stores
     * @param array                  $config
     * @return self
     */
    public function addBatch(Varien_Data_Collection $productIdCol, array $stores, array $config, Mage_Core_Model_Store $defaultStore)
    {
        $this->_batches[] = Mage::getModel('ebayenterprise_catalog/pim_batch', array(
            EbayEnterprise_Catalog_Model_Pim_Batch::COLLECTION_KEY => $productIdCol,
            EbayEnterprise_Catalog_Model_Pim_Batch::STORES_KEY => $stores,
            EbayEnterprise_Catalog_Model_Pim_Batch::FT_CONFIG_KEY => $config,
            EbayEnterprise_Catalog_Model_Pim_Batch::DEFAULT_STORE_KEY => $defaultStore,
        ));
        return $this;
    }
    /**
     * @see $_batches
     * @return array list of batches
     */
    public function getBatches()
    {
        return $this->_batches;
    }
}
