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

class EbayEnterprise_Eb2cGiftwrap_Test_Helper_DataTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test that the method EbayEnterprise_Eb2cGiftwrap_Helper_Data::createNewGiftwrapping
	 * will be invoked and will return new Mage_Catalog_Model_Giftwrap object with dummy data
	 * @test
	 */
	public function testCreateNewGiftwrapping()
	{
		$sku = '52-ABC-3832';
		$result = array(
			'eb2c_tax_class' => null,
			'base_price' => 0.0,
			'image' => null,
			'status' => 1,
			'design' => "Invalid gift wrapping: $sku",
			'eb2c_sku' => $sku
		);

		$this->assertSame($result, Mage::helper('eb2cgiftwrap')->createNewGiftwrapping($sku)->getData());
	}
}
