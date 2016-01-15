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

class EbayEnterprise_Catalog_Test_Helper_Map_StockTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Scenario: Get the default stock map
     * When getting the default stock map
     * Then check if the stock map doesn't already exists in the class property
     * EbayEnterprise_Catalog_Helper_Map_Stock::$stockMap, if not
     * Then continue to call the catalog helper config model and get the config map by
     * the stock path.
     */
    public function testGetDefaultStockMap()
    {
        $mapData = [
            'advanceOrderOpen' => Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY,
            'advanceOrderLimited' => Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY,
        ];

        /** @var Mock_EbayEnterprise_Eb2cCore_Model_Config_Registry */
        $config = $this->getModelMock('eb2ccore/config_registry', ['getConfigData']);
        $config->expects($this->once())
            ->method('getConfigData')
            ->with($this->identicalTo(EbayEnterprise_Catalog_Helper_Map_Stock::STOCK_CONFIG_PATH))
            ->will($this->returnValue($mapData));

        /** @var Mock_EbayEnterprise_Catalog_Helper_Data */
        $catalogHelper = $this->getHelperMock('ebayenterprise_catalog/data', ['getConfigModel']);
        $catalogHelper->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($config));

        /** @var EbayEnterprise_Catalog_Helper_Map_Stock */
        $stock = $this->getHelperMock('ebayenterprise_catalog/map_stock', ['foo'], false, [[
            'catalog_helper' => $catalogHelper,
        ]]);

        $this->assertSame($mapData, EcomDev_Utils_Reflection::invokeRestrictedMethod($stock, 'getDefaultStockMap', []));
    }

    /**
     * @return array
     */
    public function providerExtractStockData()
    {
        return [
            [7],
            [0],
        ];
    }

    /**
     * Scenario: Extract stock data
     * Given a NodeList object containing the type of stock option
     * And a catalog/product object
     * When extracting stock data
     * Then extract the stock option value from the passed in node list.
     * Then, store product ID to a local variable
     * Then, build stock data array
     * Then, if the product doesn't have an id, then simply set the build stock data on the product object
     * Otherwise, continue to save the stock item and return null.
     *
     * @param int
     * @dataProvider providerExtractStockData
     */
    public function testExtractStockData($productId)
    {
        $nodes = new DOMNodeList();
        $value = 'advanceOrderOpen';
        $stockData = [];
        /** @var Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product', ['entity_id' => $productId]);

        /** @var Mock_EbayEnterprise_Eb2cCore_Helper_Data */
        $coreHelper = $this->getHelperMock('eb2ccore/data', ['extractNodeVal']);
        $coreHelper->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodes))
            ->will($this->returnValue($value));

        /** @var EbayEnterprise_Catalog_Helper_Map_Stock */
        $stock = $this->getHelperMock('ebayenterprise_catalog/map_stock', ['buildStockData', 'saveStockItem'], false, [[
            'core_helper' => $coreHelper,
        ]]);
        $stock->expects($this->once())
            ->method('buildStockData')
            ->with($this->identicalTo($productId), $this->identicalTo($value))
            ->will($this->returnValue($stockData));
        $stock->expects($productId ? $this->once() : $this->never())
            ->method('saveStockItem')
            ->with($this->identicalTo($stockData), $this->identicalTo($productId))
            ->will($this->returnSelf());

        $this->assertNull($stock->extractStockData($nodes, $product));
        // Proving that whenever a product doesn't already exists
        // meaning that its product id is 0, then a new magic data attribute
        // will be set to it.
        $this->assertSame(!$productId, $product->hasNewProductStockData());
    }

    /**
     * Scenario: save stock item
     * Given an array of stock data
     * And product id
     * When saving stock item
     * Then, get new stock item model from the factory,
     * Then, load the stock item model by product id
     * Then, add the passed in stock data to the stock item model
     * Then, finally save the save item model
     */
    public function testSaveStockItem()
    {
        $productId = 8;
        $stockData = [];

        /** @var Mock_Mage_CatalogInventory_Model_Stock_Item */
        $item = $this->getModelMock('cataloginventory/stock_item', ['loadByProduct', 'addData', 'save']);
        $item->expects($this->once())
            ->method('loadByProduct')
            ->with($this->identicalTo($productId))
            ->will($this->returnSelf());
        $item->expects($this->once())
            ->method('addData')
            ->with($this->identicalTo($stockData))
            ->will($this->returnSelf());
        $item->expects($this->once())
            ->method('save')
            ->will($this->returnSelf());

        /** @var EbayEnterprise_Catalog_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_catalog/factory', ['getNewStockItemModel']);
        $factory->expects($this->once())
            ->method('getNewStockItemModel')
            ->will($this->returnValue($item));
        $stock = $this->getHelperMock('ebayenterprise_catalog/map_stock', ['foo'], false, [[
            'factory' => $factory,
        ]]);

        $this->assertSame($stock, $stock->saveStockItem($stockData, $productId));
    }

    /**
     * @return array
     */
    public function providerGetBackOrderData()
    {
        return [
            ['unknowKeys', Mage_CatalogInventory_Model_Stock::BACKORDERS_NO],
            ['advanceOrderOpen', Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY],
            ['advanceOrderLimited', Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY],
        ];
    }

    /**
     * Scenario: Get backorder data
     * Given extracted string value from feed
     * When getting backorder data
     * Then, check if the passed in extracted string value from feed exists in the stock map
     * Then, if so when return the value it mapped to
     * Then, if not so return the default no backorder value
     *
     * @param string
     * @param int
     * @dataProvider providerGetBackOrderData
     */
    public function testGetBackOrderData($value, $expected)
    {
        $mapData = [
            'advanceOrderOpen' => Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY,
            'advanceOrderLimited' => Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY,
        ];
        $stock = $this->getHelperMock('ebayenterprise_catalog/map_stock', ['foo'], false, [[
            'stock_map' => $mapData,
        ]]);

        $this->assertSame($expected, EcomDev_Utils_Reflection::invokeRestrictedMethod($stock, 'getBackOrderData', [$value]));
    }

    /**
     * @return array
     */
    public function providerBuildStockData()
    {
        return [
            [7, 'unknowKeys', [
                'product_id' => 7,
                'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
                'backorders' =>  Mage_CatalogInventory_Model_Stock::BACKORDERS_NO,
                'use_config_backorders' => false,
            ]],
            [9, 'advanceOrderOpen', [
                'product_id' => 9,
                'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
                'backorders' =>  Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY,
                'use_config_backorders' => false,
                'is_in_stock' => true,
            ]],
            [17, 'advanceOrderLimited', [
                'product_id' => 17,
                'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
                'backorders' =>  Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY,
                'use_config_backorders' => false,
                'is_in_stock' => true,
            ]],
        ];
    }

    /**
     * Scenario: Build stock data
     * Given a product id
     * And an extracted string value from feed
     * When building stock data
     * Then, get the magento backorder data according to the passed in string value from feed
     * Then, build an array with the proper stock data using the passed in product id,
     * default stock id, magento backorder, use config set to false.
     * Then, merge the is in stock data array based on the Magento backorder with the build stock array data
     * Then, finally return the stock array.
     *
     * @param int
     * @param string
     * @param array
     * @dataProvider providerBuildStockData
     */
    public function testBuildStockData($productId, $value, array $expected)
    {
        $stock = $this->getHelperMock('ebayenterprise_catalog/map_stock', ['getBackOrderData', 'getIsInStockData']);
        $stock->expects($this->once())
            ->method('getBackOrderData')
            ->will($this->returnValueMap([
                ['unknowKeys', Mage_CatalogInventory_Model_Stock::BACKORDERS_NO],
                ['advanceOrderOpen', Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY],
                ['advanceOrderLimited', Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY],
            ]));
        $stock->expects($this->once())
            ->method('getIsInStockData')
            ->will($this->returnValueMap([
                [Mage_CatalogInventory_Model_Stock::BACKORDERS_NO, []],
                [Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY, ['is_in_stock' => true]],
                [Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY, ['is_in_stock' => true]],
            ]));
        $this->assertSame($expected, EcomDev_Utils_Reflection::invokeRestrictedMethod($stock, 'buildStockData', [$productId, $value]));
    }

    /**
     * @return array
     */
    public function providerGetIsInStockData()
    {
        return [
            [Mage_CatalogInventory_Model_Stock::BACKORDERS_NO, []],
            [Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY, ['is_in_stock' => true]],
            [Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY, ['is_in_stock' => true]]
        ];
    }

    /**
     * Scenario: Get is in stock data
     * Given a backorder integer value
     * When getting is in stock data
     * Then, return an array with key is in stock map to true if
     * the passed in backorder integer value is greater than the no backorder
     * Then, return an empty array when the passed in backorder integer value is not greater than the no backorder
     *
     * @param int
     * @param array
     * @dataProvider providerGetIsInStockData
     */
    public function testGetIsInStockData($backorder, array $expected)
    {
        $stock = Mage::helper('ebayenterprise_catalog/map_stock');
        $this->assertSame($expected, EcomDev_Utils_Reflection::invokeRestrictedMethod($stock, 'getIsInStockData', [$backorder]));
    }
}
