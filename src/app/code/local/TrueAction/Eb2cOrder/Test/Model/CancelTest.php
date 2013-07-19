<?php
/**
 * Test Suite for the Order_Cancel
 */
class TrueAction_Eb2cOrder_Test_Model_CancelTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * @loadFixture
	 */
	public function testCancel()
	{
		$status = null;
		// Test build and send
        $cancelor = $this->getMock('TrueAction_Eb2cOrder_Model_Cancel', array('sendRequest'));
        $cancelor->expects($this->any())
             ->method('sendRequest')
             ->will($this->returnValue(true));

		try {
			$cancelor->buildRequest( array(
					'order_id'=>'12345',
					'order_type'=>'RETURN',
					'reason_code'=>'TestReasonCode',
					'reason'=>'Testing out a longish reason text right here',
				)
			);
			$status = $cancelor->sendRequest();
		}
		catch( Exception $e ) {
			$status = false;
		}
		$this->assertSame($status,true);

		// Test class factory:
		$testFactoryCreator = Mage::getModel('eb2corder/cancel');
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Cancel', $testFactoryCreator );
	}

	/**
	 * @test
	 * @loadFixture
	 * This fixture was setup to fail with a syntactically correct URL that couldn't really answer us in any sensible way.
	 */
	public function testWithEb2cPaymentsEnabled()
	{
		$status = null;

		$cancelor = Mage::getModel('eb2corder/cancel');
		$incrementId = '100000003';
		try {
			$cancelor->buildRequest( array(
					'order_id'=>'12345',
					'order_type'=>'RETURN',
					'reason_code'=>'TestReasonCode',
					'reason'=>'Testing out a longish reason text right here',
				)
			);
			$status = $cancelor->sendRequest();
		}
		catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);
	}
}
