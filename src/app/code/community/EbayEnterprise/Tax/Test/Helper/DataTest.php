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

class EbayEnterprise_Tax_Test_Helper_DataTest
	extends EcomDev_PHPUnit_Test_Case
{
	/** @var EbayEnterprise_Tax_Helper_Data */
	protected $_helper;

	public function setUp()
	{
		$this->_helper = Mage::helper('ebayenterprise_tax');
	}

	/**
	 * Test getting the HTS code for a product in a given country.
	 */
	public function testGetProductHtsCodeByCountry()
	{
		$product = Mage::getModel('catalog/product', ['hts_codes' => serialize([
			['destination_country' => 'US', 'hts_code' => 'US-HTS-Code'],
			['destination_country' => 'CA', 'hts_code' => 'CA-HTS-Code'],
		])]);
		$this->assertSame(
			$this->_helper->getProductHtsCodeByCountry($product, 'CA'),
			'CA-HTS-Code'
		);
	}

	/**
	 * When a product has not HTS codes available, null should be returned
	 * when attpemting to get an HTS code.
	 */
	public function testGetProductHtsCodeByCountryNoHtsCodes()
	{
		$product = Mage::getModel('catalog/product');
		$this->assertNull($this->_helper->getProductHtsCodeByCountry($product, 'US'));
	}

	/**
	 * When a product has not HTS codes available, null should be returned
	 * when attpemting to get an HTS code.
	 */
	public function testGetProductHtsCodeByCountryNoMatchingHtsCode()
	{
		$product = Mage::getModel(
			'catalog/product',
			['hts_codes' => serialize([['destination_country' => 'US', 'hst_code' => 'US-HTS-Code']])]
		);
		$this->assertNull($this->_helper->getProductHtsCodeByCountry($product, 'CA'));
	}
}
