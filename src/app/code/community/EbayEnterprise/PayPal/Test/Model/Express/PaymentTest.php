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

class EbayEnterprise_PayPal_Test_Model_Express_PaymentTest
{
	public function testRollback()
	{
		$paymentHelper;
		$quote;
		$order;
		$api = $this->getModelMock('ebayenterprise_paypal/express_api');
		$api->expects($this->once())
			->method('doVoid')->with($order);
	}
}
