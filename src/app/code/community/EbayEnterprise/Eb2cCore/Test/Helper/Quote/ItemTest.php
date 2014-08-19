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
		$manageProduct = Mage::getModel(
			'catalog/product',
			array(
				'stock_item' => Mage::getModel('cataloginventory/stock_item', array(
					'manage_stock' => true,
					'use_config_manage_stock' => false
				)),
			)
		);
		$noManageProduct = Mage::getModel(
			'catalog/product',
			array(
				'stock_item' => Mage::getModel('cataloginventory/stock_item', array(
					'manage_stock' => false,
					'use_config_manage_stock' => false
				)),
			)
		);

		$singleItemManaged        = Mage::getModel('sales/quote_item', array('product' => $manageProduct));
		$singleItemNoManaged      = Mage::getModel('sales/quote_item', array('product' => $noManageProduct));
		$parentItemManagedChild   = Mage::getModel('sales/quote_item', array('product' => $noManageProduct,));
		$parentItemNoManagedChild = Mage::getModel('sales/quote_item', array('product' => $noManageProduct,));
		$managedChildItem         = Mage::getModel('sales/quote_item', array('product' => $manageProduct, 'parent_item_id' => 2,));
		$noManagedChildItem       = Mage::getModel('sales/quote_item', array('product' => $noManageProduct, 'parent_item_id' => null,));

		// Relate parent and child items: 
		$managedChildItem->setParentItem($parentItemManagedChild);
		$noManagedChildItem->setParentItem($parentItemNoManagedChild);

		return array(
			array($singleItemManaged, true),
			array($singleItemNoManaged, false),
			array($parentItemManagedChild, true),
			array($parentItemNoManagedChild, false),
			array($managedChildItem, false),
			array($noManagedChildItem, false),
		);
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

