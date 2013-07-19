<?php

class TrueAction_Eb2cAddress_Test_Model_ValidatorTest
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
	 * Get the session object used by address validation.
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Setup the session data with the supplied addresses.
	 * @param Mage_Customer_Model_Address_Abstract $original
	 * @param Mage_Customer_Model_Address_Abstract[] $suggestions
	 */
	protected function _setupSessionWithSuggestions($original, $suggestions)
	{
		// populate the session with usable data - replaced with mock in setUp
		$addresses = new TrueAction_Eb2cAddress_Model_Suggestion_Group();
		$addresses->setOriginalAddress($original);
		$addresses->setSuggestedAddresses($suggestions);
		$this->_getSession()
			->setData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY, $addresses);
	}

	/**
	 * Test the various session interactions when there is not session data
	 * for address validation initialized.
	 * @test
	 */
	public function testValidatorSessionNotInitialized()
	{
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertNull($validator->getOriginalAddress());
		$this->assertNull($validator->getSuggestedAddresses());
		$this->assertNull($validator->getValidatedAddress('original_address'));
		$this->assertFalse($validator->hasSuggestions());
	}

	/**
	 * Make sure getAddressCollection *always* returns a
	 * TrueAction_Eb2cAddress_Model_Suggestion_Group even if the
	 * session data where it expects to find the suggestions is polluted
	 * with something else.
	 * @test
	 */
	public function testGettingAddressCollectionAlwaysReturnsProperObjectType()
	{
		$expectedType = 'TrueAction_Eb2cAddress_Model_Suggestion_Group';
		$session = $this->_getSession();
		$sessionKey = TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY;

		$validator = Mage::getModel('eb2caddress/validator');

		$session->setData($sessionKey, "a string");
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->setData($sessionKey, new Varien_Object());
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->setData($sessionKey, 23);
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->setData($sessionKey, new TrueAction_Eb2cAddress_Model_Suggestion_Group());
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->unsetData($sessionKey);
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());
	}

	/**
	 * Test updating the submitted address with data from the chosen suggestion.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testUpdateAddressWithSelections($postValue)
	{
		$originalAddress = $this->_createAddress(array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address',
		));
		$suggestions = array(
			$this->_createAddress(array(
				'street' => '321 Main Rd',
				'city' => 'Barton',
				'region_code' => 'NY',
				'country_id' => 'US',
				'postcode' => '54321-1234',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/0',
			)),
			$this->_createAddress(array(
				'street' => '321 Main St',
				'city' => 'Fooville',
				'country_id' => 'US',
				'postcode' => '12345-6789',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/1',
			))
		);
		$this->_setupSessionWithSuggestions($originalAddress, $suggestions);

		// set the submitted value in the request post data
		$_POST[TrueAction_Eb2cAddress_Block_Suggestions::SUGGESTION_INPUT_NAME] = $postValue;

		// create an address object to act as the address submitted by the user
		$submittedAddress = $this->_createAddress(array(
			'street' => '1 Street Rd',
			'city' => 'Foo',
			'region_code' => 'PA',
			'region_id' => 51,
			'country_id' => 'US',
			'postcode' => '23456',
		));

		// this is necessary due to expectation not allowing a / in the key to get expectations
		$expectationKey = str_replace("/", "", $postValue);

		$validator = Mage::getModel('eb2caddress/validator');
		$reflection = new ReflectionClass('TrueAction_Eb2cAddress_Model_Validator');
		$method = $reflection->getMethod('_updateAddressWithSelection');
		$method->setAccessible(true);

		$updated = $method->invoke($validator, $submittedAddress);

		$this->assertSame(
			$this->expected($expectationKey)->getStreet1(),
			$submittedAddress->getStreet1()
		);
		$this->assertSame(
			$this->expected($expectationKey)->getCity(),
			$submittedAddress->getCity()
		);
		$this->assertSame(
			$this->expected($expectationKey)->getPostcode(),
			$submittedAddress->getPostcode()
		);
		$this->assertSame(
			$this->expected($expectationKey)->getHasBeenValidated(),
			$submittedAddress->getHasBeenValidated()
		);
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
		$this->assertNull($errorMessage, 'Validation of a valid address produces no errors.');
		$this->assertTrue($origAddress->getHasBeenValidated(), 'has_been_validated "magic" data set on the address that has been validated.');
		$this->assertSame($origAddress->getStreet1(), '1671 Clark Street Rd');
		$this->assertSame($origAddress->getCity(), 'Auburn');
		$this->assertSame($origAddress->getRegionCode(), 'NY');
		$this->assertSame($origAddress->getCountryId(), 'US');
		$this->assertSame($origAddress->getPostcode(), '13021-9523', 'Corrections from address validation applied to the "original" address.');
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
			'postcode' => '13025-1234',
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

		$this->assertTrue($address->getHasBeenValidated(), '"has_been_validated" data added to the address object.');
		$this->assertSame($address->getPostcode(), $origAddress->getPostcode(), 'Corrected data in the "original_address" fields from EB2C should still be copied over.');
		$this->assertSame($errorMessage, TrueAction_Eb2cAddress_Model_Validator::SUGGESTIONS_ERROR_MESSAGE, 'Invalid address with suggestions should have the appropriate message.');
	}

	/**
	 * If a previously validated address is passed to the validate method,
	 * validation should assume the address is correct and
	 * should be successful (no errors returned) and
	 * should clear out any session data.
	 * @test
	 */
	public function testWithValidatedAddress()
	{
		$address = $this->_createAddress(array(
			'has_been_validated' => true
		));

		// add some data into the customer session mock.
		$session = $this->_getSession();
		$session->setData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY, 'this should be cleared out');
		$validator = Mage::getModel('eb2caddress/validator');
		$errors = $validator->validateAddress($address);

		$this->assertNull($errors, 'An address that is marked as already having been validated is assumed valid, hence no errors.');
		$this->assertNull(Mage::getSingleton('customer/session')->getData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY), 'Validating a validated address should also clear address validation session data.');
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

		$mockSession = $this->_getSession();
		$this->assertTrue($mockSession->hasData('address_validation_addresses'), 'Customer session should have "magic" address_validation_addresses data.');
		$this->assertSame(
			$mockSession->getData('address_validation_addresses'),
			$validator->getAddressCollection(),
			'Ensure session data looks the way we expect it to.'
		);

		$this->assertTrue($validator->getAddressCollection()->hasData('original_address'), 'Address collection TrueAction_Eb2cAddress_Model_Suggestion_Group should have "original_address" data.');
		$this->assertTrue($validator->getAddressCollection()->hasData('suggested_addresses'), 'Address collection TrueAction_Eb2cAddress_Model_Suggestion_Group should have "suggested_addresses" data.');
		$this->assertSame(
			count($validator->getAddressCollection()->getSuggestedAddresses()),
			2,
			'Ensure both suggested addresses are added to the address collection\'s suggested addresses.'
		);
		$this->assertSame(
			$validator->getAddressCollection()->getOriginalAddress()->getStashKey(),
			'original_address',
			'Ensure the a "stash_key" has been added to the original address.'
		);
		$validatorSuggested = $validator->getAddressCollection()->getSuggestedAddresses();
		$this->assertSame(
			$validatorSuggested[0]->getStashKey(),
			'suggested_addresses/0',
			'Ensure a "stash_key" was added to the suggested addresses.');
		$this->assertSame(
			$validatorSuggested[1]->getStashKey(),
			'suggested_addresses/1',
			'Ensure a "stash_key" was added to the suggested addresses.');
	}

	/**
	 * Test the removal of session values.
	 * @test
	 */
	public function testCleaningOfSession()
	{
		$mockSession = $this->_getSession();
		$validator = Mage::getModel('eb2caddress/validator');
		$staleData = "STALE_DATA";
		$mockSession->setAddressValidationAddresses($staleData);
		$this->assertNotNull($mockSession->getAddressValidationAddresses(), 'Session has address validation data.');
		$validator->clearSessionAddresses();
		$this->assertNull($mockSession->getAddressValidationAddresses(), 'Session does not have address valdidation data.');
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
		$sessionKey = TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY;
		$mockSession = $this->_getSession();
		$mockSession->setData($sessionKey, $staleSessionData);
		// make sure it has been set
		$this->assertSame($staleSessionData, $mockSession->getData($sessionKey), 'Session has initial address validation data.');

		$validator = Mage::getModel('eb2caddress/validator');
		$validator->validateAddress($address);

		$this->assertNotEquals(
			$staleSessionData,
			$mockSession->getData($sessionKey),
			'Stale session data replaced by new address validation data.'
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
			TrueAction_Eb2cAddress_Model_Validator::NO_SUGGESTIONS_ERROR_MESSAGE
		);
	}

	/**
	 * Test retrieval of address objects from the validator by key.
	 * Each address should have a stash_key which will be used to get
	 * the address back out of the address collection stored in the session.
	 * @test
	 */
	public function getValidatedAddressByKey()
	{
		$originalAddress = $this->_createAddress(array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address',
		));
		$suggestions = array(
			$this->_createAddress(array(
				'street' => '321 Main Rd',
				'city' => 'Barton',
				'region_code' => 'NY',
				'country_id' => 'US',
				'postcode' => '54321-1234',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/0',
			)),
			$this->_createAddress(array(
				'street' => '321 Main St',
				'city' => 'Fooville',
				'country_id' => 'US',
				'postcode' => '12345-6789',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/1',
			))
		);
		$this->_setupSessionWithSuggestions($originalAddress, $suggestions);

		$validator = Mage::getModel('eb2caddress/validator');

		$this->assertSame(
			$originalAddress,
			$validator->getValidatedAddress($originalAddress->getStashKey())
		);
		$this->assertSame(
			$suggestions[1],
			$validator->getValidatedAddress($suggestions[1]->getStashKey())
		);
	}

	/**
	 * Trying to get a validated address with an unknown key will return null
	 * @test
	 */
	public function gettingValidatedAddressByUnknownKey()
	{
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertNull($validator->getValidatedAddress('dont_know_about_this'), 'Unknown "stash_key" results in "null" response.');
	}

	/**
	 * Test getting the original address out of the session
	 * @test
	 */
	public function testGetOriginalAddress()
	{
		// set up an address object to be put into the session
		$origAddress = $this->_createAddress((array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_code' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address'
		)));
		// create the TrueAction_Eb2cAddress_Model_Suggestion_Group that stores address data in the session
		$addresses = new TrueAction_Eb2cAddress_Model_Suggestion_Group();
		$addresses->setOriginalAddress($origAddress);
		// add the address data to the session
		$this->_getSession()->setData(
			TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY,
			$addresses
		);

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertSame(
			$origAddress,
			$validator->getOriginalAddress()
		);
	}

	/**
	 * Test getting the list of suggested addresses.
	 * @test
	 */
	public function testGetSuggestedAddresses()
	{
		// set up suggested addresses to place into the session
		$suggestions = array(
			$this->_createAddress(array(
				'street' => '123 Main St',
				'city' => 'Fooville',
				'region_code' => 'NY',
				'country_code' => 'US',
				'postcode' => '12345',
			),
			array(
				'street' => '321 Main Rd',
				'city' => 'Bartown',
				'region_code' => 'PA',
				'country_code' => 'US',
				'postcode' => '19231',
			))
		);
		// create the TrueAction_Eb2cAddress_Model_Suggestion_Group that stores the address data in the session
		$addressCollection = new TrueAction_Eb2cAddress_Model_Suggestion_Group();
		$addressCollection->setSuggestedAddresses($suggestions);
		// add suggestions to the session
		$this->_getSession()->setData(
			TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY,
			$addressCollection
		);

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertSame(
			$suggestions,
			$validator->getSuggestedAddresses()
		);
	}

	public function testMergeCloneAddresses()
	{
		$destData = array(
			'firstname' => 'The',
			'lastname' => 'Bar',
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'PA',
			'country_code' => 'US',
			'postcode' => '12345',
		);
		$destAddr = $this->_createAddress($destData);
		$sourceData = array(
			'street' => '555 Foo St',
			'city' => 'Bartown',
			'region_code' => 'NY',
			'country_code' => 'US',
			'postcode' => '12345-6789',
		);
		$sourceAddr = $this->_createAddress($sourceData);

		$validator = Mage::getModel('eb2caddress/validator');
		$reflection = new ReflectionClass('TrueAction_Eb2cAddress_Model_Validator');
		$method = $reflection->getMethod('_cloneMerge');
		$method->setAccessible(true);

		$mergedClone = $method->invoke($validator, $destAddr, $sourceAddr);
		// make sure a new object was returned, not a modified version of one of the merged objects
		$this->assertNotSame($destAddr, $mergedClone, '_cloneMerge should return a new object.');
		$this->assertNotSame($sourceAddr, $mergedClone, '_cloneMerge shoudl return a new object.');
		// first name should have been preserved from the destination object
		$this->assertSame(
			$destAddr->getFirstname(),
			$mergedClone->getFirstname(),
			'Data on the destination object that is not on the source object should remain.');
		// city should have been copied from the source object
		$this->assertSame(
			$sourceAddr->getCity(),
			$mergedClone->getCity(),
			'Data on the source object should replace data on the destination object.'
		);

		// make sure the original source and destination objects weren't modified
		$this->assertSame(
			$destAddr->getData(),
			$destData,
			'Original object\'s data should not be changed.'
		);
		$this->assertSame(
			$sourceAddr->getData(),
			$sourceData,
			'Original object\'s data should not be changed.'
		);
	}

}
