<?php

class TrueAction_Eb2c_Address_Test_Model_TestValidator
	extends EcomDev_PHPUnit_Test_Case
{

	protected function _createAddress()
	{
		$addr = Mage::getModel('customer/address')
			->setStreet('123 Main St')
			->setCity('Foo')
			->setCountryId('US')
			->setRegionId(51)
			->setPostcode('19406');
		return $addr;
	}

	/**
	 * Replace the eb2ccore helper/data class with a mock.
	 * $message will be returned as the response message from eb2c in the
	 * callApi method.
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock helper
	 */
	protected function _mockCoreHelper($message)
	{
		$mockCoreHelper = $this->getHelperMock(
			'eb2ccore/data',
			array('callApi', 'apiUri', 'getMocked')
		);
		$mockCoreHelper->expects($this->any())
			->method('callApi')
			->will($this->returnValue($message));
		$mockCoreHelper->expects($this->any())
			->method('apiUri')
			->will($this->returnValue('https://does.not.matter/as/this/isnot/actually/used.xml'));
		$mockCoreHelper->expects($this->any())
			->method('getMocked')
			->will($this->returnValue('yup'));
		$this->replaceByMock('helper', 'eb2ccore', $mockCoreHelper);
		return $mockCoreHelper;
	}

	/**
	 * Replace the customer session object with a mock.
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock session model
	 */
	protected function _mockCustomerSession()
	{
		$sessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(null) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$this->replaceByMock('singleton', 'customer/session', $sessionMock);
		return $sessionMock;
	}

	/**
	 * Test validation when the response indicates the address is correct
	 * and there are no suggestions or changes.
	 * @test
	 */
	public function testValidateAddressVerified()
	{
		/* Simulated response message:
		 * ResultCode - V
		 * Request Address:
		 * Line1 - 1671 Clark Street Rd
		 * City - Auburn
		 * MainDivision - NY
		 * CountryCode - US
		 * PostalCode - 13021-9523
		 */
		$mockHelper = $this->_mockCoreHelper('<?xml version="1.0" encoding="UTF-8"?><AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><Header><MaxAddressSuggestions>5</MaxAddressSuggestions></Header><RequestAddress><Line1>1671 Clark Street Rd</Line1><City>Auburn</City><MainDivision>NY</MainDivision><CountryCode>US</CountryCode><PostalCode>13021-9523</PostalCode><FormattedAddress>1671 Clark Street Rd\nAuburn NY 13021-9523\nUS</FormattedAddress></RequestAddress><Result><ResultCode>V</ResultCode><ProviderResultCode>C</ProviderResultCode><ProviderName>Address Doctor</ProviderName><ResultSuggestionCount>0</ResultSuggestionCount></Result></AddressValidationResponse>');
		$mockSession = $this->_mockCustomerSession();
		$validator = Mage::getModel('eb2caddress/validator');
		$address = $this->_createAddress();
		$validator->validateAddress($address);
		$this->assertTrue($address->getHasBeenValidated());
		$this->assertSame($address->getStreet1(), '1671 Clark Street Rd');
		$this->assertSame($address->getCity(), 'Auburn');
		$this->assertSame($address->getRegionCode(), 'NY');
		$this->assertSame($address->getCountryId(), 'US');
		$this->assertSame($address->getPostcode(), '13021-9523');
	}

	/**
	 * Test validation when the response indicates the address is not correct
	 * and multiple suggestions are available.
	 * @test
	 */
	public function testValidateAddressMultiSuggestions()
	{
		/* Simulated response message
		 * Result Code - C
		 * Request Address:
		 * -  Line1 - 1671 Clark Street Rd
		 * -  City - Auburn
		 * -  MainDivision - NY
		 * -  CountryCode - US
		 * -  PostalCode - 13025
		 *
		 * 2 Suggested Addresses
		 * First Suggestion
		 * -  Line1 - Suggestion 1 Line 1
		 * -  City - Suggestion 1 City
		 * -  MainDivision - NY
		 * -  CountryCode - US
		 * -  PostalCode - 13021-9876
		 * Second Suggestion
		 * -  Line1 - 1671 W Clark Street Rd
		 * -  City - Auburn
		 * -  MainDivision - NY
		 * -  CountryCode - US
		 * -  PostalCode - 13021-1234
		 *
		 */
		$mockHelper = $this->_mockCoreHelper('<?xml version="1.0" encoding="UTF-8"?><AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><Header><MaxAddressSuggestions>5</MaxAddressSuggestions></Header><RequestAddress><Line1>1671 Clark Street Rd</Line1><City>Auburn</City><MainDivision>NY</MainDivision><CountryCode>US</CountryCode><PostalCode>13025</PostalCode></RequestAddress><Result><ResultCode>C</ResultCode><ProviderResultCode>C</ProviderResultCode><ProviderName>Address Doctor</ProviderName><ErrorLocations><ErrorLocation>PostalCode</ErrorLocation></ErrorLocations><ResultSuggestionCount>1</ResultSuggestionCount><SuggestedAddresses><SuggestedAddress><Line1>Suggestion 1 Line 1</Line1><City>Suggestion 1 City</City><MainDivision>NY</MainDivision><CountryCode>US</CountryCode><PostalCode>13021-9876</PostalCode><FormattedAddress>Do Not Care</FormattedAddress><ErrorLocations><ErrorLocation>Line1</ErrorLocation><ErrorLocation>City</ErrorLocation><ErrorLocation>PostalCode</ErrorLocation></ErrorLocations></SuggestedAddress><SuggestedAddress><Line1>1671 W Clark Street Rd</Line1><City>Auburn</City><MainDivision>NY</MainDivision><CountryCode>US</CountryCode><PostalCode>13021-1234</PostalCode><FormattedAddress>Do Not Care</FormattedAddress><ErrorLocations><ErrorLocation>Line1</ErrorLocation><ErrorLocation>PostalCode</ErrorLocation></ErrorLocations></SuggestedAddress></SuggestedAddresses></Result></AddressValidationResponse>');
		$mockSession = $this->_mockCustomerSession();

		$validator = Mage::getModel('eb2caddress/validator');
		$address = $this->_createAddress();
		$validator->validateAddress($address);
		$this->assertTrue($address->getHasBeenValidated());
		// when there are suggestions, main address should not be changed
		$this->assertSame($address->getStreet1(), '1671 Clark Street Rd');
		$suggestions = $mockSession->getAddressSuggestions();
		$this->assertSame(count($suggestions), 2);
		$this->assertTrue($suggestions[0] instanceof Mage_Customer_Model_Address_Abstract);
		$this->assertSame($suggestions[0]->getStreet1(), 'Suggestion 1 Line 1');
		$this->assertTrue($suggestions[0]->getHasBeenValidated());

		$this->assertTrue($suggestions[1] instanceof Mage_Customer_Model_Address_Abstract);
		$this->assertSame($suggestions[1]->getStreet1(), '1671 W Clark Street Rd');
		$this->assertTrue($suggestions[1]->getHasBeenValidated());
	}

}