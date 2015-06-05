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

class EbayEnterprise_Eb2cCore_Test_Helper_Quote_ItemTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Data provider for testIsItemInventoried method. This method sends several variants of Mage_Sales_Model_Quote_Item(s)
     * to the test. Each variant represents an item that should (or should not) be subject to ROM inventory processing.
     * @return array Argument set consisting of a Mage_Sales_Model_Quote_Item, and the boolean expected when that item is sent
     *  to the isItemInventoried method.
     */
    public function providerIsItemInventoried()
    {
        $inventoriedProduct = Mage::getModel(
            // Should be inventoried:
            'catalog/product',
            [
                'stock_item' => Mage::getModel('cataloginventory/stock_item', [
                    'backorders' => Mage_CatalogInventory_Model_Stock::BACKORDERS_NO,
                    'manage_stock' => 1,
                ]),
            ]
        );
        $nonInventoriedProduct = Mage::getModel(
            // Should be inventoried; Backorders are OK
            'catalog/product',
            [
                'stock_item' => Mage::getModel('cataloginventory/stock_item', [
                    'backorders'   => Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY,
                    'manage_stock' => '1',
                ]),
            ]
        );
        $overrideInventoriedProduct = Mage::getModel(
            // Should not be inventoried, because manage_stock was set to false.
            'catalog/product',
            [
                'stock_item' => Mage::getModel('cataloginventory/stock_item', [
                    'backorders'   => Mage_CatalogInventory_Model_Stock::BACKORDERS_NO,
                    'manage_stock' => '0',
                ]),
            ]
        );

        $inventoriedSku         = Mage::getModel('sales/quote_item', ['product' => $inventoriedProduct]);
        $nonInventoriedSku      = Mage::getModel('sales/quote_item', ['product' => $nonInventoriedProduct]);
        $overrideInventoriedSku = Mage::getModel('sales/quote_item', ['product' => $overrideInventoriedProduct]);
        $inventoriedChildSku    = Mage::getModel('sales/quote_item', ['product' => $inventoriedProduct, 'parent_item_id' => 2,]);

        $childInventoriesParentSku = Mage::getModel('sales/quote_item', ['product' => $nonInventoriedProduct]);
        $childInventoriesParentSku->addChild($inventoriedChildSku);

        return [
            [$inventoriedSku, true],            // Set by ROM to be inventoried
            [$nonInventoriedSku, true],         // Set by ROM to not be inventoried
            [$overrideInventoriedSku, false],   // Set by ROM to be inventoried, but overridden by manage-stock flag
            [$inventoriedChildSku, false],      // Set by ROM to be inventoried, but has parent, so no check
            [$childInventoriesParentSku, true], // Set by ROM to not be inventoried, but child forces it.
        ];
    }
    /**
     * Test the isItemInventoried method.
     * @param  Mage_Sales_Model_Quote_Item $item
     * @param  bool $isInventoried whether the item is expected to be subject to ROM inventory processing or not.
     * @dataProvider providerIsItemInventoried
     */
    public function testIsItemInventoried($item, $isInventoried)
    {
        $this->assertSame($isInventoried, Mage::helper('eb2ccore/quote_item')->isItemInventoried($item));
    }
}
