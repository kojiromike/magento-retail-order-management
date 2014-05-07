<?php
class EbayEnterprise_Eb2cProduct_Test_Model_ObserversTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @test
	 * @loadFixture readOnlyAttributes.yaml
	 * lockReadOnlyAttributes reads the config for the attribute codes it needs to protect
	 * from admin panel edits by issuing a lockAttribute against the attribute code.
	 */
	public function testLockReadOnlyAttributes()
	{
		$product = $this->getModelMock('catalog/product', array('lockAttribute'));
		$product->expects($this->exactly(3))
			->method('lockAttribute');

		$varienEvent = $this->getMock('Varien_Event', array('getProduct'));
		$varienEvent->expects($this->once())
			->method('getProduct')
			->will($this->returnValue($product));

		$varienEventObserver = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$varienEventObserver->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($varienEvent));

		Mage::getModel('eb2cproduct/observers')->lockReadOnlyAttributes($varienEventObserver);
	}
}
