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
}
