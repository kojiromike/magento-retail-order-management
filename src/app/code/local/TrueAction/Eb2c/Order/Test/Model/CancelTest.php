<?php
/**
 * Test Suite for the Order_Cancel
 */
class TrueAction_Eb2c_Order_Test_Model_CancelTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * @loadFixture
	 */
	public function testCancel()
	{
		$cr = Mage::getModel('eb2corder/cancel');
		try {
			$status = $cr->cancel( array(
					'order_id'=>'12345',
					'order_type'=>'RETURN',
					'reason_code'=>'TestReasonCode',
					'reason'=>'Testing out a longish reason text right here',
				)
			);
		}
		catch( Exception $e ) {
			$status = false;
		}
		$this->assertSame(get_class($cr), 'TrueAction_Eb2c_Order_Model_Cancel');
		$this->assertSame($status,false);
	}
}
