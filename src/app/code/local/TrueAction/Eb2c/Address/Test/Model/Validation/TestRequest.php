<?php

/**
 * Test the generation of the xml request to the EB2C address validation service
 */

class TrueAction_Eb2c_Address_Test_Model_Validate_TestRequest
	extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * Parts of an address to use when building out the address request
	 */
	protected $_addressParts = array(
		'line1' => '123 Main St',
		'line2' => 'STE 6',
		'line3' => 'Foo',
		'line4' => 'Bar',
		'city' => 'Auburn',
		'region_id' => '51',
		'region_code' => 'PA',
		'country_id' => 'US',
		'postcode' => '13021',
	);

	/**
	 * Create a Mage_Customer_Model_Address_Abstract stub for testing
	 */
	protected function _createAddressStub()
	{
		$parts = $this->_addressParts;

		$stub = $this->getMock('Mage_Customer_Model_Address_Abstract',
			array('getStreet', 'getCity', 'getRegionCode', 'getCountry', 'getPostcode'));

		$stub->expects($this->any())
			->method('getStreet')
			->will($this->returnValue(
				array($parts['line1'], $parts['line2'], $parts['line3'], $parts['line4'])
			));
		$stub->expects($this->any())
			->method('getCity')
			->will($this->returnValue($parts['city']));
		$stub->expects($this->any())
			->method('getRegionCode')
			->will($this->returnValue($parts['region_code']));
		$stub->expects($this->any())
			->method('getCountry')
			->will($this->returnValue($parts['country_id']));
		$stub->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue($parts['postcode']));
		return $stub;
	}

	/**
	 * Create a TrueAction_Eb2c_Address_Model_Validation_Request to test against.
	 * Ensures that all of the necessary setup of the object is done.
	 */
	protected function _createRequest()
	{
		$request = Mage::getModel('eb2caddress/validation_request');
		$address = $this->_createAddressStub();
		$request->setAddress($address);
		return $request;
	}

	/**
	 * @test
	 * @loadFixture requestConfig
	 */
	public function testRequestApiUri()
	{
		$request = $this->_createRequest();
		$this->markTestSkipped('Not sure of exact spec for this functionality. Need to define where exactly each part of the API path should be coming from: class consts, module settings, core settings, etc.');
	}

	/**
	 * @test
	 */
	public function testRequestMessage()
	{
		$request = $this->_createRequest();
		$message = $request->getMessage();
		$this->markTestSkipped('Need to determine a sane way of actually testing the XML generation.');
	}

}