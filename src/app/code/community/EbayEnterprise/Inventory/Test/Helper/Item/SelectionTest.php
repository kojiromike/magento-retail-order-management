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

class EbayEnterprise_Inventory_Test_Helper_Item_SelectionTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * verify the parent of configurable products is not selected
     */
    public function testAddConfigurable()
    {
        /** @var Mage_Sales_Model_Quote_Item $parent */
        $parent = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
                'id' => 1
            ])
        ]);
        /** @var Mage_Sales_Model_Quote_Item $single */
        $single = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => 'simple'
            ])
        ]);
        $single->setParentItem($parent);

        $selection = Mage::helper('ebayenterprise_inventory/item_selection');
        $chosenItems = $selection->selectFrom([$single, $parent]);
        $this->assertTrue(in_array($parent, $chosenItems));
        $this->assertCount(1, $chosenItems);
    }

    /**
     * verify the parent of bundled products is selected
     */
    public function testAddBundle()
    {
        $parent = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
                'id' => 1
            ])
        ]);
        $single = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => 'simple',
                'parent_item_id' => $parent->getId(),
                'parent_item' => $parent
            ])
        ]);

        $selection = Mage::helper('ebayenterprise_inventory/item_selection');
        $chosenItems = $selection->selectFrom([$single, $parent]);
        $this->assertTrue(in_array($single, $chosenItems));
        $this->assertTrue(in_array($parent, $chosenItems));
        $this->assertCount(2, $chosenItems);
    }

    /**
     * verify the parent of grouped products is not selected
     */
    public function testAddGrouped()
    {
        $parent = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                'id' => 1
            ])
        ]);
        $single = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => 'simple'
            ])
        ]);
        $single->setParentItem($parent);

        $selection = Mage::helper('ebayenterprise_inventory/item_selection');
        $chosenItems = $selection->selectFrom([$single, $parent]);
        $this->assertTrue(in_array($parent, $chosenItems));
        $this->assertCount(1, $chosenItems);
    }

    /**
     * provide singular product types
     * @return array
     */
    public function provideSingularProducttypes()
    {
        return [
            [Mage_Catalog_Model_Product_Type::TYPE_SIMPLE],
            [Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL],
            [Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE],
        ];
    }

    /**
     * verify simple, virtual, and downloadable products are selected
     *
     * @param string
     * @dataProvider provideSingularProducttypes
     */
    public function testAddSingle($productType)
    {
        $single = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => $productType,
            ])
        ]);

        $selection = Mage::helper('ebayenterprise_inventory/item_selection');
        $chosenItems = $selection->selectFrom([$single]);
        $this->assertTrue(in_array($single, $chosenItems));
        $this->assertCount(1, $chosenItems);
    }

    /**
     * verify giftcards are selected
     * @param string
     */
    public function testAddGiftcard()
    {
        $this->requireModel('enterprise_giftcard/catalog_product_type_giftcard');
        $single = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(true),
                'type_id' => Enterprise_GiftCard_Model_Catalog_Product_Type_Giftcard::TYPE_GIFTCARD,
            ])
        ]);
        $selection = Mage::helper('ebayenterprise_inventory/item_selection');
        $chosenItems = $selection->selectFrom([$single]);
        $this->assertTrue(in_array($single, $chosenItems));
        $this->assertCount(1, $chosenItems);
    }

    /**
     * verify
     * - a non manage stock item will not be selected
     */
    public function testNonManageStockItem()
    {
        $single = Mage::getModel('sales/quote_item', [
            'product' => Mage::getModel('catalog/product', [
                'stock_item' => $this->_getStockItemStub(false),
                'type_id' => 'simple',
            ])
        ]);

        $selection = Mage::helper('ebayenterprise_inventory/item_selection');
        $chosenItems = $selection->selectFrom([$single]);
        $this->assertNotTrue(in_array($single, $chosenItems));
        $this->assertCount(0, $chosenItems);
    }

    /**
     * stub a stock item object.
     *
     * @param  bool
     * @return Mage_CatalogInventory_Model_Stock_Item
     */
    protected function _getStockItemStub($manageStockFlag = true)
    {
        // create the mock with defaults but with the constructor disabled
        $stockItem = $this->getModelMock('cataloginventory/stock_item', [], false, [], '', false);
        $stockItem->expects($this->any())
            ->method('getManageStock')
            ->will($this->returnValue($manageStockFlag));
        return $stockItem;
    }
}
