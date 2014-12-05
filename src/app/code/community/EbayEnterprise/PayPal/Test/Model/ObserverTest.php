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

class EbayEnterprise_Paypal_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function testIsConfigured()
	{
		EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined(
			'global',
			'eb2c_order_creation_failure',
			'ebayenterprise_paypal/observer',
			'rollbackExpressPayment',
			'ebayenterprise_paypal_express_rollback'
		);
	}
	public function testRollbackExpressPayment()
	{
		$quote = $this->getModelMock('sales/quote');
		$order = $this->getModelMock('sales/order');
		$observerData = new Varien_Event_Observer(array('event' => new Varien_Event(array('quote' => $quote, 'order' => $order))));

		// check if the payment is express and is was successfully authorized.

		Mage::getModel('ebayenterprise_paypal/observer')->rollbackExpressPayment($observer);
	}
}
