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

class EbayEnterprise_Inventory_Test_Helper_DataTest
	extends EcomDev_PHPUnit_Test_Case
{
	/** @var EbayEnterprise_Inventory_Helper_Data */
	protected $_helper;

	public function setUp()
	{
		$this->_helper = Mage::helper('ebayenterprise_inventory');
	}

	/**
	 * Create a mock quote item with an expected quantity
	 * and parent item.
	 *
	 * @param int
	 * @param Mage_Sales_Model_Quote_Item_Abstract|null
	 * @return Mage_Sales_Model_Quote_Item_Abstract
	 */
	protected function _mockItemWithQtyAndParent(
		$qty,
		Mage_Sales_Model_Quote_Item_Abstract $parentItem = null
	) {
		$item = $this->getModelMock(
			'sales/quote_item_abstract',
			['getQty', 'getParentItem'],
			true
		);
		$item->expects($this->any())
			->method('getQty')
			->will($this->returnValue($qty));
		$item->expects($this->any())
			->method('getParentItem')
			->will($this->returnValue($parentItem));
		return $item;
	}

	/**
	 * Provide items and the expected total quantity requested
	 * for that item.
	 *
	 * @return array
	 */
	public function provideItemsForRequestedItemQuantityTest()
	{
		return [
			[$this->_mockItemWithQtyAndParent(5), 5],
			[$this->_mockItemWithQtyAndParent(5, $this->_mockItemWithQtyAndParent(2)), 10],
		];
	}

	/**
	 * When calculating item quantity requested, quantity
	 * should be calculated as item quantity * parent item
	 * quantity when an item has a parent item or just
	 * item quantity when it does not.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract
	 * @param int
	 * @dataProvider provideItemsForRequestedItemQuantityTest
	 */
	public function testGetRequestedItemQuantity(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		$requestedQuantity
	) {
		$this->assertSame($requestedQuantity, $this->_helper->getRequestedItemQuantity($item));
	}
}
