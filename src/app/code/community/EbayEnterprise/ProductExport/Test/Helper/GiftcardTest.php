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

class EbayEnterprise_ProductExport_Test_Helper_GiftcardTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test the EbayEnterprise_ProductExport_Helper_Giftcard:: passMaxGCAmount method using a DataProvider to
	 * test possible outcome when this test invokes the 'passMaxGCAmount' method. The first iteration of the
	 * DataProvider is to test when a valid product of type 'gift card', with an 'open_amount_max' and 'gitcard_amounts'
	 * data, then we expect a DOMNode object to be returned and the textContent property of this DOMNode object will equal
	 * to the 'open_amount_max' value. The second iteration of DataProvider is to test when the passed in product object is
	 * not of type 'gift card', then expects return value will be null. The third iteration of the DataProvider is to test
	 * when a product of type 'gift card', has 'open_amount_max' of zero and has 'gitcard_amounts' data with the largest
	 * value equal to zero, and then expects an exception to be thrown.
	 * @param array $productData
	 * @param bool $isException The flag to set expected exception
	 * @param string $expected
	 * @dataProvider dataProvider
	 */
	public function testPassMaxGCAmount(array $productData, $isException, $expected)
	{
		$product = Mage::getModel('catalog/product', $productData);
		$attrValue = '';
		$attribute = '';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		if ($isException) {
			$this->setExpectedException('EbayEnterprise_Eb2cProduct_Model_Pim_Product_Validation_Exception');
		}
		$actual = Mage::helper('ebayenterprise_productexport/giftcard')->passMaxGCAmount($attrValue, $attribute, $product, $doc);

		if (is_null($expected)) {
			$this->assertSame($expected, $actual);
		} else {
			$this->assertSame($expected, $actual->textContent);
		}
	}
}
