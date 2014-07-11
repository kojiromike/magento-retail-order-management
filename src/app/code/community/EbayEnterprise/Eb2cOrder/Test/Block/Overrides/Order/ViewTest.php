<?php
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
