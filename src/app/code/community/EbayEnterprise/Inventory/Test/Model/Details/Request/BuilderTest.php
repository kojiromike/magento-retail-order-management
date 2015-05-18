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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInStorePickUpItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IShippingItem;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsRequest;

class EbayEnterprise_Inventory_Test_Model_Details_Request_BuilderTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_logContext;
    /** @var IInventoryDetailsRequest */
    protected $_request;
    /** @var IItemIterable */
    protected $_itemIterable;

    public function setUp()
    {
        parent::setUp();
        $this->_logger= $this->getHelperMockBuilder('ebayenterprise_magelog/data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->_logContext = $this->getHelperMockBuilder('ebayenterprise_magelog/context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->_logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));

        // mock the item iterable to create and store
        // item payloads
        $this->_itemIterable = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IItemIterable'
        )
            ->disableOriginalConstructor()
            ->setMethods(['attach', 'getEmptyShippingItem', 'getEmptyInStorePickUpItem'])
            ->getMockForAbstractClass();

        // mock the request to return the iterable mock
        $this->_request = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsRequest'
        )
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMockForAbstractClass();
        $this->_request->expects($this->any())
            ->method('getItems')
            ->will($this->returnValue($this->_itemIterable));

        // avoid having to mock the item helper's dependencies
        $this->_itemHelper = $this->getHelperMock('ebayenterprise_inventory/details_item', [
            'applyQuoteItemData', 'applyMageAddressData'
        ]);
        // prevent magento events from actually triggering
        Mage::app()->disableEvents();
    }

    public function tearDown()
    {
        parent::tearDown();
        // prevent magento events from actually triggering
        Mage::app()->enableEvents();
    }

    public function testAddItemPayloads()
    {
        $items = [Mage::getModel('sales/quote_item')];
        $address = Mage::getModel('sales/quote_address');
        $builder = Mage::getModel('ebayenterprise_inventory/details_request_builder', [
            'request' => $this->_request,
            'item_helper' => $this->_itemHelper,
            'logger' => $this->_logger,
            'log_context' => $this->_logContext,
        ]);

        // the shipping item payload will be created if the ispu payload
        // doesn't get any modifications
        $shippingItem = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\ShippingItem'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->_itemIterable->expects($this->once())
            ->method('getEmptyShippingItem')
            ->will($this->returnValue($shippingItem));

        // the builder is always expected to create the ispu payload
        // but not modify it
        $ispuItem = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\InStorePickUpItem'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $ispuItem->expects($this->never())
            ->method('setItemId');
        $this->_itemIterable->expects($this->once())
            ->method('getEmptyInStorePickUpItem')
            ->will($this->returnValue($ispuItem));

        // the item helper is expected to be used to
        // set item and address data on the shipping item
        // payload
        $this->_itemHelper->expects($this->once())
            ->method('applyQuoteItemData')
            ->with(
                $this->isInstanceOf('\eBayEnterprise\RetailOrderManagement\Payload\Inventory\ShippingItem'),
                $this->isInstanceOf('Mage_Sales_Model_Quote_Item_Abstract')
            );
        $this->_itemHelper->expects($this->once())
            ->method('applyMageAddressData')
            ->with(
                $this->isInstanceOf('\eBayEnterprise\RetailOrderManagement\Payload\Inventory\ShippingItem'),
                $this->isInstanceOf('Mage_Customer_Model_Address_Abstract')
            );

        $builder->addItemPayloads($items, $address);
    }
}
