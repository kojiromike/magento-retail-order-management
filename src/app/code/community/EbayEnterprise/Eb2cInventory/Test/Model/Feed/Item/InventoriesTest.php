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
    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }

    /**
     * Test fs tool is set in the constructor when no parameter passed.
     */
    public function testConstructor()
    {
        // mock out some directory config for the inventory feed
        $dirConfig = array('local_directory' => 'some/local', 'event_type' => 'Inventory');
        $cfg = $this->buildCoreConfigRegistry(array('feedDirectoryConfig' => $dirConfig));
        $invHelper = $this->getHelperMockBuilder('eb2cinventory/data')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigModel'))
            ->getMock();
        $invHelper->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($cfg));
        $this->replaceByMock('helper', 'eb2cinventory', $invHelper);

        // set up mock fs tool which should do nothing
        $fsToolMock = $this->getMock('Varien_Io_File');
        // set mock behavior for only the methods needed to get through the core
        // feed model's constructor (which can't be disabled and still allow for
        // meaningful test assertions)
        $fsToolMock->expects($this->any())->method('setAllowCreateFolders')->will($this->returnSelf());
        $fsToolMock->expects($this->any())->method('open')->will($this->returnValue(true));

        $feed = Mage::getModel('eb2cinventory/feed_item_inventories', array('fs_tool' => $fsToolMock));

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
        $fileDetails = array('local_file' => '/Mage/var/local/file.xml');
        $invFeed = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(array('_getFilesToProcess', 'processFile'))
            ->getMock();
        $invFeed->expects($this->once())
            ->method('_getFilesToProcess')
            ->will($this->returnValue(array($fileDetails)));
        $invFeed->expects($this->once())
            ->method('processFile')
            ->with($this->identicalTo($fileDetails))
            ->will($this->returnSelf());

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
            ->setMethods(array('_getFilesToProcess', 'processFile'))
            ->getMock();
        $invFeed->expects($this->once())
            ->method('_getFilesToProcess')
            ->will($this->returnValue(array()));
        $invFeed->expects($this->never())
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
        $extractedData = array(new Varien_Object(array('some' => 'data')));

        $fii = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(array('updateInventories', 'getExtractor'))
            ->getMock();
        $extractor = $this->getModelMockBuilder('eb2cinventory/feed_item_extractor')
            ->disableOriginalConstructor()
            ->setMethods(array('extractInventoryFeed'))
            ->getMock();

        $extractor->expects($this->once())
            ->method('extractInventoryFeed')
            ->with($this->identicalTo($dom))
            ->will($this->returnValue($extractedData));
        $fii->expects($this->once())
            ->method('getExtractor')
            ->will($this->returnValue($extractor));
        $fii->expects($this->once())
            ->method('updateInventories')
            ->with($this->identicalTo($extractedData))
            ->will($this->returnSelf());

        $this->assertSame($fii, $fii->process($dom));
    }
    /**
     */
    public function testUpdateInventories()
    {
        $fii = $this->getModelMock('eb2cinventory/feed_item_inventories', array('_extractSku', '_updateInventory'));
        $fii
            ->expects($this->once())
            ->method('_extractSku')
            ->with($this->isInstanceOf('Varien_Object'))
            ->will($this->returnValue('123'));
        $fii
            ->expects($this->once())
            ->method('_updateInventory')
            ->with($this->isType('string'), $this->isType('integer'))
            ->will($this->returnSelf());
        $sampleFeed = array(new Varien_Object(array(
            'catalog_id' => 'foo',
            'client_item_id' => 'bar',
            'measurements' => new Varien_Object(array('available_quantity' => 3)),
        )));
        $fii->updateInventories($sampleFeed); // Just verify the right inner methods are called.
    }
    /**
     */
    public function testSetProdQty()
    {
        $id = 23;
        $qty = 42;
        $stockItem = $this->getModelMock('cataloginventory/stock_item', ['loadByProduct', 'setQty', 'getQty', 'setIsInStock', 'save', 'getManageStock']);
        $stockItem
            ->expects($this->once())
            ->method('loadByProduct')
            ->with($this->identicalTo($id))
            ->will($this->returnSelf());
        $stockItem
            ->expects($this->once())
            ->method('getManageStock')
            ->will($this->returnValue(true));
        $stockItem
            ->expects($this->never())
            ->method('setQty')
            ->with($this->identicalTo($qty))
            ->will($this->returnSelf());
        $stockItem
            ->expects($this->once())
            ->method('getQty')
            ->will($this->returnValue($qty));
        $stockItem
            ->expects($this->once())
            ->method('save')
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'cataloginventory/stock_item', $stockItem);
        $fii = $this->getModelMock('eb2cinventory/feed_item_inventories', ['_updateItemIsInStock']);
        $fii
            ->expects($this->once())
            ->method('_updateItemIsInStock')
            ->with($this->identicalTo($stockItem), $this->identicalTo($qty))
            ->will($this->returnSelf());
        EcomDev_Utils_Reflection::invokeRestrictedMethod($fii, '_setProdQty', [$id, $qty]); // Just verify the right inner methods are called.
    }
    /**
     * Data provider for testUpdateItemIsInStock, gives quantities where update is greater than,
     * less than and equal to the min qty to be in-stock. Also provides whether such quantities
     * should result in a product that is in or out of stock
     * @return array Arrays of arguments to be passed to testUpdateItemIsInStock
     */
    public function providerTestUpdateItemIsInStock()
    {
        return [
            [5, 10, true, true],
            [0, 0, false, false],
            [10, 5, false, false],
        ];
    }
    /**
     * When the update quantity is greater than the min quantity to be considered in stock,
     * the stock items should be considered to be in stock
     * @param  int $minQty    Min qty to be in stock
     * @param  int $updateQty Qty item is being updated to
     * @param  int $isInStock Should be considered in stock
     * @param  bool
     * @mock Mage_CatalogInventory_Model_Stock_Item::getMinQty return the expected min qty to be in stock
     * @mock Mage_CatalogInventory_Model_Stock_Item::setIsInStock ensure stock item properly set as in or out of stock
     * @mock EbayEnterprise_Eb2cInventory_Model_Feed_Item_Inventories mocked to disable constructor, preventing unwanted side-effects & coverage
     * @dataProvider providerTestUpdateItemIsInStock
     */
    public function testUpdateItemIsInStock($minQty, $updateQty, $isInStock, $result)
    {
        $stockItem = $this->getModelMock(
            'cataloginventory/stock_item',
            array('getMinQty', 'setIsInStock', 'getIsInStock')
        );
        $stockItem
            ->expects($this->any())
            ->method('getMinQty')
            ->will($this->returnValue($minQty));
        $stockItem
            ->expects($this->any())
            ->method('setIsInStock')
            ->with($this->identicalTo($isInStock))
            ->will($this->returnSelf());
        $stockItem
            ->expects($this->once())
            ->method('getIsInStock')
            ->will($this->returnValue(false));
        $fii = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
            ->setMethods(null)
            ->getMock();
        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($fii, '_updateItemIsInStock', [$stockItem, $updateQty]));
    }
    /**
     */
    public function testExtractSku()
    {
        $fii = Mage::getModel('eb2cinventory/feed_item_inventories');
        $this->assertSame('foo-bar', EcomDev_Utils_Reflection::invokeRestrictedMethod($fii, '_extractSku', array(new Varien_Object(array(
            'catalog_id' => 'foo',
            'item_id' => new Varien_Object(array('client_item_id' => 'bar')),
        ))))); // LISP
    }
    /**
     */
    public function testUpdateInventory()
    {
        $prod = $this->getModelMock('catalog/product', array('getIdBySku'));
        $prod
            ->expects($this->once())
            ->method('getIdBySku')
            ->with($this->isType('string'))
            ->will($this->returnValue(123));
        $this->replaceByMock('model', 'catalog/product', $prod);
        $fii = $this->getModelMock('eb2cinventory/feed_item_inventories', array('_setProdQty'));
        $fii
            ->expects($this->once())
            ->method('_setProdQty')
            ->with($this->equalTo(123), $this->equalTo(1))
            ->will($this->returnSelf());
        EcomDev_Utils_Reflection::invokeRestrictedMethod($fii, '_updateInventory', array('45-987', 1)); // Just verify the right inner methods are called.
    }
}
