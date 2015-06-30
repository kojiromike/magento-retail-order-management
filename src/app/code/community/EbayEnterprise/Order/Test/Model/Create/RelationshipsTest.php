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
 * keeps track of relationship payloads
 */
class EbayEnterprise_Order_Test_Model_Create_RelationshipsTest extends EbayEnterprise_Eb2cCore_Test_Base
{

    public function provideForInjectItemRelationship()
    {
        return [
            [false, Mage_Catalog_Model_Product_Type::TYPE_BUNDLE],
            [true, Mage_Catalog_Model_Product_Type::TYPE_BUNDLE],
            [false, 'othertype'],
            [true, 'othertype'],
        ];
    }

    /**
     * verify
     * - any item in a bundled item relationship will go through the process
     *   of being added to a relationship payload.
     * - any item not a part of a bundle will be ignored.
     *
     * @dataProvider provideForInjectItemRelationship
     */
    public function testInjectItemRelationship($isParent, $productType)
    {
        $item = $this->getModelMockBuilder('sales/order_item')
            ->setMethods(['getParentItem', 'getProductType'])
            ->getMockForAbstractClass();
        $parentItem = $this->getModelMockBuilder('sales/order_item')
            ->setMethods(['getProductType'])
            ->getMockForAbstractClass();
        $itemPayload = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem'
        )
            ->getMockForAbstractClass();

        $relationships = $this->getModelMockBuilder('ebayenterprise_order/create_relationships')
            ->setConstructorArgs([['item' => $item, 'item_payload' => $itemPayload]])
            ->setMethods(['addItemToRelationShip'])
            ->getMock();

        $item->expects($this->once())
            ->method('getParentItem')
            ->will($this->returnValue($isParent ? null : $parentItem));
        $item->expects($this->any())
            ->method('getProductType')
            ->will($this->returnValue($productType));
        $parentItem->expects($this->any())
            ->method('getProductType')
            ->will($this->returnValue($productType));

        // assert that when an item is in a bundle, its payload will be added to
        // a relationship payload. otherwise the item's payload is never added to
        // a relationship.
        if ($productType === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $relationships->expects($this->once())
                ->method('addItemToRelationShip')
                ->with(
                    $this->logicalOr(
                        $this->identicalTo($item),
                        $this->identicalTo($parentItem)
                    ),
                    $this->identicalTo($isParent)
                )
                ->will($this->returnSelf());
        } else {
            $relationships->expects($this->never())
                ->method('addItemToRelationShip');
        }
        $this->assertSame($relationships, $relationships->injectItemRelationship());
    }

    /**
     * verify when a parent bundle item is given the resulting relationship
     * payload will have had the parent item field already set to the given item
     */
    public function testAddItemToRelationshipParent()
    {
        $item = $this->getModelMockBuilder('sales/order_item')
            ->setMethods(null)
            ->getMockForAbstractClass();
        $itemPayload = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem'
        )
            ->getMockForAbstractClass();
        $relationshipPayload = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationship'
        )
            ->getMockForAbstractClass();
        $item = $this->getModelMockBuilder('sales/order_item')
            ->setMethods(null)
            ->getMock();
        $relationships = $this->getModelMockBuilder('ebayenterprise_order/create_relationships')
            ->setConstructorArgs([['item' => $item, 'item_payload' => $itemPayload]])
            ->setMethods(['getOrCreateItemRelationship', 'getKeyItem'])
            ->getMock();

        $relationships->expects($this->once())
            ->method('getKeyItem')
            ->will($this->returnValue($item));
        $relationships->expects($this->once())
            ->method('getOrCreateItemRelationship')
            ->will($this->returnValue($relationshipPayload));

        // assert the item payload is added as the parent of the
        // relationship
        $relType = IItemRelationship::TYPE_KIT;
        $name = 'parent product name';
        $item->setName($name)
            ->setRelationshipType($relType);
        $relationshipPayload->expects($this->once())
            ->method('setParentItem')
            ->with($this->identicalTo($itemPayload))
            ->will($this->returnSelf());
        $relationshipPayload->expects($this->once())
            ->method('setType')
            ->with($this->identicalTo($relType))
            ->will($this->returnSelf());
        $relationshipPayload->expects($this->once())
            ->method('setName')
            ->with($this->identicalTo($name))
            ->will($this->returnSelf());

        $this->assertSame($relationships, $relationships->injectItemRelationship());
    }

    /**
     * verify when a bundle child item is given, the resulting relationship
     * payload will be returned, but the parent item field is left untouched
     */
    public function testAddItemToRelationshipChild()
    {
        $item = $this->getModelMockBuilder('sales/order_item')
            ->setMethods(null)
            ->getMockForAbstractClass();
        $parentItem = $this->getModelMockBuilder('sales/order_item')
            ->setMethods(null)
            ->getMockForAbstractClass();
        $itemPayload = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem'
        )
            ->getMockForAbstractClass();
        $relationshipPayload = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationship'
        )
            ->getMockForAbstractClass();
        $item = $this->getModelMockBuilder('sales/order_item')
            ->setMethods(null)
            ->getMock();
        $relationships = $this->getModelMockBuilder('ebayenterprise_order/create_relationships')
            ->setConstructorArgs([['item' => $item, 'item_payload' => $itemPayload]])
            ->setMethods(['getOrCreateItemRelationship', 'getKeyItem', 'addItemAsMember', 'addItemAsParent'])
            ->getMock();

        // assert the item payload is added as a member
        $relationships->expects($this->once())
            ->method('getKeyItem')
            ->will($this->returnValue($parentItem));
        $relationships->expects($this->once())
            ->method('getOrCreateItemRelationship')
            ->will($this->returnValue($relationshipPayload));
        $relationships->expects($this->never())
            ->method('addItemAsParent');
        $relationships->expects($this->once())
            ->method('addItemAsMember')
            ->with($this->identicalTo($relationshipPayload));

        $this->assertSame($relationships, $relationships->injectItemRelationship());
    }
}
