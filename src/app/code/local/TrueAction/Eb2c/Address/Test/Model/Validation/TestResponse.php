<?php

class TrueAction_Eb2c_Address_Test_Model_Validation_TestResponse
	extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testIsValid($valid, $message)
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		$response->setMessage($message);
		$this->assertEquals((bool) $valid, $response->isAddressValid());
	}

	/*
	 * @test
	 * @dataProvider dataProvider
	*/
	public function testIsValidLogged($valid, $message)
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		$response->setMessage($message);
		$this->assertEquals((bool) $valid, $response->isAddressValid());
		$this->markTestIncomplete('Need to get data provider to work and test that messages are properly logged.');
	}

}
