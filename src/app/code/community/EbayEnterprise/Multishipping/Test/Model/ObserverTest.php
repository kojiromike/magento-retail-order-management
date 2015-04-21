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

class EbayEnterprise_Multishipping_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * When handling a sales order before save event, ensure that any shipment
	 * amounts for that order have been collected before the order is saved.
	 */
	public function testHandleSalesOrderBeforeSave()
	{
		$order = $this->getModelMock('sales/order', ['collectShipmentAmounts']);
		$order->expects($this->once())
			->method('collectShipmentAmounts')
			->will($this->returnSelf());
		$event = new Varien_Event(['order' => $order]);
		$eventObserver = new Varien_Event_Observer(['event' => $event]);

		$observer = Mage::getModel('ebayenterprise_multishipping/observer');
		$observer->handleSalesOrderSaveBefore($eventObserver);
	}
}
