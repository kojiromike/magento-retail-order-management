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

class EbayEnterprise_Eb2cPayment_Test_Model_Paypal_Set_Express_CheckoutTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test that the 'eb2cpayment/paypal_set_express_checkout::parseResponse' method, when invoked
	 * will be passed in an XML response message string as its parameter and expects a known ‘Varien_Object’
	 * class instance to be returned and then asserts that the data in the returned ‘Varien_Object’
	 * class instance matches a known array of data.
	 * @param string $file The fixture relative path
	 * @param array $expected
	 * @dataProvider dataProvider
	 */
	public function testParseResponse($file, array $expected)
	{
		$xml = file_get_contents(__DIR__ . $file, true);
		$actual = Mage::getModel('eb2cpayment/paypal_set_express_checkout')->parseResponse($xml)->getData();
		$this->assertSame($expected, $actual);
	}
	/**
	 * Test the method 'eb2cpayment/paypal_set_express_checkout::_calculateUnitAmount' passed in a known
	 * 'sales/quote_item' class instance, as parameter, then expects the proper ‘UnitAmount’ calculation value to be returned.
	 * @param array $itemData
	 * @param float $expected
	 * @dataProvider dataProvider
	 */
	public function testCalculateUnitAmount(array $itemData, $expected)
	{
		$item = Mage::getModel('sales/quote_item', $itemData);
		$checkout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
		$actual = EcomDev_Utils_Reflection::invokeRestrictedMethod($checkout, '_calculateUnitAmount', array($item));
		$this->assertSame($expected, $actual);
	}
	/**
	 * Test the method 'eb2cpayment/paypal_set_express_checkout::_calculateLineItemsTotal' passed in a known 'sales/quote'
	 * class instance as the  first parameter and an array of totals as second parameter, then expects the proper LineItemsTotal
	 * calculated value to be returned.
	 * @param array $quoteData
	 * @param float $expected
	 * @dataProvider dataProvider
	 */
	public function testCalculateLineItemsTotal(array $quoteData, $expected)
	{
		// This is a hack because yaml is converting the data from float to string
		$expected = (float) $expected;
		$quote = Mage::getModel('sales/quote', $quoteData);
		$checkout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
		$actual = EcomDev_Utils_Reflection::invokeRestrictedMethod($checkout, '_calculateLineItemsTotal', array($quote));
		$this->assertSame($expected, $actual);
	}
}
