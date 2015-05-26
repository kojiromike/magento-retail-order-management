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

class EbayEnterprise_Inventory_Test_Helper_Quantity_FactoryTest
	extends EcomDev_PHPUnit_Test_Case
{
	/** @var EbayEnterprise_Inventory_Helper_Quantity_Factory */
	protected $_quantityFactory;

	public function setUp()
	{
		$this->_quantityFactory = Mage::helper('ebayenterprise_inventory/quantity_factory');
	}

	/**
	 * When creating a new quantity model, a new
	 * quantity model with the provided sku, item id
	 * and quantity should be returned.
	 */
	public function testCreateQuantity()
	{
		$sku = 'the-sku';
		$itemId = 3;
		$quantity = 345;
		$quantityModel = $this->_quantityFactory->createQuantity($sku, $itemId, $quantity);

		$this->assertInstanceOf('EbayEnterprise_Inventory_Model_Quantity', $quantityModel);
		$this->assertSame($sku, $quantityModel->getSku());
		$this->assertSame($itemId, $quantityModel->getItemId());
		$this->assertSame($quantity, $quantityModel->getQuantity());
	}
}
