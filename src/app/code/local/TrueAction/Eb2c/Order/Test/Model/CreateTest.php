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

        $creator = $this->getMock('TrueAction_Eb2c_Order_Model_Create', array('sendRequest'));
        $creator->expects($this->any())
             ->method('sendRequest')
             ->will($this->returnValue(true));

		// Create proper class:
		$testFactoryCreator = Mage::getModel('eb2corder/create');
		$this->assertInstanceOf('TrueAction_Eb2c_Order_Model_Create', $testFactoryCreator );

		// Get a collection; try creating order for last one
		$testId = Mage::getModel('sales/order')->getCollection()->getLastItem()->getIncrementId();
		try {
			$creator->buildRequest($testId);
			$status = $creator->sendRequest();
		} catch(Exception $e) {
			echo $e->getMessage();
		}
		$this->assertSame($status, true);

		// Got one known order, create it ...
		$status = null;
		$incrementId = '100000002';
		try {
			$creator->buildRequest($incrementId);
			$status = $creator->sendRequest();
		} catch(Exception $e) {
			echo $e->getMessage();
			$status = false;
		}
		$this->assertSame($status, true);

		// Don't want to find this, handle exception correctly.
		$status = null;
		$incrementId = 'NO_CHANCE';
		try {
			$creator->buildRequest($incrementId);
			$status = $creator->sendRequest();
		} catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);
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
			$creator->buildRequest($incrementId);
			$status = $creator->sendRequest();
		}
		catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);
	}
}
