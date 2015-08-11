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

class EbayEnterprise_Inventory_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Scenario: Get all products from a quote item
     * Given a quote item
     * When getting all products from a quote item.
     * Then an array containing all child and parent products is returned.
     */
    public function testGetAllProductsFromItem()
    {
        /** @var Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');
        /** @var Mage_Sales_Model_Quote_Item */
        $item = Mage::getModel('sales/quote_item');

        /** @var EbayEnterprise_Inventory_Helper_Data */
        $helper = $this->getHelperMock('ebayenterprise_inventory', [
            'getAllChildProductsFromItem', 'getAllParentProductFromItem'
        ]);
        $helper->expects($this->once())
            ->method('getAllChildProductsFromItem')
            ->with($this->identicalTo($item))
            ->will($this->returnValue([]));
        $helper->expects($this->once())
            ->method('getAllParentProductFromItem')
            ->with($this->identicalTo($item))
            ->will($this->returnValue([$product]));

        $this->assertSame([$product], EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, 'getAllProductsFromItem', [$item]));
    }

    /**
     * Scenario: Get all child products from a quote item
     * Given a quote item with child products
     * When getting all child products from a quote item with child item.
     * Then an array containing all child products is returned.
     */
    public function testGetAllChildProductsFromItem()
    {
        /** @var Mage_Catalog_Model_Product */
        $childProduct = Mage::getModel('catalog/product');
        /** @var Mage_Sales_Model_Quote_Item */
        $children = Mage::getModel('sales/quote_item', ['product' => $childProduct]);
        /** @var Mage_Sales_Model_Quote_Item */
        $item = Mage::getModel('sales/quote_item');
        $item->addChild($children);
        /** @var EbayEnterprise_Inventory_Helper_Data */
        $helper = Mage::helper('ebayenterprise_inventory');

        $this->assertSame([$childProduct], EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, 'getAllChildProductsFromItem', [$item]));
    }

    /**
     * Scenario: Get current product and parent product from a quote item
     * Given a quote item with parent product
     * When getting both current and parent products from a quote item.
     * Then an array containing both current and parent products is returned.
     */
    public function testGetAllParentProductFromItem()
    {
        /** @var Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product', ['entity_id' => 34]);
        /** @var Mage_Catalog_Model_Product */
        $parentProduct = Mage::getModel('catalog/product', ['entity_id' => 55]);
        /** @var Mage_Sales_Model_Quote_Item */
        $parent = Mage::getModel('sales/quote_item', ['product' => $parentProduct]);
        /** @var Mage_Sales_Model_Quote_Item */
        $item = Mage::getModel('sales/quote_item', ['product' => $product]);
        $item->setParentItem($parent);
        /** @var EbayEnterprise_Inventory_Helper_Data */
        $helper = Mage::helper('ebayenterprise_inventory');

        $this->assertSame([$product, $parentProduct], EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, 'getAllParentProductFromItem', [$item]));
    }

    /**
     * @return array
     */
    public function providerGetStreetDateForBackorderableItem()
    {
        return [
            [true, '2015-09-15', true],
            [true, '2015-09-15', false],
            [false, null, false],
        ];
    }

    /**
     * Mock ebayenterprise_inventory/data helper class
     * @param  bool
     * @param  string
     * @param  bool
     * @param  Mage_Sales_Model_Quote_Item
     * @param  Mage_Catalog_Model_Product[]
     * @param  Varien_Object | null
     * @param  array
     * @param  string
     * @param  string
     * @return Mock_EbayEnterprise_Inventory_Helper_Data
     */
    protected function getMockHelperClass($isUseStreetDateAsEddDate, $streetDate, $isDateInFuture, Mage_Sales_Model_Quote_Item $item, array $products, $result, array $data, $fromDate, $toDate)
    {
        /** @var array */
        $mockMethods = ['getAllProductsFromItem', 'getStreetDateFromProduct', 'isStreetDateInTheFuture', 'getNewVarienObject', 'getNewDateTime', 'getStreetToDate'];
        /** @var EbayEnterprise_Inventory_Helper_Data */
        $helper = $this->getHelperMock('ebayenterprise_inventory/data', $mockMethods, false, [[
            'core_config' => $this->buildCoreConfigRegistry(['isUseStreetDateAsEddDate' => $isUseStreetDateAsEddDate]),
        ]]);
        $a = $isUseStreetDateAsEddDate ? $this->any() : $this->never();
        $b = ($isUseStreetDateAsEddDate && $streetDate) ? $this->any() : $this->never();
        $c = ($isUseStreetDateAsEddDate && $streetDate && $isDateInFuture) ? $this->any() : $this->never();
        $helper->expects($a)
            ->method('getAllProductsFromItem')
            ->with($this->identicalTo($item))
            ->will($this->returnValue($products));
        $helper->expects($a)
            ->method('getStreetDateFromProduct')
            ->with($this->identicalTo($products))
            ->will($this->returnValue($streetDate));
        $helper->expects($b)
            ->method('isStreetDateInTheFuture')
            ->with($this->identicalTo($streetDate))
            ->will($this->returnValue($isDateInFuture));
        $helper->expects($c)
            ->method('getNewVarienObject')
            ->with($this->identicalTo($data))
            ->will($this->returnValue($result));
        $helper->expects($c)
            ->method('getNewDateTime')
            ->with($this->identicalTo($streetDate))
            ->will($this->returnValue($fromDate));
        $helper->expects($c)
            ->method('getStreetToDate')
            ->with($this->identicalTo($streetDate))
            ->will($this->returnValue($toDate));
        return $helper;
    }

    /**
     * Scenario: Get street date for backorderable item in a quote
     * Given a quote item with parent and child products
     * When getting street date for backorderable item in a quote
     * Then a Varien_Object is return containing the from and to date,
     * otherwise, null is return.
     *
     * @param bool
     * @param string
     * @param bool
     * @dataProvider providerGetStreetDateForBackorderableItem
     */
    public function testGetStreetDateForBackorderableItem($isUseStreetDateAsEddDate, $streetDate, $isDateInFuture)
    {
        /** @var DateTime */
        $fromDate = new DateTime();
        /** @var DateTime */
        $toDate = new DateTime();
        /** @var array */
        $data = [
            'delivery_window_from_date' => $fromDate,
            'delivery_window_to_date' => $toDate,
        ];
        /** @var Varien_Object */
        $result = ($isUseStreetDateAsEddDate && $streetDate && $isDateInFuture)
            ? new Varien_Object($data) : null;
        /** @var Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product', ['entity_id' => 34]);
        /** @var Mage_Catalog_Model_Product */
        $parentProduct = Mage::getModel('catalog/product', ['entity_id' => 55]);
        /** @var Mage_Catalog_Model_Product[] */
        $products = [$parentProduct, $product];
        /** @var Mage_Sales_Model_Quote_Item */
        $parent = Mage::getModel('sales/quote_item', ['product' => $parentProduct]);
        /** @var Mage_Sales_Model_Quote_Item */
        $item = Mage::getModel('sales/quote_item', ['product' => $product]);
        $item->setParentItem($parent);
        /** @var EbayEnterprise_Inventory_Helper_Data */
        $helper = $this->getMockHelperClass($isUseStreetDateAsEddDate, $streetDate, $isDateInFuture, $item, $products, $result, $data, $fromDate, $toDate);
        $this->assertSame($result, $helper->getStreetDateForBackorderableItem($item));
    }

    /**
     * @return array
     */
    public function providerGetStreetDateFromProduct()
    {
        /** @var Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product', ['street_date' => '2015-09-15']);
        /** @var Mage_Catalog_Model_Product */
        $parentProduct = Mage::getModel('catalog/product');
        /** @var Mage_Catalog_Model_Product[] */
        $products = [$parentProduct, $product];
        return [
            [$products, '2015-09-15'],
            [[$parentProduct], null],
        ];
    }

    /**
     * Scenario: Get street date an array of products
     * Given an array of products
     * When getting street date from products
     * Then return a street date or null
     *
     * @param Mage_Catalog_Model_Product[]
     * @param string
     * @dataProvider providerGetStreetDateFromProduct
     */
    public function testGetStreetDateFromProduct(array $products, $result)
    {
        /** @var EbayEnterprise_Inventory_Helper_Data */
        $helper = Mage::helper('ebayenterprise_inventory/data');
        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, 'getStreetDateFromProduct', [$products]));
    }
}
