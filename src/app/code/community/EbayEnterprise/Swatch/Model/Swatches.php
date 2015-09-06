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
class EbayEnterprise_Swatch_Model_Swatches
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;
    /** @var Mage_Catalog_Model_Product_Attribute_Media_Api */
    protected $mediaApi;
    /** @var Mage_Catalog_Model_Resource_Product_Type_Configurable */
    protected $productType;
    /** @var int */
    protected $storeId;
    /** @var Mage_Catalog_Helper_Image */
    protected $imageHelper;
    /** @var Mage_Catalog_Model_Product */
    protected $product;
    /** @var string */
    protected $basePath;
    /** @var array */
    protected $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
    ];

    public function __construct(array $initParams = [])
    {
        list($this->mediaApi, $this->productType, $this->imageHelper, $this->product, $this->logger, $this->context, $this->config) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'media_api', Mage::getModel('catalog/product_attribute_media_api')),
            $this->nullCoalesce($initParams, 'product_type', Mage::getResourceSingleton('catalog/product_type_configurable')),
            $this->nullCoalesce($initParams, 'image_helper', Mage::helper('catalog/image')),
            $this->nullCoalesce($initParams, 'product', Mage::getModel('catalog/product')),
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($initParams, 'config', Mage::helper('ebayenterprise_swatch')->getConfigModel())
        );
        $this->storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
        $this->basePath = Mage::getBaseDir();
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  Mage_Catalog_Model_Product_Attribute_Media_Api
     * @param  Mage_Catalog_Model_Resource_Product_Type_Configurable
     * @param  Mage_Catalog_Helper_Image
     * @param  Mage_Catalog_Model_Product
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function checkTypes(
        Mage_Catalog_Model_Product_Attribute_Media_Api $mediaApi,
        Mage_Catalog_Model_Resource_Product_Type_Configurable $productType,
        Mage_Catalog_Helper_Image $imageHelper,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config
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
            if (!$parent->getIsProcessingSwatches()) {
                $parent->setIsProcessingSwatches(true);
                /** @var array */
                $images = $this->mediaApi->items($parent->getId());
                /** @var array */
                $validSwatches = $this->getValidSwatches($parent);
                /** @var array */
                $currentSwatches = $this->getCurrentProductSwatches($parent, $images);
                /** @var array */
                $updateSwatches = $this->getSwatchToBeUpdated($validSwatches, $currentSwatches);
                /** @var array */
                $newSwatches = $this->getSwatchToBeAdded($validSwatches, $currentSwatches, $updateSwatches);
                /** @var array */
                $duplicateSwatches = $this->getDuplicatedSwatches($images);
                $this->processSwatchUpdates($parent, $updateSwatches, $images)
                    ->processSwatchCreates($parent, $newSwatches)
                    ->processDuplicatedSwatches($parent, $duplicateSwatches);
            }
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
    protected function processSwatchUpdates(Mage_Catalog_Model_Product $parent, array $updateSwatches, array $images)
    {
        foreach ($updateSwatches as $oldSwatch => $newSwatch) {
            $this->updateProductSwatch($parent, $images, $oldSwatch, $newSwatch);
        }
        return $this;
    }

    /**
     * Process changes found between valid swatches and old swatches
     *
     * @param  Mage_Catalog_Model_Product
     * @param  array
     * @return self
     */
    protected function processSwatchCreates(Mage_Catalog_Model_Product $parent, array $newSwatches)
    {
        foreach ($newSwatches as $newSwatch) {
            /** @var array */
            $data = $this->getSwatchData($newSwatch['image'], $newSwatch['swatch']);
            $this->createProductSwatch($parent, $data);
        }
        return $this;
    }

    /**
     * Process removing duplicated swatches
     *
     * @param  Mage_Catalog_Model_Product
     * @param  array
     * @return self
     */
    protected function processDuplicatedSwatches(Mage_Catalog_Model_Product $parent, array $duplicateSwatches)
    {
        foreach ($duplicateSwatches as $swatch) {
            $this->removeProductSwatch($parent, $swatch);
        }
        return $this;
    }

    /**
     * Get an array of current swatches that need to be updated with valid swatches.
     *
     * @param  array
     * @param  array
     * @return array
     */
    protected function getSwatchToBeUpdated(array $validSwatches, array $currentSwatches)
    {
        /** @var array */
        $localValidSwatches = $validSwatches;
        /** @var array */
        $updated = [];
        foreach ($currentSwatches as $current) {
            if (!$this->isInValidSwatches($current, $localValidSwatches)) {
                /** @var array */
                $item = array_shift($localValidSwatches);
                $updated[$current] = $item['swatch'];
            }
        }
        return $updated;
    }

    /**
     * Get an array of valid swatches that's not in the current swatches and
     * not in the array of swatches to be updated.
     *
     * @param  array
     * @param  array
     * @param  array
     * @return array
     */
    protected function getSwatchToBeAdded(array $validSwatches, array $currentSwatches, array $updates)
    {
        /** @var array */
        $added = [];
        /** @var array */
        $updated = array_values($updates);
        foreach ($validSwatches as $validSwatch) {
            if (!in_array($validSwatch['swatch'], $currentSwatches) && !in_array($validSwatch['swatch'], $updated)) {
                $added[] = $validSwatch;
            }
        }
        return $added;
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
            $allProducts = $this->getAssociatedProducts($product);
            foreach ($allProducts as $child) {
                /** @var string */
                $image = $this->getProductImageFile($child);
                if ($image) {
                    $swatches[] = [
                        'swatch' => sprintf('%s-%s', $child->getAttributeText('color'), $this->config->swatchSuffix),
                        'image' => $image,
                    ];
                }
            }
        }
        return $swatches;
    }

    /**
     * Get parent configurable product child associated products
     *
     * @param  Mage_Catalog_Model_Product
     * @return Mage_Catalog_Model_Product[]
     */
    protected function getAssociatedProducts(Mage_Catalog_Model_Product $product)
    {
        return $product->getTypeInstance(true)->getUsedProducts(null, $product);
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
     * Create new product swatch.
     *
     * @param  Mage_Catalog_Model_Product
     * @param  array
     * @return self
     */
    protected function createProductSwatch(Mage_Catalog_Model_Product $product, array $data)
    {
        $this->mediaApi->create($product->getId(), $data, $this->storeId);
        return $this;
    }

    /**
     * Remove duplicated product swatch.
     *
     * @param  Mage_Catalog_Model_Product
     * @param  array
     * @return self
     */
    protected function removeProductSwatch(Mage_Catalog_Model_Product $product, array $swatch)
    {
        try {
            $this->mediaApi->remove($product->getId(), $swatch['file']);
        } catch (Mage_Api_Exception $e) {
            $this->logger->warning($e->getMessage(), $this->context->getMetaData(__CLASS__, [], $e));
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

    /**
     * Get a product image URL path
     *
     * @param  Mage_Catalog_Model_Product
     * @return string | null
     */
    protected function getImagePath(Mage_Catalog_Model_Product $product)
    {
        /** @var array */
        $urlData = [];
        try {
            $urlData = parse_url((string) $this->imageHelper->init($product, 'small_image'));
        } catch (Exception $e) {
            // No image types were selected for the product.
            // We must get the first image from the image
            // gallery for this product
            $urlData = $this->getImagePathByGallery($product);
        }
        /** @var string | null */
        return $this->nullCoalesce($urlData, 'path', null);
    }

    /**
     * Get product image path from the product gallery
     *
     * @param  Mage_Catalog_Model_Product
     * @return array
     */
    protected function getImagePathByGallery(Mage_Catalog_Model_Product $product)
    {
        /** @var array */
        $images = $this->mediaApi->items($product->getId());
        /** @var array */
        $image = current($images);
        return isset($image['url']) ? parse_url($image['url']) : [];
    }

    /**
     * Get all parent configurable product ids.
     *
     * @param  Mage_Catalog_Model_Product
     * @return string | null
     */
    protected function getProductImageFile(Mage_Catalog_Model_Product $product)
    {
        /** @var string */
        $path = $this->getImagePath($product);
        return $path ? $this->basePath . $this->getImagePath($product) : null;
    }

    /**
     * Get an image file mime type
     *
     * @param  string
     * @return string | null
     */
    protected function getImageMimeType($imageFile)
    {
        return $this->nullCoalesce($this->mimeTypes, pathinfo($imageFile, PATHINFO_EXTENSION), null);
    }

    /**
     * Get swatch image data to be added.
     *
     * @param  string
     * @return array
     */
    protected function getImageData($imageFile)
    {
        return [
            'content' => base64_encode(file_get_contents($imageFile)),
            'mime' => $this->getImageMimeType($imageFile),
            'name' => basename($imageFile),
        ];
    }

    /**
     * Get new swatch data to be added.
     *
     * @param  string
     * @param  string
     * @return array
     */
    protected function getSwatchData($imageFile, $label)
    {
        return [
            'file' => $this->getImageData($imageFile),
            'label' => $label,
            'position' => 0,
            'types' => [],
            'exclude' => 0
        ];
    }

    /**
     * Determine if the current swatch is in the valid swatch array
     *
     * @param  string
     * @param  array
     * @return bool
     */
    protected function isInValidSwatches($current, array $localValidSwatches)
    {
        foreach ($localValidSwatches as $validSwatch) {
            if ($validSwatch['swatch'] === $current) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get duplicated swatches
     *
     * @param array
     * @return array
     */
    protected function getDuplicatedSwatches(array $images)
    {
        /** @var array */
        $unique = [];
        /** @var array */
        $dupe = [];
        foreach ($images as $image) {
            $label = $image['label'];
            if (!in_array($label, $unique)) {
                $unique[] = $label;
            } else {
                $dupe[] = $image;
            }
        }
        return $dupe;
    }
}
