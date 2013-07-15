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
	public function testOrderCreateScenarios()
	{
		$status = null;

		// Create proper class:
		$creator = Mage::getModel('eb2corder/create');
		$this->assertInstanceOf('TrueAction_Eb2c_Order_Model_Create', $creator );

		// Get a collection; try creating order for last one
		$creator = Mage::getModel('eb2corder/create');
		$testId = Mage::getModel('sales/order')->getCollection()->getLastItem()->getIncrementId();
		try {
			$status = $creator->create($testId);
		} catch(Exception $e) {
			echo $e->getMessage();
			$status = false;
		}
		$this->assertSame($status, true);

		// Got 1 known order, create it ...
		$incrementId = '100000003';
		try {
			$status = $creator->create($incrementId);
		} catch(Exception $e) {
			echo $e->getMessage();
			$status = false;
		}
		$this->assertSame($status, true);

		// Don't want to find this, handle exception correctly.
		$incrementId = 'NO_CHANCE';
		try {
			$status = $creator->create($incrementId);
		} catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);

		// Exercise Event Observer Version. 
		$incrementId = '100000002';
		try {
			$status = $creator->observerCreate($incrementId);
		} catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, true);
	} 
	/**
	 * @test
	 * @loadFixture
	 * This fixture was setup to fail with a syntactically correct URL that couldn't really answer us in any sensible way.
	 */
	public function testWithEb2cPaymentsEnabled()
	{
		$status = null;

		$creator = Mage::getModel('eb2corder/create');
		$incrementId = '100000003';
		try {
			$status = $creator->create($incrementId);
		}
		catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);
	}
}
