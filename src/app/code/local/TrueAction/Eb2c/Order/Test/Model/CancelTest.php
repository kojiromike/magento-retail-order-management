<?php
/**
 * Test Suite for the Order_Cancel
 */
class TrueAction_Eb2c_Order_Test_Model_CancelTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
	}

	/**
	 * @test
	 */
	public function testInstantiate()
	{
		// TODO: args should come from a fixture
		$cr = Mage::getModel('eb2corder/cancel');
		$cr->cancel( array(
						'order_id'=>'12345',
						'order_type'=>'RETURN',
						'reason_code'=>'TestReasonCode',
						'reason'=>'Testing out a longish reason text right here',
					)
				);
		$this->assertSame(get_class($cr), 'TrueAction_Eb2c_Order_Model_Cancel');
		$xmlString = $cr->toXml();
		$this->assertSame(get_class(simplexml_load_string($xmlString)), 'SimpleXMLElement');
		echo "\n" . get_class($cr) . ": \n" . $xmlString . "\n";
	}
}
