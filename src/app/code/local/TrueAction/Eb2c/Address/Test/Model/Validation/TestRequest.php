<?php

/**
 * Test the generation of the xml request to the EB2C address validation service
 */

class AddressStub extends Mage_Customer_Model_Address_Abstract {
	public function getStreetFull()
	{
		return array('123 Main St', 'Ste 6', 'Next to Gas Station', 'Across from Post Office');
	}
	public function getCity()
	{
		return 'King of Prussia';
	}
	public function getRegionCode()
	{
		return 'PA';
	}
	public function getCountry()
	{
		'US';
	}
	public function getPostcode()
	{
		'19406';
	}
}

class QuoteStub {
	protected $_shippingAddress;

	public function __construct() {
		$this->_shippingAddress = new AddressStub();
	}

	public function getShippingAddress()
	{
		return $this->_shippingAddress;
	}

}

class TrueAction_Eb2c_Address_Test_Model_Validate_Request extends EcomDev_PHPUnit_Test_Case
{

	protected function _createRequest()
	{
		$request = Mage::getModel('eb2caddress/validation_request');
		$request->setQuote(new QuoteStub());
		return $request;
	}

	/**
	 * @test
	 * @loadFixtures requestConfig
	 */
	public function testRequestHeader()
	{
		$request = $this->_createRequest();
		$request->createRequestMessage();
		$this->assertTrue($header->hasChildNodes());
		$this->assertEquals($header->childNodes->length, 1);
		$this->assertEquals($header->firstChild->tagName, 'MaxAddress');
		$this->assertEquals($header->firstChild->nodeValue, 5);
	}

}