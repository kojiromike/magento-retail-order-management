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

class EbayEnterprise_Catalog_Model_Observers
{
    /** @var EbayEnterprise_Catalog_Model_Swatches */
    protected $swatch;

    public function __construct(array $initParams = [])
    {
        list($this->swatch) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'swatch', Mage::getModel('ebayenterprise_catalog/swatches'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Catalog_Model_Swatches
     * @return array
     */
    protected function checkTypes(EbayEnterprise_Catalog_Model_Swatches $swatch)
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * This observer locks attributes we've configured as read-only
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function lockReadOnlyAttributes(Varien_Event_Observer $observer)
    {
        $readOnlyAttributesString = Mage::helper('ebayenterprise_catalog')->getConfigModel()->readOnlyAttributes;
        // We use preg_split's PREG_SPLIT_NO_EMPTY so multiple ',' won't populate an array slot
        //  with an empty string. A single string without separators ends up at index 0.
        $readOnlyAttributes = preg_split('/,/', $readOnlyAttributesString, -1, PREG_SPLIT_NO_EMPTY);
        if ($readOnlyAttributes) {
            $product = $observer->getEvent()->getProduct();
            foreach ($readOnlyAttributes as $readOnlyAttribute) {
                $product->lockAttribute($readOnlyAttribute);
            }
        }
    }

    /**
     * Observe the 'catalog_product_save_after' event
     * in order to fix product swatches
     *
     * @param  Varien_Event_Observer
     * @return null
     */
    public function handleCatalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event */
        $event = $observer->getEvent();
        /** @var Mage_Catalog_Model_Product */
        $product = $event->getProduct();
        if ($product instanceof Mage_Catalog_Model_Product) {
            $this->swatch->fixProductSwatches($product);
        }
    }
}
