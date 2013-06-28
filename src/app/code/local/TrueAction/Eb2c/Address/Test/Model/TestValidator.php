<?php

class TrueAction_Eb2c_Address_Test_Model_TestValidator
	extends EcomDev_PHPUnit_Test_Case
{

	public function setUp()
	{
		parent::setUp();
		$this->_mockCoreHelper();
		$this->_mockCustomerSession();
	}

	/**
	 * Create an address object to pass off to the validator.
	 * @return Mage_Customer_Model_Address
	 */
	protected function _createAddress($fields = array())
	{
		$addr = Mage::getModel('customer/address');
		$addr->setData($fields);
		return $addr;
	}

	/**
	 * Replace the eb2ccore helper/data class with a mock.
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock helper
	 */
	protected function _mockCoreHelper()
	{
		$mockCoreHelper = $this->getHelperMock(
			'eb2ccore/data',
			array('callApi', 'apiUri', 'getMocked')
		);
		$mockCoreHelper->expects($this->any())
			->method('callApi')
			->will($this->returnValue('<?xml version="1.0" encoding="UTF-8"?><AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0"></AddressValidationResponse>'));
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
	 * Replace the eb2caddress/validation_response model with a mock
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock respose model
	 */
	protected function _mockValidationResponse(
		$isAddressValid = false,
		$hasSuggestions = false,
		$originalAddress = null,
		$validAddress = null,
		$addressSuggestions = array()
	) {
		$respMock = $this->getModelMock('eb2caddress/validation_response', array(
			'setMessage', 'isAddressValid', 'getValidAddress', 'getOriginalAddress',
			'hasSuggestions', 'getAddressSuggestions'));
		$respMock->expects($this->any())
			->method('isAddressValid')
			->will($this->returnValue($isAddressValid));
		$respMock->expects($this->any())
			->method('hasSuggestions')
			->will($this->returnValue($hasSuggestions));
		$respMock->expects($this->any())
			->method('getOriginalAddress')
			->will($this->returnValue($originalAddress));
		$respMock->expects($this->any())
			->method('getValidAddress')
			->will($this->returnValue($validAddress));
		$respMock->expects($this->any())
			->method('getAddressSuggestions')
			->will($this->returnValue($addressSuggestions));
		$respMock->expects($this->any())
			->method('setMessage')
			->will($this->returnValue($respMock));
		$this->replaceByMock('model', 'eb2caddress/validation_response', $respMock);
	}

	/**
	 * Get the session model used by the validator model.
	 * @return Mage_Customer_Model_Session
	 */
	protected function _session()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Test validation when the response indicates the address is correct
	 * and there are no suggestions or changes.
	 * @test
	 */
	public function testValidateAddressVerified()
	{
		// address to feed to the validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13021-9523',
			'has_been_validated' => true,
		));
		// original address from the response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13021',
		));

		$mockResponse = $this->_mockValidationResponse(true, false, $address, $address);
		$validator = Mage::getModel('eb2caddress/validator');
		$errorMessage = $validator->validateAddress($origAddress);
		$this->assertNull($errorMessage);
		$this->assertTrue($origAddress->getHasBeenValidated());
		$this->assertSame($origAddress->getStreet1(), '1671 Clark Street Rd');
		$this->assertSame($origAddress->getCity(), 'Auburn');
		$this->assertSame($origAddress->getRegionCode(), 'NY');
		$this->assertSame($origAddress->getCountryId(), 'US');
		$this->assertSame($origAddress->getPostcode(), '13021-9523');
	}

	/**
	 * Test validation when the response indicates the address is not correct
	 * and multiple suggestions are available.
	 * @test
	 */
	public function testValidateAddressMultiSuggestions()
	{
		// address to feed to validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		));
		// suggestions from the response model
		$suggestions = array(
			$this->_createAddress(array(
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			)),
			$this->_createAddress(array(
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			)),
		);
		$mockResponse = $this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		$validator = Mage::getModel('eb2caddress/validator');
		$errorMessage = $validator->validateAddress($address);

		$this->assertTrue($address->getHasBeenValidated());
		// when there are suggestions, main address should not be changed
		$this->assertSame($address->getStreet1(), '1671 Clark Street Rd');
		$this->assertSame($errorMessage, TrueAction_Eb2c_Address_Model_Validator::SUGGESTIONS_ERROR_MESSAGE);
	}

	/**
	 * Test the session interactions of the validation.
	 * @test
	 */
	public function testSessionInteractions()
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
		 */

		// address to feed to validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		));
		// suggestions from the response model
		$suggestions = array(
			$this->_createAddress(array(
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			)),
			$this->_createAddress(array(
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			)),
		);
		$mockResponse = $this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		$validator = Mage::getModel('eb2caddress/validator');
		$validator->validateAddress($address);

		$mockSession = $this->_session();
		$this->assertTrue($mockSession->hasData('address_validation_addresses'));
		$this->assertSame(
			$mockSession->getData('address_validation_addresses'),
			$validator->getAddressCollection()
		);

		$this->assertTrue($validator->getAddressCollection()->hasData('original_address'));
		$this->assertTrue($validator->getAddressCollection()->hasData('suggested_addresses'));
		$this->assertSame(
			count($validator->getAddressCollection()->getSuggestedAddresses()),
			2
		);
	}

	/**
	 * Test the removal of session values.
	 * @test
	 */
	public function testCleaningOfSession()
	{
		$mockSession = $this->_session();
		$validator = Mage::getModel('eb2caddress/validator');
		$staleData = "STALE_DATA";
		$mockSession->setAddressValidationAddresses($staleData);
		$this->assertNotNull($mockSession->getAddressValidationAddresses());
		$validator->clearSessionAddresses();
		$this->assertNull($mockSession->getAddressValidationAddresses());
	}

	/**
	 * Asking the validator model to validate a new address should clear out
	 * any values it has populated in the session.
	 * @test
	 */
	public function ensureSessionClearedOnNewValidation()
	{
		// address to feed to validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		));
		// suggestions from the response model
		$suggestions = array(
			$this->_createAddress(array(
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			)),
			$this->_createAddress(array(
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			)),
		);
		$mockResponse = $this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		// set up some stale data in the session that should be overwritten by the validator model
		$staleSessionData = 'STALE_DATA';
		$sessionKey = TrueAction_Eb2c_Address_Model_Validator::SESSION_KEY;
		$mockSession = $this->_session();
		$mockSession->setData($sessionKey, $staleSessionData);
		// make sure it has been set
		$this->assertSame($staleSessionData, $mockSession->getData($sessionKey));

		$validator = Mage::getModel('eb2caddress/validator');
		$validator->validateAddress($address);

		$this->assertNotEquals(
			$staleSessionData,
			$mockSession->getData($sessionKey)
		);
	}

	/**
	 * This is a very odd scenario and really should never happen.
	 * @test
	 */
	public function errorMessageWithInvalidMessageAndNoSuggestions()
	{
		// address to feed to validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		));
		$mockResponse = $this->_mockValidationResponse(false, false, $origAddress, null);

		$validator = Mage::getModel('eb2caddress/validator');
		$errorMessage = $validator->validateAddress($address);

		$this->assertSame(
			$errorMessage,
			TrueAction_Eb2c_Address_Model_Validator::NO_SUGGESTIONS_ERROR_MESSAGE
		);
	}

	/**
	 * Test retrieval of address objects from the validator by key.
	 * @test
	 */
	public function getValidatedAddressKyKey()
	{
		$this->markTestIncomplete('not yet implemented.');
		$validator = Mage::getModel('eb2caddress/validator');
	}

	/**
	 * Trying to get a validated address with an unknown key will return null
	 * @test
	 */
	public function gettingValidatedAddressByUnknownKey()
	{
		$this->markTestIncomplete('not yet implemented.');
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertNull($validator->getValidatedAddress('dont_know_about_this'));
	}

}