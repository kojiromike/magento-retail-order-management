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
 * Assumptions: if a quantity operation reports an item is available, but
 * then is reported unavailable in the following detail operation, a
 * following quantity operation will report the item is unavailable
 */
class EbayEnterprise_Inventory_Test_Model_Details_ServiceTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Inventory_Helper_Details_Factory */
    protected $factory;

    public function setUp()
    {
        parent::setUp();
        $this->logger= $this->getHelperMockBuilder('ebayenterprise_magelog/data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext = $this->getHelperMockBuilder('ebayenterprise_magelog/context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));

        // mock the factory
        $this->factory = $this->getHelperMock('ebayenterprise_inventory/details_factory');
    }

    public function testGetDetailsForItem()
    {
        // stub a detail model to return from the result
        $unavailable = Mage::getModel(
            'ebayenterprise_inventory/details_item',
            ['item_id' => 5, 'sku' => 'foo']
        );
        // mock the details model and the factory method to
        // create it
        $detailsModel = $this->getModelMockBuilder('ebayenterprise_inventory/details')
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory->expects($this->once())
            ->method('createDetailsModel')
            ->will($this->returnValue($detailsModel));

        // mock up the result and mock the details model
        // to get back
        $result = $this->getModelMockBuilder('ebayenterprise_inventory/details_result')
            ->disableOriginalConstructor()
            ->setMethods(['lookupDetailsByItemId'])
            ->getMock();
        $result->expects($this->once())
            ->method('lookupDetailsByItemId')
            ->with($this->isType('int'))
            ->will($this->returnValue($unavailable));
        $detailsModel->expects($this->any())
            ->method('fetch')
            ->with($this->isInstanceOf('Mage_Sales_Model_Quote'))
            ->will($this->returnValue($result));

        // start the test
        $detailService = Mage::getModel('ebayenterprise_inventory/details_service', [
            'factory' => $this->factory,
            'logger' => $this->logger,
            'log_context' => $this->logContext]);

        $quote = $this->getModelMock('sales/quote');
        $item = $this->getModelMock('sales/quote_item', ['getQuote', 'getId', 'getProduct']);
        $item->expects($this->once())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $item->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));
        $product = Mage::getModel('catalog/product', ['stock_item' => Mage::getModel('catalogInventory/stock_item', [
            'backorders' => Mage_CatalogInventory_Model_Stock::BACKORDERS_NO,
            'qty' => 1,
        ])]);
        $item->expects($this->once())
            ->method('getProduct')
            ->will($this->returnValue($product));
        $detail = $detailService->getDetailsForItem($item);
        $this->assertInstanceOf('EbayEnterprise_Inventory_Model_Details_Item', $detail);
    }


    /**
     * parent items will be skipped.
     */
    public function testHandleUnavailableItemsParentItems()
    {
        $item = $this->getModelMock('sales/quote_item', ['getErrorInfos', 'getHasChildren', 'getParent']);
        $quote = $this->getModelMockBuilder('sales/quote')
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMock();
        $quote->expects($this->any())
            ->method('getAllItems')
            ->will($this->returnValue([$item]));
        // if the item is a parent item check to see if there are child
        // items. note that this only works while the quote is still loaded.
        $item->expects($this->once())
            ->method('getHasChildren')
            ->will($this->returnValue(true));
        $item->expects($this->never())
            ->method('getErrorInfos');
        $detailService = Mage::getModel('ebayenterprise_inventory/details_service', [
            'factory' => $this->factory,
            'logger' => $this->logger,
            'log_context' => $this->logContext]);
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $detailService,
            'handleUnavailableItems',
            [$quote]
        );
    }

    /**
     * if items are found with no error from the quantity service but are
     * reported unavailable by the detail service, throw an exception to
     * return to the cart.
     *
     * @expectedException EbayEnterprise_Inventory_Exception_Details_Unavailable_Exception
     */
    public function testHandleUnavailableItems()
    {
        $unavailable = Mage::getModel(
            'ebayenterprise_inventory/details_item',
            ['item_id' => 5, 'sku' => 'foo']
        );
        $statusList = $this->getModelMock('sales/status_list', ['getItems']);
        $item = $this->getModelMock('sales/quote_item', ['getErrorInfos', 'getHasChildren', 'getParent']);
        $quote = $this->getModelMockBuilder('sales/quote')
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMock();
        $detailService = $this->getModelMockBuilder('ebayenterprise_inventory/details_service')
            ->setMethods(['getDetailsForItem'])
            ->setConstructorArgs([[
                'factory' => $this->factory,
                'logger' => $this->logger,
                'log_context' => $this->logContext
            ]])
            ->getMock();

        $quote->expects($this->any())
            ->method('getAllItems')
            ->will($this->returnValue([$item]));

        // ensure the service deems the item unavailable
        $detailService->expects($this->any())
            ->method('getDetailsForItem')
            ->will($this->returnValue($unavailable));

        // get the error info to see if the quantity service already
        // marked the item as unavailable.
        $item->expects($this->once())
            ->method('getErrorInfos')
            ->will($this->returnValue($statusList));
        $statusList->expects($this->any())
            ->method('getItems')
            ->will($this->returnValue([
                [
                    'origin' => 'SOMEOTHERSOURCE',
                    'code' => 5,
                ],
            ]));
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $detailService,
            'handleUnavailableItems',
            [$quote]
        );
    }

    /**
     * when a previous error exists, return
     */
    public function testHandleUnavailableItemsPreviousError()
    {
        $unavailable = Mage::getModel(
            'ebayenterprise_inventory/details_item',
            ['item_id' => 5, 'sku' => 'foo']
        );
        $statusList = $this->getModelMock('sales/status_list');
        $quote = $this->getModelMockBuilder('sales/quote')
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMock();
        $detailService = $this->getModelMockBuilder('ebayenterprise_inventory/details_service')
            ->setMethods(['getDetailsForItem'])
            ->setConstructorArgs([[
                'factory' => $this->factory,
                'logger' => $this->logger,
                'log_context' => $this->logContext
            ]])
            ->getMock();
        // getHasChildren needs to be falsey so that the item doesn't
        // get skipped
        $item = $this->getModelMock('sales/quote_item', ['getErrorInfos', 'getHasChildren', 'getParent']);

        $quote->expects($this->any())
            ->method('getAllItems')
            ->will($this->returnValue([$item]));

        // ensure the service deems the item unavailable
        $detailService->expects($this->any())
            ->method('getDetailsForItem')
            ->will($this->returnValue($unavailable));

        // go though the item's error info to see if the quantity
        // service already set an error on it.
        $item->expects($this->once())
            ->method('getErrorInfos')
            ->will($this->returnValue($statusList));
        $statusList->expects($this->once())
            ->method('getItems')
            ->will($this->returnValue([
                [
                    'origin' => EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE,
                    'code' => EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE,
                ],
                [
                    'origin' => 'SOMEOTHERSOURCE',
                    'code' => 5,
                ],
            ]));
        // do not throw an exception since the quantity services
        // already marked the item as being unavailable
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $detailService,
            'handleUnavailableItems',
            [$quote]
        );
    }

    /**
     * @return array
     */
    public function providerIsItemAllowInventoryDetail()
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item');
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product', ['stock_item' => $stockItem]);
        /** @var int $parentItemId */
        $parentItemId = 550;
        /** @var int $childItemId */
        $childItemId = 551;
        /** @var Mage_Sales_Model_Quote_Item $childItem */
        $childItem = Mage::getModel('sales/quote_item', ['item_id' => $childItemId, 'product' => $product]);
        /** @var Mage_Sales_Model_Quote_Item $parentItem */
        $parentItem = Mage::getModel('sales/quote_item', ['item_id' => $parentItemId,'product' => $product]);
        $parentItem->addChild($childItem);

        return [
            [$parentItem, true, true],
            [$parentItem, false, false],
            [$childItem, true, true],
        ];
    }

    /**
     * Test that the method ebayenterprise_inventory/details_service::isItemAllowInventoryDetail()
     * when invoked will be given an instance of type Mage_Sales_Model_Quote_Item. It will
     * return true if either the child item or the parent in quote item is allowed inventory detail call,
     * otherwise it will return false.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @param bool
     * @param bool
     * @dataProvider providerIsItemAllowInventoryDetail
     */
    public function testIsItemAllowInventoryDetail(Mage_Sales_Model_Quote_Item_Abstract $item, $isAllowedInventoryDetail, $result)
    {
        $detailService = $this->getModelMock('ebayenterprise_inventory/details_service', ['isAllowedInventoryDetail'], false, [[
            'factory' => $this->factory,
            'logger' => $this->logger,
            'log_context' => $this->logContext
        ]]);
        $detailService->expects($this->once())
            ->method('isAllowedInventoryDetail')
            ->with($this->isInstanceOf('Mage_CatalogInventory_Model_Stock_Item'))
            ->will($this->returnValue($isAllowedInventoryDetail));

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($detailService, 'isItemAllowInventoryDetail', [$item]));
    }

    /**
     * @return array
     */
    public function providerGetQuoteItemId()
    {
        /** @var int $parentItemId */
        $parentItemId = 370;
        /** @var int $childItemId */
        $childItemId = 371;
        /** @var Mage_Sales_Model_Quote_Item $childItem */
        $childItem = Mage::getModel('sales/quote_item', ['item_id' => $childItemId]);
        /** @var Mage_Sales_Model_Quote_Item $parentItem */
        $parentItem = Mage::getModel('sales/quote_item', ['item_id' => $parentItemId]);
        $parentItem->addChild($childItem);

        return [
            [$parentItem, $childItemId],
            [$childItem, $childItemId],
        ];
    }

    /**
     * Test that the method ebayenterprise_inventory/details_service::getQuoteItemId()
     * when invoked will be given an instance of type Mage_Sales_Model_Quote_Item. It will
     * return child quote item id if the passed in quote item instance has a child, otherwise
     * if the passed in quote item instance don't have a child it will simply return the
     * quote item id of the passed in quote item instance.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @param int
     * @dataProvider providerGetQuoteItemId
     */
    public function testGetQuoteItemId(Mage_Sales_Model_Quote_Item_Abstract $item, $itemId)
    {

        $detailService = $this->getModelMock('ebayenterprise_inventory/details_service', ['foo'], false, [[
            'factory' => $this->factory,
            'logger' => $this->logger,
            'log_context' => $this->logContext
        ]]);

        $this->assertSame($itemId, EcomDev_Utils_Reflection::invokeRestrictedMethod($detailService, 'getQuoteItemId', [$item]));
    }
}
