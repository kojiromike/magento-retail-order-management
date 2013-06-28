<?php
/**
 * Test Suite for the Order_Create
 */
class TrueAction_Eb2c_Order_Test_Model_CreateTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test we can fail gracefully
	 * @test
	 * @loadFixture
	 */
	public function testInstantiate()
	{
		$cr = Mage::getModel('eb2corder/create');
		$this->assertSame(get_class($cr), 'TrueAction_Eb2c_Order_Model_Create');

		$salesModel = Mage::getModel('sales/order');
		$orders = $salesModel->getCollection();
		foreach( $orders as $order ) {
			$testId = $order->getIncrementId();
		}
		$status = '';
		try {
			$status = $cr->create($order->getIncrementId());
		}
		catch(Exception $e) {
			$status = false;		// Order isn't found.
		}
		$this->assertSame($status, false);
	}

	/**
	 * Test the observer's event handler can fail gracefully
	 * @test
	 */
	public function testObserverCreate()
	{
		$cr = Mage::getModel('eb2corder/create');
		try {
			$status = $cr->observerCreate('X00000004');
		}
		catch(Exception $e) {
			$status = false;		// Order isn't found.
		}
		$this->assertSame($status, false);
	}

	/**
	 * Test get some XML, OK to be empty
	 * @test
	 */
	public function testToXml()
	{
		$cr = Mage::getModel('eb2corder/create');
		$xml = $cr->toXml();
		$this->assertSame($xml, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	}
}
