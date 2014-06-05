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

class EbayEnterprise_Eb2cPayment_Test_Model_Paypal_Test
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function provideModelAliases()
	{
		return array(
			array('paypal_do_authorization', file_get_contents(__DIR__ . '/Do/AuthorizationTest/fixtures/PayPalDoAuthorizationReply.xml', true)),
			array('paypal_do_void', file_get_contents(__DIR__ . '/Do/VoidTest/fixtures/PayPalDoVoidReply.xml')),
			array('paypal_do_express_checkout', file_get_contents(__DIR__ . '/Do/Express/CheckoutTest/fixtures/PayPalDoExpressCheckoutReply.xml')),
			array('paypal_get_express_checkout', file_get_contents(__DIR__ . '/Get/Express/CheckoutTest/fixtures/PayPalGetExpressCheckoutReply.xml')),
			array('paypal_set_express_checkout', file_get_contents(__DIR__ . '/Set/Express/CheckoutTest/fixtures/PayPalSetExpressCheckoutReply.xml')),
		);
	}
	/**
	 * ensure the parse response method is being called.
	 *
	 * @test
	 * @dataProvider provideModelAliases
	 */
	public function testParseResponseCallsFailureHandler($alias, $responseMessage)
	{
		$testModel = $this->getModelMock("eb2cpayment/{$alias}", array('_blockIfRequestFailed'));
		$testModel->expects($this->once())
			->method('_blockIfRequestFailed')
			->with($this->isType('string'), $this->isInstanceOf('DOMXPath'));
		$testModel->parseResponse($responseMessage);
	}
}
