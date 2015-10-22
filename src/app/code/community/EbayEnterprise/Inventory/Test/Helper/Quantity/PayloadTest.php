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

class EbayEnterprise_Inventory_Test_Helper_Quantity_PayloadTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EbayEnterprise_Inventory_Helper_Quantity_Payload */
    protected $payloadHelper;
    /**
     * Mock instance of the inventory helper to ensure consistent behavior
     * of the dependency during test.
     *
     * @var EbayEnterprise_Inventory_Helper_Data
     */
    protected $inventoryHelper;
    /**
     * Inventory helper instance referenced by the payload helper before being
     * swapped out for a mock.
     *
     * @var EbayEnterprise_Inventory_Helper_Data
     */
    protected $origInventoryHelper;

    protected function setUp()
    {
        $this->payloadHelper = Mage::helper('ebayenterprise_inventory/quantity_payload');
        $this->inventoryHelper = $this->getHelperMock('ebayenterprise_inventory', ['getRomSku']);
        $this->inventoryHelper->method('getRomSku')->will($this->returnArgument(0));

        // Swap out the inventory helper instance referenced by the payload helper
        // with the mock for consistent behavior during tests.
        $this->origInventoryHelper = EcomDev_Utils_Reflection::getRestrictedPropertyValue($this->payloadHelper, 'inventoryHelper');

        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->payloadHelper, 'inventoryHelper', $this->inventoryHelper);
    }

    protected function tearDown()
    {
        // Restore the original inventory helper instance to the payload helper
        // to prevent the mock form potentially polluting other tests with
        // unexpected mock behavior.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->payloadHelper, 'inventoryHelper', $this->origInventoryHelper);
    }

    /**
     * Mock a quote item with the provided sku, id and fulfillment location data.
     *
     * @para string
     * @para int
     * @param string
     * @param string
     */
    protected function mockQuoteItem($sku, $id, $fulfillmentId, $fulfillmentType)
    {
        $item = $this->getModelMock(
            'sales/quote_item_abstract',
            ['getSku', 'getId', 'getFulfillmentLocationId', 'getFulfillmentLocationType'],
            true
        );
        $item->expects($this->any())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $item->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $item->expects($this->any())
            ->method('getFulfillmentLocationId')
            ->will($this->returnValue($fulfillmentId));
        $item->expects($this->any())
            ->method('getFulfillmentLocationType')
            ->will($this->returnValue($fulfillmentType));
        return $item;
    }

    /**
     * When transferring data from an item to a quantity
     * request item, the item sku, id and fulfillment location
     * data should be transferred to the payload.
     */
    public function testItemToRequestQuantityItem()
    {
        $sku = '45-12345';
        $id = 9;
        $locationId = 'location-id';
        $locationType = 'ISPU';

        $item = $this->mockQuoteItem($sku, $id, $locationId, $locationType);

        $itemPayload = $this->getMockForAbstractClass(
            'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IRequestQuantityItem',
            ['setItemId', 'setLineId', 'setFulfillmentLocationId', 'setFulfillmentLocationType']
        );
        $itemPayload->expects($this->once())
            ->method('setItemId')
            ->with($this->identicalTo($sku))
            ->will($this->returnSelf());
        $itemPayload->expects($this->once())
            ->method('setLineId')
            ->with($this->identicalTo($id))
            ->will($this->returnSelf());
        $itemPayload->expects($this->once())
            ->method('setFulfillmentLocationId')
            ->with($this->identicalTo($locationId))
            ->will($this->returnSelf());
        $itemPayload->expects($this->once())
            ->method('setFulfillmentLocationType')
            ->with($this->identicalTo($locationType))
            ->will($this->returnSelf());

        $this->assertSame(
            $itemPayload,
            $this->payloadHelper->itemToRequestQuantityItem($item, $itemPayload)
        );
    }

    /**
     * When transferring item data to an item payload, the item sku should
     * be set on the payload, and a unique line id generated and set.
     */
    public function testItemToQuantityItem()
    {
        $sku = '45-12345';
        $id = 9;
        $locationId = null;
        $locationType = null;

        $item = $this->mockQuoteItem($sku, $id, $locationId, $locationType);

        $itemPayload = $this->getMockForAbstractClass(
            'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityItem',
            ['setItemId', 'setLineId']
        );
        $itemPayload->expects($this->once())
            ->method('setItemId')
            ->with($this->identicalTo($sku))
            ->will($this->returnSelf());
        $itemPayload->expects($this->once())
            ->method('setLineId')
            ->with($this->identicalTo($id))
            ->will($this->returnSelf());

        $this->assertSame(
            $itemPayload,
            $this->payloadHelper->itemToQuantityItem($item, $itemPayload)
        );
    }

    /**
     * When transferring item data to an item payload, the item sku should
     * be set on the payload, and a unique line id generated and set. Items
     * without an existing item id should not result in an empty line id.
     */
    public function testItemToQuantityItemNoItemId()
    {
        $sku = '45-12345';
        $id = null;
        $locationId = null;
        $locationType = null;

        $item = $this->mockQuoteItem($sku, $id, $locationId, $locationType);

        $itemPayload = $this->getMockForAbstractClass(
            'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityItem',
            ['setItemId', 'setLineId']
        );
        $itemPayload->expects($this->once())
            ->method('setItemId')
            ->with($this->identicalTo($sku))
            ->will($this->returnSelf());
        // Line id will be set to null - item->getId is currently null - so
        // SDK will autogenerate the line id when creating the request payload.
        $itemPayload->expects($this->once())
            ->method('setLineId')
            ->with($this->isNull())
            ->will($this->returnSelf());

        $this->assertSame(
            $itemPayload,
            $this->payloadHelper->itemToQuantityItem($item, $itemPayload)
        );
    }
}
