<?php
/**
 * Test Suite for the Order_Create
 */
class TrueAction_Eb2c_Order_Test_Model_CreateTest extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * @test
	 * @loadFixture
	 */
	public function testOrdersInterfaces()
	{
		$status = null;

		// Factory should be the same as new: 
		$theClassItself = new TrueAction_Eb2c_Order_Model_Create();
		$cr = Mage::getModel('eb2corder/create');
		$this->assertSame(get_class($cr), get_class($theClassItself));

		// Loop thru a collection, try creating order for last one
		$salesModel = Mage::getModel('sales/order');
		$orders = $salesModel->getCollection();
		foreach( $orders as $order ) {
			$testId = $order->getIncrementId();
		}
		$creator = Mage::getModel('eb2corder/create');
		try {
			$status = $creator->create($testId);
		}
		catch(Exception $e) {
			echo $e->getMessage();
			$status = false;
		}
		$this->assertSame($status, true);

		// Got 1 known order, create it ...
		$incrementId = '100000003';
		try {
			$status = $creator->create($incrementId);
		}
		catch(Exception $e) {
			echo $e->getMessage();
			$status = false;
		}
		$this->assertSame($status, true);

		// Don't want to find this, handle exception correctly.
		$incrementId = 'NO_CHANCE';
		try {
			$status = $creator->create($incrementId);
		}
		catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);

		// Excercise Event Observer Version. 
		$incrementId = '100000002';
		try {
			$status = $cr->observerCreate($incrementId);
		}
		catch(Exception $e) {
			$status = false;	
		}
		$this->assertSame($status, true);
	}
	
	/**
	 * Test get some XML, OK to be empty
	 * @test
	 */
	public function testEmptyXml()
	{
		$cr = Mage::getModel('eb2corder/create');
		$xml = $cr->toXml();
		$this->assertSame($xml, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	}
}
