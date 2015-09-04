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
 * Fix Configurable product swatches
 */
class EbayEnterprise_Catalog_Model_Swatches
{
    /** @var Mage_Catalog_Model_Product_Attribute_Media_Api */
    protected $mediaApi;
    /** @var Mage_Catalog_Model_Resource_Product_Type_Configurable */
    protected $productType;
    /** @var int */
    protected $storeId;

    public function __construct(array $initParams = [])
    {
        list($this->mediaApi, $this->productType) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'media_api', Mage::getModel('catalog/product_attribute_media_api')),
            $this->nullCoalesce($initParams, 'product_type', Mage::getResourceSingleton('catalog/product_type_configurable'))
        );
        $this->storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  Mage_Catalog_Model_Product_Attribute_Media_Api
     * @param  Mage_Catalog_Model_Resource_Product_Type_Configurable
     * @return array
     */
    protected function checkTypes(
        Mage_Catalog_Model_Product_Attribute_Media_Api $mediaApi,
        Mage_Catalog_Model_Resource_Product_Type_Configurable $productType
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
     * Fix configurable product swatches.
     *
     * @param  Mage_Catalog_Model_Product
     * @return self
     */
    public function fixProductSwatches(Mage_Catalog_Model_Product $product)
    {
        /** @var Mage_Catalog_Model_Product[] | Mage_Catalog_Model_Resource_Product_Collection */
        $parents = $this->getParentConfigurableProducts($product);
        foreach ($parents as $parent) {
            /** @var array */
            $images = $this->mediaApi->items($parent->getId());
            /** @var array */
            $validSwatches = $this->getValidSwatches($parent);
            /** @var array */
            $currentSwatches = $this->getCurrentProductSwatches($parent, $images);
            /** @var array */
            $changes = $this->getSwatchChanges($validSwatches, $currentSwatches);
            $this->processSwatchChanges($parent, $changes, $images);
        }
        return $this;
    }

    /**
     * Process changes found between valid swatches and old swatches
     *
     * @param  Mage_Catalog_Model_Product
     * @param  array
     * @param  array
     * @return self
     */
    protected function processSwatchChanges(Mage_Catalog_Model_Product $parent, array $changes, array $images)
    {
        foreach ($changes as $oldSwatch => $newSwatch) {
            $this->updateProductSwatch($parent, $images, $oldSwatch, $newSwatch);
        }
        return $this;
    }

    /**
     * Get an array of swatch changes between valid swatches and current swatches.
     *
     * @param  array
     * @param  array
     * @return array
     */
    protected function getSwatchChanges(array $validSwatches, array $currentSwatches)
    {
         /** @var array */
        $diffValues = array_values(array_diff($validSwatches, $currentSwatches));
        /** @var array */
        $diffKeys = array_values(array_diff($currentSwatches, $validSwatches));
        /** @var array */
        return array_combine($diffKeys, $diffValues);
    }

    /**
     * Get valid swatches using parent product child product options.
     *
     * @param  Mage_Catalog_Model_Product
     * @return array
     */
    protected function getValidSwatches(Mage_Catalog_Model_Product $product)
    {
        /** @var array */
        $swatches = [];
        if ($product->getId() && $product->getTypeId() === Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            /** @var array */
            $allProducts = $product
                ->getTypeInstance(true)
                ->getUsedProducts(null, $product);
            foreach ($allProducts as $child) {
                $swatches[] = sprintf('%s-swatch', $child->getAttributeText('color'));
            }
        }
        return $swatches;
    }

    /**
     * Get swatches that's actually in the passed in product.
     *
     * @param  Mage_Catalog_Model_Product
     * @param  array
     * @return array
     */
    protected function getCurrentProductSwatches(Mage_Catalog_Model_Product $product, array $images)
    {
        /** @var array */
        $swatches = [];
        /** @var array $image */
        foreach ($images as $image) {
           $swatches[] = $image['label'];
        }
        return $swatches;
    }

    /**
     * Updating product old swatch with new swatch.
     *
     * @param  Mage_Catalog_Model_Product
     * @param  array
     * @param  string
     * @param  string
     * @param  int
     * @return self
     */
    protected function updateProductSwatch(Mage_Catalog_Model_Product $product, array $images, $oldSwatch, $newSwatch)
    {
        /** @var array $image */
        foreach ($images as $image) {
           if ($image['label'] === $oldSwatch) {
                $image['label'] = $newSwatch;
                $this->mediaApi->update($product->getId(), $image['file'], $image, $this->storeId);
           }
        }
        return $this;
    }

    /**
     * Return an array of parent configurable products.
     *
     * @param  Mage_Catalog_Model_Product
     * @return Mage_Catalog_Model_Product[] | Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getParentConfigurableProducts(Mage_Catalog_Model_Product $child)
    {
        if ($child->getTypeId() === Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            return [$child];
        }
        /** @var array */
        $ids = $this->getAllParentProductIds($child);
        return Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect(['*'])
            ->addAttributeToFilter([['attribute' => 'entity_id', 'in' => $ids]])
            ->load();
    }

    /**
     * Get all parent configurable product ids.
     *
     * @param  Mage_Catalog_Model_Product
     * @return array
     */
    protected function getAllParentProductIds(Mage_Catalog_Model_Product $child)
    {
        return $this->productType->getParentIdsByChild($child->getId());
    }
}
