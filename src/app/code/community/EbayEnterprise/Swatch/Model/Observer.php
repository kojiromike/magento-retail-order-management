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

class EbayEnterprise_Swatch_Model_Observer
{
    /** @var EbayEnterprise_Swatch_Model_Swatches */
    protected $swatch;
    /** @var EbayEnterprise_Swatch_Helper_Data */
    protected $helper;

    public function __construct(array $initParams = [])
    {
        list($this->swatch, $this->helper) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'swatch', Mage::getModel('ebayenterprise_swatch/swatches')),
            $this->nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_swatch'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Swatch_Model_Swatches
     * @param  EbayEnterprise_Swatch_Helper_Data
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Swatch_Model_Swatches $swatch,
        EbayEnterprise_Swatch_Helper_Data $helper
    )
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
        if ($product instanceof Mage_Catalog_Model_Product && !$this->helper->isLocked($product)) {
            $this->swatch->fixProductSwatches($product);
        }
    }
}
