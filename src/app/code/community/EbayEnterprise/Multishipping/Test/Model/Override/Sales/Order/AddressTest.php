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

class EbayEnterprise_Multishipping_Test_Model_Override_Sales_Order_AddressTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EbayEnterprise_Multishipping_Helper_Factory */
    protected $_multishippingFactory;

    protected function setUp()
    {
        $this->_multishippingFactory = $this->getHelperMock(
            'ebayenterprise_multishipping/factory',
            ['createItemCollectionForAddress']
        );
    }

    /**
     * Create an order address that should be considered to be the "primary"
     * shipping address for an order. Address will have the provided address
     * id, a parent id of the order id, and an order with the created address
     * as the shipping address.
     *
     * @param int
     * @param int
     * @return EbayEnterprise_Multishipping_Override_Model_Sales_Order_Address
     */
    protected function _getPrimaryShippingAddress($addressId, $orderId)
    {
        $address = Mage::getModel(
            'sales/order_address',
            ['entity_id' => $addressId, 'parent_id' => $orderId]
        );
        $order = $this->getModelMock('sales/order', ['getShippingAddress']);
        $order->method('getShippingAddress')
            ->will($this->returnValue($address));
        $address->setOrder($order);
        return $address;
    }

    /**
     * Create an address model with the provided item collection as the address'
     * collection of items.
     *
     * @param Mage_Sales_Model_Resource_Order_Item_Collection
     * @return EbayEnterprise_Multishipping_Override_Model_Sales_Order_Address
     */
    protected function _createAddressWithItems(Mage_Sales_Model_Resource_Order_Item_Collection $itemCollection)
    {
        $address = Mage::getModel('sales/order_address', ['multishipping_factory' => $this->_multishippingFactory]);
        $this->_multishippingFactory->method('createItemCollectionForAddress')
            ->will($this->returnValue($itemCollection));
        return $address;
    }

    /**
     * When getting an items collection for the address, if an order item
     * does not yet exist for the address, a new item collection should be
     * created and returned.
     */
    public function testGetItemsCollection()
    {
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->disableOriginalConstructor()
            ->getMock();

        $address = Mage::getModel('sales/order_address', ['multishipping_factory' => $this->_multishippingFactory]);
        // Mock the factory such that, if given the address, factory will return
        // the expected item collection. Expect that no matter how many times this
        // method is called, a new collection will only be created once.
        $this->_multishippingFactory->expects($this->once())
            ->method('createItemCollectionForAddress')
            ->with($this->identicalTo($address))
            ->will($this->returnValue($itemCollection));
        $this->assertSame($itemCollection, $address->getItemsCollection());
        // Re-call to ensure that the item collection is stored and not
        // recreated each time it is requested. Factory's `once` invocation
        // matcher will ensure the method is only called the one time to create
        // a new item collection.
        $this->assertSame($itemCollection, $address->getItemsCollection());
    }

    /**
     * When the address is the address returned by $order->getShippingAddress
     * the address should consider itself to be the primary shipping address.
     */
    public function testAddressIsPrimaryAddress()
    {
        $addressId = 1;
        $orderId = 2;
        $address = $this->_getPrimaryShippingAddress($addressId, $orderId);
        $this->assertTrue($address->isPrimaryShippingAddress());
    }

    /**
     * When getting all items, any items in the address' item collection should
     * be returned as an array of items.
     */
    public function testGetAllItems()
    {
        // Create an array of items to serve as the array of the items in
        // the collection.
        $itemsAsArray = [Mage::getModel('sales/order_item'), Mage::getModel('sales/order_item')];
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection->method('getItems')
            ->will($this->returnValue($itemsAsArray));

        $address = $this->_createAddressWithItems($itemCollection);
        $this->assertSame($itemsAsArray, $address->getAllItems());
    }

    /**
     * When getting all visible items, any items in the collection that do not
     * have a parent item should be returned as an array of items.
     */
    public function testGetAllVisibleItems()
    {
        $visibleId = 4;
        $childId = 5;
        // Create the items in the address - one visible (no parent id), one a
        // child/not visible (has a parent id).
        $visibleItem = Mage::getModel('sales/order_item', ['parent_id' => null, 'item_id' => $visibleId]);
        $childItem = Mage::getModel('sales/order_item', ['parent_id' => $visibleId, 'item_id' => $childId]);
        // Create a complete array of items within the collection.
        $itemsAsArray = [$visibleItem, $childItem];
        $visibleItems = [$visibleItem];
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['getItems', 'getItemsByColumnValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection->method('getItems')
            ->will($this->returnValue($itemsAsArray));
        $itemCollection->method('getItemsByColumnValue')
            ->with($this->identicalTo('parent_id'), $this->isFalse())
            ->will($this->returnValue($visibleItems));

        $address = $this->_createAddressWithItems($itemCollection);
        $this->assertSame($visibleItems, $address->getAllVisibleItems());
    }

    /**
     * Provide the size of the address collection and whether that should
     * indicate the address has items.
     *
     * @return array
     */
    public function provideItemCollectionSize()
    {
        return [[2, true], [1, true], [0, false]];
    }

    /**
     * When checking if an address has items, if the collection has at least
     * one item in it, size of 1 or greater, the address should indicate it
     * has items.
     *
     * @param int
     * @param bool
     * @dataProvider provideItemCollectionSize
     */
    public function testAddressHasItems($collectionSize, $hasItems)
    {
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['getSize'])
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection->method('getSize')
            ->will($this->returnValue($collectionSize));

        $address = $this->_createAddressWithItems($itemCollection);
        $this->assertSame($hasItems, $address->hasItems());
    }

    /**
     * When getting an item by id, the item in the collection with a matching
     * id should be returned.
     */
    public function testGetItemById()
    {
        $itemId = 3;
        $item = Mage::getModel('sales/order_item', ['item_id' => $itemId]);
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['getItemById'])
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection->method('getItemById')
            ->with($this->identicalTo($itemId))
            ->will($this->returnValue($item));

        $address = $this->_createAddressWithItems($itemCollection);
        $this->assertSame($item, $address->getItemById($itemId));
    }

    /**
     * When getting an item by quote item id, the item in the collection with a
     * matching quote item id should be returned.
     */
    public function testGetItemByQuoteItemId()
    {
        $quoteItemId = 4;
        $item = Mage::getModel('sales/order_item', ['quote_item_id' => $quoteItemId]);
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['getItemByColumnValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection->method('getItemByColumnValue')
            ->with($this->identicalTo('quote_item_id'), $this->identicalTo($quoteItemId))
            ->will($this->returnValue($item));

        $address = $this->_createAddressWithItems($itemCollection);
        $this->assertSame($item, $address->getItemByQuoteItemId($quoteItemId));
    }

    /**
     * When removing an item by id from an address, the item should be retrieved
     * from the item collection and if it exists, have its _isDeleted flag set
     * to true.
     */
    public function testRemoveItem()
    {
        $itemId = 5;
        $item = $this->getModelMock('sales/order_item', ['isDeleted']);
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['getItemById'])
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection->method('getItemById')
            ->with($this->identicalTo($itemId))
            ->will($this->returnValue($item));
        $item->expects($this->once())
            ->method('isDeleted')
            ->with($this->isTrue())
            ->will($this->returnValue(false));

        $address = $this->_createAddressWithItems($itemCollection);
        $this->assertSame($address, $address->removeItem($itemId));
    }

    /**
     * When removing an item by id from an address, if no matching item is
     * found in the address, nothing should happen and the method should
     * simply return self.
     */
    public function testRemoveItemNoMatchingItem()
    {
        $itemId = 5;
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['getItemById'])
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection->method('getItemById')
            ->with($this->identicalTo($itemId))
            ->will($this->returnValue(null));
        $address = $this->_createAddressWithItems($itemCollection);
        // Basically just make sure address->removeItem returns self, nothing
        // else that can be done in this scenario.
        $this->assertSame($address, $address->removeItem($itemId));
    }

    /**
     * When adding an item to an address, the item should have the address it
     * is being added to set on it and the item should be added to the address'
     * item collection.
     */
    public function testAddItem()
    {
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->setMethods(['addItem'])
            ->disableOriginalConstructor()
            ->getMock();

        $item = $this->getModelMock('sales/order_item', ['setAddress']);

        $address = $this->_createAddressWithItems($itemCollection);

        // Side-effect test: ensure that the given item is added to the current
        // item collection.
        $itemCollection->expects($this->once())
            ->method('addItem')
            ->with($this->identicalTo($item))
            ->will($this->returnSelf());
        // Side-effect test: ensure that the item being added has the address
        // it is being added to set to it - required to make the link between
        // the item and the address.
        $item->expects($this->once())
            ->method('setAddress')
            ->with($this->identicalTo($address))
            ->will($this->returnSelf());

        $this->assertSame($address, $address->addItem($item));
    }
}
