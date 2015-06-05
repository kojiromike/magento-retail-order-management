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

class EbayEnterprise_Multishipping_Test_Helper_FactoryTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EbayEnterprise_Multishipping_Helper_Factory */
    protected $_multishippingFactory;

    protected function setUp()
    {
        $this->_multishippingFactory = Mage::helper('ebayenterprise_multishipping/factory');
    }
    /**
     * Create a stub address model.
     *
     * @param int
     * @param bool
     * @param int
     * @param string
     * @return Mage_Sales_Model_Order_Address
     */
    protected function _stubAddress($addressId, $isPrimaryShipping, $orderId, $addressType)
    {
        $address = $this->getModelMock(
            'sales/order_address',
            ['getId', 'isPrimaryShippingAddress', 'getParentId', 'getAddressType']
        );
        $address->method('getId')
            ->will($this->returnValue($addressId));
        $address->method('isPrimaryShippingAddress')
            ->will($this->returnValue($isPrimaryShipping));
        $address->method('getParentId')
            ->will($this->returnValue($orderId));
        $address->method('getAddressType')
            ->will($this->returnValue($addressType));
        return $address;
    }

    /**
     * Create a mock order item collection. Replace the mock for real order
     * item collections in the Magento factory so newly instantiated order item
     * collections created during the test will use the mock.
     *
     * @return Mage_Sales_Model_Resource_Order_Item_Collection
     */
    protected function _mockAndReplaceItemCollection()
    {
        $itemCollection = $this->getResourceModelMockBuilder('sales/order_item_collection')
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'setDataToAll'])
            ->getMock();
        // New instance of the item collection needs to be created and will
        // be created using the Mage::getResourceModel factory method. Replace
        // it in the factory with the mock to allow the necessary behavior mocking.
        $this->replaceByMock('resource_model', 'sales/order_item_collection', $itemCollection);
        return $itemCollection;
    }

    /**
     * Create a mockable order address model. Replace the mock for the real
     * order address model in the Mage factory. so newly instantiated order
     * address models created during the test will use the mock.
     */
    protected function _mockAndReplaceOrderAddress()
    {
        $address = $this->getModelMock('sales/order_address', ['load']);
        $this->replaceByMock('model', 'sales/order_address', $address);
        return $address;
    }

    /**
     * Provide configurations for "primary" order addresses - either a primary
     * shipping address or a billing address.
     *
     * @return array
     */
    public function providePrimaryAddressConfigurations()
    {
        return [
            [true, Mage_Sales_Model_Order_Address::TYPE_SHIPPING, false],
            [false, Mage_Sales_Model_Order_Address::TYPE_BILLING, true],
        ];
    }

    /**
     * When creating an item collection for a "primary" address - primary shipping
     * or billing address - the item collection should be filtered to only items
     * with a matching order id; an order address id matching the address id or
     * with no order address id; and virtual items for billing addresses or
     * non-virtual items for shipping addresses.
     *
     * @param bool
     * @param string
     * @param bool
     * @dataProvider providePrimaryAddressConfigurations
     */
    public function testCreateItemCollectionPrimaryAddress(
        $isPrimaryShipping,
        $addressType,
        $includeVirtual
    ) {
        $addressId = 3;
        $orderId = 9;
        $address = $this->_stubAddress($addressId, $isPrimaryShipping, $orderId, $addressType);

        $itemCollection = $this->_mockAndReplaceItemCollection();
        $itemCollection->method('setDataToAll')
            ->will($this->returnSelf());
        // Side-effect test: ensure that the collection is filtered by
        // order_address_id and order_id.
        $itemCollection->expects($this->exactly(3))
            ->method('addFieldToFilter')
            ->withConsecutive(
                [
                    $this->identicalTo('order_id'),
                    $this->identicalTo($orderId)
                ],
                [
                    $this->identicalTo('is_virtual'),
                    $this->identicalTo($includeVirtual)
                ],
                [
                    $this->identicalTo('order_address_id'),
                    $this->identicalTo(
                        [['eq' => $addressId], ['null' => true]]
                    )
                ]
            )
            ->will($this->returnSelf());

        $this->assertSame(
            $itemCollection,
            $this->_multishippingFactory->createItemCollectionForAddress($address)
        );
    }

    /**
     * When creating an item collection for a secondary shipping address, items
     * should be filtered to only include items with a matching order id,
     * matching order address id, and non-virtual items.
     *
     * @param bool
     * @param string
     * @param bool
     * @dataProvider providePrimaryAddressConfigurations
     */
    public function testCreateItemCollectionSecondaryAddress()
    {
        $addressId = 3;
        $orderId = 9;
        $addressType = Mage_Sales_Model_Order_Address::TYPE_SHIPPING;
        $address = $this->_stubAddress($addressId, false, $orderId, $addressType);

        $itemCollection = $this->_mockAndReplaceItemCollection();
        $itemCollection->method('setDataToAll')
            ->will($this->returnSelf());
        // Side-effect test: ensure that the collection is filtered by
        // order_address_id and order_id.
        $itemCollection->expects($this->exactly(3))
            ->method('addFieldToFilter')
            ->withConsecutive(
                [
                    $this->identicalTo('order_id'),
                    $this->identicalTo($orderId)
                ],
                [
                    $this->identicalTo('is_virtual'),
                    $this->identicalTo(false)
                ],
                [
                    $this->identicalTo('order_address_id'),
                    $this->identicalTo($addressId)
                ]
            )
            ->will($this->returnSelf());

        $this->assertSame(
            $itemCollection,
            $this->_multishippingFactory->createItemCollectionForAddress($address)
        );
    }

    /**
     * When creating an item collection for a secondary shipping address, items
     * should be filtered to only include items with a matching order id,
     * matching order address id, and non-virtual items.
     *
     * @param bool
     * @param string
     * @param bool
     * @dataProvider providePrimaryAddressConfigurations
     */
    public function testCreateItemCollectionSetItemData()
    {
        $addressId = 3;
        $orderId = 9;
        $addressType = Mage_Sales_Model_Order_Address::TYPE_SHIPPING;
        $address = $this->_stubAddress($addressId, false, $orderId, $addressType);

        $itemCollection = $this->_mockAndReplaceItemCollection();
        $itemCollection->method('addFieldToFilter')
            ->will($this->returnSelf());
        // Side-effect test: ensure that whenever an item collection is created,
        // all items in the collection have the order address instance and order
        // address id for the address the collection is being created for -
        // ensures the items are linked to the address.
        $itemCollection->expects($this->once())
            ->method('setDataToAll')
            ->with($this->identicalTo([
                'order_address' => $address,
                'order_address_id' => $addressId
            ]))
            ->will($this->returnSelf());

        $this->assertSame(
            $itemCollection,
            $this->_multishippingFactory->createItemCollectionForAddress($address)
        );
    }

    /**
     * When loading an order address for an item with an address id, a new
     * order address model should be instantiated and loaded using the order
     * address id of the item.
     */
    public function testLoadAddressForItemWithAddressId()
    {
        $addressId = 89;
        // Create a mockable order address and replace it in the Mage factory.
        // This method needs to instantiate new order address models so the
        // Mage factory needs to be setup to return the mock object instead
        // of a real order address model.
        $address = $this->_mockAndReplaceOrderAddress();
        // Side-effect test: ensure that the model instance returned by the Mage
        // model factory is loaded using the order address id of the order item.
        $address->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($addressId))
            ->will($this->returnSelf());
        $item = $this->getModelMock('sales/order_item', ['getOrderAddressId']);
        $item->method('getOrderAddressId')->will($this->returnValue($addressId));

        $this->assertSame(
            $address,
            $this->_multishippingFactory->loadAddressForItem($item)
        );
    }

    /**
     * When loading an address for an virtual item without an order address id,
     * the default shipping address of the order associated with the item
     * should be used.
     */
    public function testLoadAddressForItemDefaultPhysicalAddress()
    {
        $shippingAddress = $this->getModelMock('sales/order_address');
        $billingAddress = $this->getModelMock('sales/order_address');
        $order = $this->getModelMock('sales/order', ['getShippingAddress', 'getBillingAddress']);
        $order->method('getShippingAddress')->will($this->returnValue($shippingAddress));
        $order->method('getBillingAddress')->will($this->returnValue($billingAddress));

        $item = $this->getModelMock('sales/order_item', ['getOrderAddressId', 'getOrder', 'getIsVirtual']);
        $item->method('getOrderAddressId')->will($this->returnValue(null));
        $item->method('getOrder')->will($this->returnValue($order));
        $item->method('getIsVirtual')->will($this->returnValue(false));

        $this->assertSame(
            $shippingAddress,
            $this->_multishippingFactory->loadAddressForItem($item)
        );
    }

    /**
     * When loading an address for an virtual item without an order address id,
     * the default billing address of the order associated with the item
     * should be used.
     */
    public function testLoadAddressForItemDefaultVirtualAddress()
    {
        $shippingAddress = $this->getModelMock('sales/order_address');
        $billingAddress = $this->getModelMock('sales/order_address');
        $order = $this->getModelMock('sales/order', ['getShippingAddress', 'getBillingAddress']);
        $order->method('getShippingAddress')->will($this->returnValue($shippingAddress));
        $order->method('getBillingAddress')->will($this->returnValue($billingAddress));

        $item = $this->getModelMock('sales/order_item', ['getOrderAddressId', 'getOrder', 'getIsVirtual']);
        $item->method('getOrderAddressId')->will($this->returnValue(null));
        $item->method('getOrder')->will($this->returnValue($order));
        $item->method('getIsVirtual')->will($this->returnValue(true));

        $this->assertSame(
            $billingAddress,
            $this->_multishippingFactory->loadAddressForItem($item)
        );
    }
}
