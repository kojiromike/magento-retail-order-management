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

class EbayEnterprise_Eb2cOrder_Test_Block_Overrides_Order_ViewTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Testing that when EbayEnterprise_Eb2cOrder_Overrides_Block_Order_View::getOrder
	 * method is invoked by this test will set the current order in the registry to
	 * eb2corder/customer_order_detail_order_adapter instance
	 */
	public function testGetOrder()
	{
		$incrementId = '1004840000113';
		$order = Mage::getModel('sales/order', array('real_order_id' => $incrementId));
		Mage::unregister('current_order');
		Mage::register('current_order', $order);

		$adapter = $this->getModelMock('eb2corder/customer_order_detail_order_adapter', array('loadByIncrementId'));
		$adapter->expects($this->once())
			->method('loadByIncrementId')
			->with($this->identicalTo($incrementId))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2corder/customer_order_detail_order_adapter', $adapter);

		$orderViewBlockMock = $this->getBlockMockBuilder('sales/order_view')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		// invoking get order will set the registry to the adapter class
		$orderViewBlockMock->getOrder();

		$currentOrder = Mage::registry('current_order');
		$this->assertSame($adapter, $currentOrder);
	}

}
