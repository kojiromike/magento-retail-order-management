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

class EbayEnterprise_Eb2cInventory_Test_Model_Feed_Item_InventoriesTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Stub the core session
     */
    public function setUp()
    {
        parent::setUp();
        $this->_replaceSession('core/session');
    }

    /**
     * Test fs tool is set in the constructor when no parameter passed.
     */
    public function testConstructor()
    {
        // mock out some directory config for the inventory feed
        $dirConfig = ['local_directory' => 'some/local', 'event_type' => 'Inventory'];
        $cfg = $this->buildCoreConfigRegistry(['feedDirectoryConfig' => $dirConfig]);
        $invHelper = $this->getHelperMockBuilder('eb2cinventory/data')
            ->disableOriginalConstructor()
            ->setMethods(['getConfigModel'])
            ->getMock();
        $invHelper
            ->expects($this->once())
            ->method('getConfigModel')
            ->willReturn($cfg);
        $this->replaceByMock('helper', 'eb2cinventory', $invHelper);
        // set up mock fs tool which should do nothing
        $fsToolMock = $this->getMock('Varien_Io_File');
        // set mock behavior for only the methods needed to get through the core
        // feed model's constructor (which can't be disabled and still allow for
        // meaningful test assertions)
        $fsToolMock
            ->method('setAllowCreateFolders')
            ->willReturnSelf();
        $fsToolMock
            ->method('open')
            ->willReturn(true);
        $feed = Mage::getModel('eb2cinventory/feed_item_inventories', ['fs_tool' => $fsToolMock]);
        // test the setup
        $this->assertInstanceOf('EbayEnterprise_Eb2cInventory_Model_Feed_Item_Extractor', $feed->getExtractor());
        $this->assertInstanceOf('Mage_CatalogInventory_Model_Stock_Item', $feed->getStockItem());
        $this->assertInstanceOf('Mage_CatalogInventory_Model_Stock_Status', $feed->getStockStatus());
        // make sure the core feed model was instantiated with the proper magic data
        $this->assertSame($dirConfig, $feed->getFeedConfig());
    }

    /**
     * Test processing of the feeds, success and failure
     */
    public function testFeedProcessing()
    {
        $fileDetails = ['local_file' => '/Mage/var/local/file.xml'];
        $invFeed = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(['_getFilesToProcess', 'processFile'])
            ->getMock();
        $invFeed
            ->expects($this->once())
            ->method('_getFilesToProcess')
            ->willReturn([$fileDetails]);
        $invFeed
            ->expects($this->once())
            ->method('processFile')
            ->with($this->identicalTo($fileDetails))
            ->willReturnSelf();
        $this->assertSame(1, $invFeed->processFeeds());
        // when no files processed, event should not be triggered
        $this->assertEventDispatched('inventory_feed_processing_complete');
    }

    /**
     * Test processing of the feeds, success and failure
     */
    public function testFeedProcessingNoFilesToProcess()
    {
        $invFeed = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(['_getFilesToProcess', 'processFile'])
            ->getMock();
        $invFeed
            ->expects($this->once())
            ->method('_getFilesToProcess')
            ->willReturn([]);
        $invFeed
            ->expects($this->never())
            ->method('processFile');
        $this->assertSame(0, $invFeed->processFeeds());
        // when no files processed, event should not be triggered
        $this->assertEventNotDispatched('inventory_feed_processing_complete');
    }

    /**
     * Test processing the DOM for a feed file - should used the instances
     * extractor to extract feed data and pass it on to updateInventories
     */
    public function testProcess()
    {
        $dom = Mage::helper('eb2ccore')->getNewDomDocument();
        $extractedData = [new Varien_Object(['some' => 'data'])];
        $feedItemInventories = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(['updateInventories', 'getExtractor'])
            ->getMock();
        $extractor = $this->getModelMockBuilder('eb2cinventory/feed_item_extractor')
            ->disableOriginalConstructor()
            ->setMethods(['extractInventoryFeed'])
            ->getMock();
        $extractor
            ->expects($this->once())
            ->method('extractInventoryFeed')
            ->with($this->identicalTo($dom))
            ->willReturn($extractedData);
        $feedItemInventories
            ->expects($this->once())
            ->method('getExtractor')
            ->willReturn($extractor);
        $feedItemInventories->expects($this->once())
            ->method('updateInventories')
            ->with($this->identicalTo($extractedData))
            ->willReturnSelf();
        $this->assertSame($feedItemInventories, $feedItemInventories->process($dom));
    }

    /**
     * Test triggering inventory update for a collection of products.
     */
    public function testUpdateInventories()
    {
        $feedItemInventories = $this->getModelMock('eb2cinventory/feed_item_inventories', ['extractSku', 'updateInventory']);
        $feedItemInventories
            ->expects($this->once())
            ->method('extractSku')
            ->with($this->isInstanceOf('Varien_Object'))
            ->willReturn('123');
        $feedItemInventories
            ->expects($this->once())
            ->method('updateInventory')
            ->with($this->isType('string'), $this->isType('integer'))
            ->willReturnSelf();
        $sampleFeed = [new Varien_Object([
            'catalog_id' => 'foo',
            'client_item_id' => 'bar',
            'measurements' => new Varien_Object(['available_quantity' => 3]),
        ])];
        $feedItemInventories->updateInventories($sampleFeed);
    }

    /**
     * Provide product quantities for testing setting the product quantities.
     *
     * @return array
     */
    public function provideProductQuantities()
    {
        return [
            // old quantity, new quantity
            [0, 0],
            [0, 10],
            [10, 0],
            [10, 10],
        ];
    }

    /**
     * Test setting the product quantity.
     *
     * @dataProvider provideProductQuantities
     */
    public function testUpdateProductQuantity($oldQty, $newQty)
    {
        $isChange = ($oldQty !== $newQty);
        $stockItem = $this->getModelMock('cataloginventory/stock_item', ['getQty', 'setQty']);
        $stockItem
            ->method('getQty')
            ->willReturn($oldQty);
        $stockItem
            ->expects($isChange ? $this->once() : $this->never())
            ->method('setQty')
            ->with($this->identicalTo($newQty))
            ->willReturnSelf();
        $this->replaceByMock('model', 'cataloginventory/stock_item', $stockItem);
        $feedItemInventories = Mage::getModel('eb2cinventory/feed_item_inventories');
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $feedItemInventories,
            'updateProductQuantity',
            [$stockItem, $newQty]
        );
    }

    /**
     * Provide stock statuses for testing setting products in and out of stock.
     *
     * @return array
     */
    public function provideInStockStatuses()
    {
        // minimum quantity, new quantity, was in stock, is a change
        return [
            // No Change
            [0, 0, false, false], // still out of stock
            [10, 5, false, false], // below minimum quantity
            [5, 10, true, false], // still in stock
            // Going out of stock
            [0, 0, true, true],
            [10, 5, true, true],
            // Going in stock
            [5, 10, false, true],
        ];
    }

    /**
     * Test marking products in stock and out of stock when the quantity changes.
     *
     * @param  int $minQty    Min qty to be in stock
     * @param  int $updateQty Qty item is being updated to
     * @param  int $isInStock Should be considered in stock
     * @param  bool
     * @dataProvider provideInStockStatuses
     */
    public function testUpdateItemIsInStock($minQty, $updateQty, $wasInStock, $isChange)
    {
        $stockItem = $this->getModelMockBuilder('cataloginventory/stock_item')
            ->setMethods(['getMinQty', 'setIsInStock', 'getIsInStock'])
            ->getMock();
        $stockItem
            ->method('getMinQty')
            ->willReturn($minQty);
        $stockItem
            ->method('getIsInStock')
            ->willReturn($wasInStock);
        $stockItem
            ->expects($isChange ? $this->once() : $this->never())
            ->method('setIsInStock')
            ->with($this->identicalTo(!$wasInStock))
            ->willReturnSelf();
        $feedItemInventories = Mage::getModel('eb2cinventory/feed_item_inventories');
        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $feedItemInventories,
            'updateProductInStockStatus',
            [$stockItem, $updateQty]
        );
        $this->assertSame($isChange, $result);
    }

    /**
     * Test extracting the sku from the xml.
     */
    public function testExtractSku()
    {
        $feedItemInventories = Mage::getModel('eb2cinventory/feed_item_inventories');
        $this->assertSame('foo-bar', EcomDev_Utils_Reflection::invokeRestrictedMethod($feedItemInventories, 'extractSku', [new Varien_Object([
            'catalog_id' => 'foo',
            'item_id' => new Varien_Object(['client_item_id' => 'bar']),
        ])])); // LISP
    }

    /**
     * Test that inventory that can be updated gets updated
     *
     * @dataProvider provideTrueFalse
     */
    public function testUpdateInventory($exists)
    {
        $product = $this->getModelMock('catalog/product', ['getIdBySku']);
        $product
            ->method('getIdBySku')
            ->willReturn($exists ? 'an-id' : false);
        $this->replaceByMock('model', 'catalog/product', $product);
        $feedItemInventories = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(['updateProductInventory', 'handleSkuNotFound'])
            ->getMock();
        $feedItemInventories
            ->expects($exists ? $this->once() : $this->never())
            ->method('updateProductInventory')
            ->willReturnSelf();
        $feedItemInventories
            ->method('handleSkuNotFound')
            ->willReturnSelf();
        $sku = 'a-sku';
        $quantity = 123;
        EcomDev_Utils_Reflection::invokeRestrictedMethod($feedItemInventories, 'updateInventory', [$sku, $quantity]);
    }

    /**
     * Test that inventory for a known product that can be updated gets updated.
     *
     * @dataProvider provideTrueFalse
     */
    public function testUpdateProductInventory($managed)
    {
        $stockItem = $this->getModelMock('cataloginventory/stock_item', ['loadByProduct', 'getManageStock']);
        $stockItem
            ->method('loadByProduct')
            ->willReturnSelf();
        $stockItem
            ->method('getManageStock')
            ->willReturn($managed);
        $this->replaceByMock('model', 'cataloginventory/stock_item', $stockItem);
        $feedItemInventories = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(['updateManagedStockInventory', 'handleNotManagedStock'])
            ->getMock();
        $feedItemInventories
            ->expects($managed ? $this->once() : $this->never())
            ->method('updateManagedStockInventory')
            ->willReturnSelf();
        $feedItemInventories
            ->method('handleNotManagedStock')
            ->willReturnSelf();
        $id = 'an-id';
        $quantity = 123;
        EcomDev_Utils_Reflection::invokeRestrictedMethod($feedItemInventories, 'updateProductInventory', [$id, $quantity]);
    }

    /**
     * Provide a pair of true/false values to determine if a stock item should be saved.
     */
    public function provideStockChanges()
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
    }

    /**
     * Test that inventory for known managed-stock that has changed gets updated.
     *
     * @dataProvider provideStockChanges
     */
    public function testUpdateManagedStockInventory($quantityChanged, $stockStatusChanged)
    {
        $stockItem = $this->getModelMock('cataloginventory/stock_item', ['save']);
        $stockItem
            ->expects(($quantityChanged || $stockStatusChanged) ? $this->once() : $this->never())
            ->method('save')
            ->willReturnSelf();
        $feedItemInventories = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(['updateProductQuantity', 'updateProductInStockStatus'])
            ->getMock();
        $feedItemInventories
            ->method('updateProductQuantity')
            ->willReturn($quantityChanged);
        $feedItemInventories
            ->method('updateProductInStockStatus')
            ->willReturn($stockStatusChanged);
        $quantity = 123;
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $feedItemInventories,
            'updateManagedStockInventory',
            [$stockItem, $quantity]
        );
    }
}
