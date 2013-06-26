<?php
/**
 * Test Suite for the Order_Create
 */
class TrueAction_Eb2c_Order_Test_Model_CreateTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
	}

	/**
	 * @test
	 */
	public function testInstantiate()
	{
		$cr = Mage::getModel('eb2corder/create');
		$this->assertSame(get_class($cr), 'TrueAction_Eb2c_Order_Model_Create');

		$status = '';
		try {
			$status = $cr->create('100000004');
		}
		catch(Exception $e) {
			echo 'Exception ' . $e->getMessage() . "\n";
		}
		$this->assertSame($status, true);
	}
}
