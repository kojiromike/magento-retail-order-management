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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationshipIterable;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReferenceIterable;

class EbayEnterprise_Order_Model_Detail_Process_Response_Relationship
{
    /** @var EbayEnterprise_Order_Model_Detail_Process_IResponse */
    protected $order;

    /**
     * @param array $initParams Must have this key:
     *                          - 'order' => EbayEnterprise_Order_Model_Detail_Process_IResponse
     */
    public function __construct(array $initParams)
    {
        list($this->order) = $this->checkTypes(
            $initParams['order']
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_IResponse
     * @return array
     */
    protected function checkTypes(EbayEnterprise_Order_Model_Detail_Process_IResponse $order)
    {
        return func_get_args();
    }

    /**
     * Unified all bundle items with their children as a single item with options using
     * bundle child configuration and the relationship data from ROM.
     *
     * @return self
     */
    public function process()
    {
        /** @var Varien_Data_Collection */
        $items = $this->order->getItemsCollection();
        /** @var IOrderDetailResponse */
        $response = $this->order->getResponse();
        /** @var IOrderResponse */
        $order = $response->getOrder();
        /** @var IItemRelationshipIterable */
        $itemRelationships = $order->getItemRelationships();
        /** @var array */
        $bundles = $this->extractBundleItems($itemRelationships);
        foreach ($bundles as $bundle => $data) {
            /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item */
            $parentItem = $items->getItemByColumnValue('ref_id', $bundle);
            if ($parentItem) {
                $this->groupBundle($parentItem, $data);
            }
        }
        return $this;
    }

    /**
     * Extract parent bundle item mapped to their child items.
     *
     * @param  IItemRelationshipIterable
     * @return array
     */
    protected function extractBundleItems(IItemRelationshipIterable $itemRelationships)
    {
        /** @var array */
        $bundles = [];
        /** @var IItemRelationship $itemRelationship */
        foreach ($itemRelationships as $itemRelationship) {
            /** @var string */
            $parentItemId = $itemRelationship->getParentItemId();
            /** @var IOrderItemReferenceIterable */
            $itemReferences = $itemRelationship->getItemReferences();
            $bundles[$parentItemId] = $this->extractBundleChildItems($itemReferences);
        }
        return $bundles;
    }

    /**
     * Extract bundle product child items
     *
     * @param  IOrderItemReferenceIterable
     * @return array
     */
    protected function extractBundleChildItems(IOrderItemReferenceIterable $itemReferences)
    {
        /** @var array */
        $childItems = [];
        /** @var Varien_Data_Collection */
        $items = $this->order->getItemsCollection();
        /** @var IOrderItemReference $itemReference */
        foreach ($itemReferences as $itemReference) {
            /** @var string */
            $referencedItemId = $itemReference->getReferencedItemId();
            /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item */
            $childItem = $items->getItemByColumnValue('ref_id', $referencedItemId);
            if ($childItem) {
                $childItems[$referencedItemId] = $childItem;
            }
        }
        return $childItems;
    }

    /**
     * Add child bundle items as options to the parent product and remove them from the item collection.
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Item
     * @param  array
     * @return self
     */
    protected function groupBundle(EbayEnterprise_Order_Model_Detail_Process_Response_Item $parentItem, array $data)
    {
        /** @var Mage_Catalog_Model_Product */
        $product = $this->getBundleProduct($parentItem);
        /** @var Mage_Bundle_Model_Product_Type */
        $bundleParent = $product->getTypeInstance();
        /** @var array */
        $bundleParentData = $this->getBundleParentData($bundleParent);
        /** @var array */
        $bundleOptions = $this->buildBundleOptions($data, $bundleParentData);
        $parentItem->setProductOptions($bundleOptions);
        $this->removeBundleChildItems($data);
        return $this;
    }

    /**
     * Add child bundle items as options to the parent product and remove them from the item collection.
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Item
     * @return Mage_Catalog_Model_Product
     */
    protected function getBundleProduct(EbayEnterprise_Order_Model_Detail_Process_Response_Item $parentItem)
    {
        return Mage::getModel('catalog/product')
            ->loadByAttribute('sku', $parentItem->getSku());
    }

    /**
     * Get all the bundle data for a product
     *
     * @param Mage_Bundle_Model_Product_Type
     * @return array
     */
    protected function getBundleParentData(Mage_Bundle_Model_Product_Type $bundleParent)
    {
        /** @var Mage_Bundle_Model_Resource_Option_Collection */
        $optionCollection = $bundleParent->getOptionsCollection();
        /** @var int[] */
        $optionIds = $bundleParent->getOptionsIds();
        /** @var Mage_Bundle_Model_Resource_Selection_Collection */
        $selectionCollection = $bundleParent->getSelectionsCollection($optionIds);
        /** @var Mage_Bundle_Model_Option[] */
        $options = $optionCollection->appendSelections($selectionCollection);
        /** @var array */
        $bundleData = [];
        /** @var Mage_Bundle_Model_Option $option */
        foreach ($options as $option) {
            /** @var Mage_Catalog_Model_Product[] */
            $bundleItems = $option->getSelections();
            $bundleData[$option->getDefaultTitle()] = $this->getBundleItemData($bundleItems);
        }
        return $bundleData;
    }

    /**
     * Get all the bundle items data for an array of products
     *
     * @param  Mage_Catalog_Model_Product[]
     * @return array
     */
    protected function getBundleItemData(array $bundleItems)
    {
        /** @var array */
        $bundleItemData = [];
        /** @var Mage_Catalog_Model_Product $selection */
        foreach ($bundleItems as $bundleItem) {
            $bundleItemData[$bundleItem->getSku()] = $bundleItem->getName();
        }
        return $bundleItemData;
    }

    /**
     * Build the bundle item options using child option configurations.
     *
     * @param  array
     * @param  array
     * @return array
     */
    protected function buildBundleOptions(array $data, array $bundleParentData)
    {
        /** @var array */
        $options = [];
        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item $childItem */
        foreach ($data as $childItem) {
            $options = $this->extractBundleOptions($options, $childItem, $bundleParentData);
        }
        return ['options' => $options];
    }

    /**
     * Remove bundle's item child items.
     *
     * @param  array
     * @param  array
     * @return self
     */
    protected function removeBundleChildItems(array $data)
    {
        /** @var Varien_Data_Collection */
        $items = $this->order->getItemsCollection();
        /** @var string $itemId */
        foreach (array_keys($data) as $itemId) {
            /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item */
            $childItem = $items->getItemByColumnValue('ref_id', $itemId);
            if ($childItem) {
                $items->removeItemByKey($childItem->getId());
            }
        }
        return $this;
    }

    /**
     * Extract the bundle options as configured in the parent bundle product.
     *
     * @param  array
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Item
     * @param  array
     * @return array
     */
    protected function extractBundleOptions(array $options, EbayEnterprise_Order_Model_Detail_Process_Response_Item $childItem, array $bundleParentData)
    {
        foreach ($bundleParentData as $label => $data) {
            $options = $this->extractBundleOption($options, $childItem, $label, $data);
        }
        return $options;
    }

    /**
     * Determine and add an option value under the proper bundle configured options.
     *
     * @param  array
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Item
     * @param  string
     * @param  array
     * @return array
     */
    protected function extractBundleOption(array $options, EbayEnterprise_Order_Model_Detail_Process_Response_Item $childItem, $label, array $data)
    {
        $value = $this->getBundleOptionValue($childItem, $data);
        if ($value) {
            /** @var int */
            $position = -1;
            foreach ($options as $index => $option) {
                if ($option['label'] === $label) {
                    $position = $index;
                    break;
                }
            }
            if ($position >= 0) {
                /** @var array */
                $foundOption = $options[$position];
                /** @var string */
                $newValue = $foundOption['value'] . $value;
                $foundOption['value'] = $newValue;
                $options[$position] = $foundOption;
            } else {
                $options[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }
        }
        return $options;
    }

    /**
     * Get the bundle option value name from the passed in child item.
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_Response_Item
     * @param  array
     * @return string | null
     */
    protected function getBundleOptionValue(EbayEnterprise_Order_Model_Detail_Process_Response_Item $childItem, array $data)
    {
        foreach (array_keys($data) as $sku) {
            if ($childItem->getSku() === $sku) {
                return sprintf("%d x %s\n", $childItem->getQtyOrdered(), $childItem->getName());
            }
        }
        return null;
    }
}
