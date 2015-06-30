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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationship;

/**
 * builds relationship payloads into the ocr request
 */
class EbayEnterprise_Order_Model_Create_Relationships
{
    /** @var EbayEnterprise_Order_Model_Create_Relationships_IStorage */
    protected $storage;
    /** @var Mage_Sales_Model_Order_Item */
    protected $item;
    /** @var IOrderItem */
    protected $itemPayload;

    public function __construct(array $init)
    {
        list(
            $this->item,
            $this->itemPayload,
            $this->storage
        ) = $this->checkTypes(
            $init['item'],
            $init['item_payload'],
            $this->nullCoalesce(
                $init,
                'storage',
                Mage::getSingleton('ebayenterprise_order/create_relationships_storage')
            )
        );
    }

    protected function checkTypes(
        Mage_Sales_Model_Order_Item $item,
        IOrderItem $itemPayload,
        EbayEnterprise_Order_Model_Create_Relationships_IStorage $storage
    ) {
        return func_get_args();
    }

    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    public function injectItemRelationship()
    {
        $keyItem = $this->getKeyItem();
        if (!$keyItem) {
            // if the item is not a member of a bundle, we dont need to worry about
            // a relationship
            return $this;
        }
        $isParentItemPayload = $keyItem === $this->item;
        $this->addItemToRelationShip($keyItem, $isParentItemPayload);
        return $this;
    }

    /**
     * get the item to use when looking up a relationship.
     * return null if the item is not supposed to have a
     * relationship payload
     *
     * @return Mage_Sales_Model_Order_Item|null
     */
    protected function getKeyItem()
    {
        $keyItem = $this->item->getParentItem() ?: $this->item;
        return ($keyItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)
            ? $keyItem
            : null;
    }

    /**
     * add the item to a relationship
     *
     * @param Mage_Sales_Model_Order_Item
     * @param bool
     */
    protected function addItemToRelationship(Mage_Sales_Model_Order_Item $keyItem, $isParentItemPayload)
    {
        $relationship = $this->getOrCreateItemRelationship($keyItem);
        if (!$isParentItemPayload) {
            $this->addItemAsMember($relationship);
        } elseif (!$relationship->getParentItem()) {
            $this->addItemAsParent($relationship, $keyItem);
        }
    }

    /**
     * get the relationship payload for the item.
     * return a new, empty relationship payload if not found.
     *
     * @param Mage_Sales_Model_Order_Item
     * @return IItemRelationship
     */
    protected function getOrCreateItemRelationship(Mage_Sales_Model_Order_Item $keyItem)
    {
        $relationship = $this->storage->getRelationshipPayload($keyItem) ?:
            $this->createRelationshipPayload($this->itemPayload);
        $this->storage->addRelationship($keyItem, $relationship);
        return $relationship;
    }

    /**
     * get the relationship container payload
     *
     * @return IItemRelationshipContainer
     */
    protected function getRelationshipContainer()
    {
        return $this->itemPayload->getParentPayload()->getParentPayload();
    }

    /**
     * create a relationship payload
     *
     * @return IItemRelationship
     */
    protected function createRelationshipPayload()
    {
        $relationshipContainer = $this->getRelationshipContainer();
        $relationships = $relationshipContainer->getItemRelationships();
        $relationship = $relationships->getEmptyItemRelationship();
        $relationships->attach($relationship);
        return $relationship;
    }

    /**
     * add the item payload to the relationship as a member
     *
     * @param IItemRelationship
     */
    protected function addItemAsMember(IItemRelationship $relationship)
    {
        $references = $relationship->getItemReferences();
        $reference = $references->getEmptyItemReference();
        $reference->setReferencedItem($this->itemPayload);
        $references->attach($reference);
    }

    /**
     * add the item payload to the relationship as
     * the parent
     *
     * @param IItemRelationship
     * @param Mage_Sales_Model_Order_Item
     */
    protected function addItemAsParent(
        IItemRelationship $relationship,
        Mage_Sales_Model_Order_Item $keyItem
    ) {
        $relationship->setParentItem($this->itemPayload)
            ->setType($keyItem->getRelationshipType() ?: IItemRelationship::TYPE_DYNAMIC)
            ->setName($keyItem->getName());
    }
}
