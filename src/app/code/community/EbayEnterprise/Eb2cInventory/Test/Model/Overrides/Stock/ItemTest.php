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

class EbayEnterprise_Eb2cInventory_Test_Model_Overrides_Stock_ItemTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_item;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_item = Mage::getModel('eb2cinventoryoverride/stock_item');
	}

	/**
	 * testing canSubtractQty method
	 *
	 * @large
	 * @test
	 */
	public function testCanSubtractQty()
	{
		$this->assertSame(
			false,
			$this->_item->canSubtractQty()
		);
	}
}
