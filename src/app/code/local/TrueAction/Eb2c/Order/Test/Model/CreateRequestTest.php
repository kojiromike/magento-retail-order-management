<?php
/**
 * Test Suite for the Order_CreateRequest
 */
class TrueAction_Eb2c_Order_Test_Model_CreateRequestTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
	}

	/**
	 * @test
	 */
	public function testInstantiate()
	{
		$cr = Mage::getModel('eb2corder/createRequest');
		$this->assertSame(get_class($cr), 'TrueAction_Eb2c_Order_Model_CreateRequest');
		$cr->createOrder('100000002');
		echo "\n" . $cr->toXml();
	}
}
