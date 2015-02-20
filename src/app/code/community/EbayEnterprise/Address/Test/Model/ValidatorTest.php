<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Address_Test_Model_ValidatorTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi (mock) */
	protected $_sdkApi;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data (mock) */
	protected $_coreHelper;
	/** @var EbayEnterprise_Address_Helper_Data (mock) */
	protected $_addressHelper;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_addressConfig;

	public function setUp()
	{
		parent::setUp();
		$this->_mockCustomerSession();
		$this->_mockCheckoutSession();

		$this->_sdkApi = $this->_mockSdkApi();

		$this->_coreHelper = $this->getHelperMock('eb2ccore/data', ['getSdkApi']);
		$this->_coreHelper->expects($this->any())
			->method('getSdkApi')
			->will($this->returnValue($this->_sdkApi));

		// Stub the __get method with config key => value value map in tests that need to mock config
		$this->_addressConfig = $this->buildCoreConfigRegistry();

		$this->_addressHelper = $this->getHelperMock('ebayenterprise_address/data', ['__', 'getConfigModel']);
		$this->_addressHelper->expects($this->any())
			->method('__')
			->will($this->returnArgument(0));
		$this->_addressHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->_addressConfig));

		$this->_validatorRequest = $this->getModelMockBuilder('ebayenterprise_address/validation_request')
			->disableOriginalConstructor()
			->setMethods(['prepareRequest', 'getRequest'])
			->getMock();
		$this->_validatorResponse = $this->getModelMockBuilder('ebayenterprise_address/validation_response')
			->disableOriginalConstructor()
			->setMethods([
				'getValidAddress', 'getOriginalAddress', 'getHasSuggestions',
				'getAddressSuggestions', 'isAddressValid'
			])
			->getMock();

		$this->_validator = $this->getModelMockBuilder('ebayenterprise_address/validator')
			// mock out interactions with validation request and response models,
			// prevents deep mocking of SDK objects
			->setMethods(['_prepareApiForAddressRequest', '_getValidationResponse'])
			->setConstructorArgs([['core_helper' => $this->_coreHelper, 'helper' => $this->_addressHelper]])
			->getMock();
		$this->_validator->expects($this->any())
			->method('_getValidationResponse')
			->will($this->returnValue($this->_validatorResponse));
	}

	/**
	 * Create an address object to pass off to the validator.
	 * @return Mage_Customer_Model_Address
	 */
	protected function _createAddress($fields=[])
	{
		$addr = Mage::getModel('customer/address');
		$addr->setData($fields);
		return $addr;
	}

	/**
	 * Mock an SDK IBidirectionalApi object.
	 *
	 * @return \eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi (mock)
	 */
	protected function _mockSdkApi()
	{
		$sdk = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi')
			// Constructor disabled to prevent needing to create and inject
			// API configuration.
			->disableOriginalConstructor()
			// Payload getters mocked but not set to return anything. The validator
			// shouln't need to worry about manipulating the payloads so shouldn't
			// be necessary to create valid or mocked payloads.
			->setMethods(['getRequestBody', 'setRequestBody', 'send', 'getResponseBody'])
			->getMock();
		$sdk->expects($this->any())
			->method('setRequestBody')
			->will($this->returnSelf());
		$sdk->expects($this->any())
			->method('send')
			->will($this->returnSelf());
		return $sdk;
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
	 * Replace the customer session object with a mock.
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock session model
	 */
	protected function _mockCheckoutSession()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(null) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
		return $sessionMock;
	}

	/**
	 * Stub validator response methods with expected bahaviors.
	 *
	 * @param bool
	 * @param bool
	 * @param Mage_Customer_Model_Address_Abstract|null
	 * @param Mage_Customer_Model_Address_Abstract|null
	 * @param Mage_Customer_Model_Address_Abstract[]
	 * @return self
	 */
	protected function _mockValidationResponse(
		$isAddressValid=false,
		$hasSuggestions=false,
		$originalAddress=null,
		$validAddress=null,
		$addressSuggestions=[]
	)
	{
		$this->_validatorResponse->expects($this->any())
			->method('isAddressValid')
			->will($this->returnValue($isAddressValid));
		$this->_validatorResponse->expects($this->any())
			->method('getHasSuggestions')
			->will($this->returnValue($hasSuggestions));
		$this->_validatorResponse->expects($this->any())
			->method('getOriginalAddress')
			->will($this->returnValue($originalAddress));
		$this->_validatorResponse->expects($this->any())
			->method('getValidAddress')
			->will($this->returnValue($validAddress));
		$this->_validatorResponse->expects($this->any())
			->method('getAddressSuggestions')
			->will($this->returnValue($addressSuggestions));
		return $this;
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
	 * @param bool $hasFreshSuggestions
	 */
	protected function _setupSessionWithSuggestions($original, $suggestions, $hasFreshSuggestions=true)
	{
		// populate the session with usable data - replaced with mock in setUp
		$addresses = new EbayEnterprise_Address_Model_Suggestion_Group();
		$addresses->setOriginalAddress($original);
		$addresses->setSuggestedAddresses($suggestions);
		$addresses->setHasFreshSuggestions($hasFreshSuggestions);
		$this->_getSession()
			->setData(EbayEnterprise_Address_Model_Validator::SESSION_KEY, $addresses);
	}

	/**
	 * Test the various session interactions when there is not session data
	 * for address validation initialized.
	 */
	public function testValidatorSessionNotInitialized()
	{
		$this->assertNull($this->_validator->getOriginalAddress());
		$this->assertNull($this->_validator->getSuggestedAddresses());
		$this->assertNull($this->_validator->getStashedAddressByKey('original_address'));
		$this->assertFalse($this->_validator->hasSuggestions());
	}

	/**
	 * Make sure getAddressCollection *always* returns a
	 * EbayEnterprise_Address_Model_Suggestion_Group even if the
	 * session data where it expects to find the suggestions is polluted
	 * with something else.
	 */
	public function testGettingAddressCollectionAlwaysReturnsProperObjectType()
	{
		$expectedType = 'EbayEnterprise_Address_Model_Suggestion_Group';
		$session = $this->_getSession();
		$sessionKey = EbayEnterprise_Address_Model_Validator::SESSION_KEY;

		$session->setData($sessionKey, 'a string');
		$this->assertInstanceOf($expectedType, $this->_validator->getAddressCollection());

		$session->setData($sessionKey, new Varien_Object());
		$this->assertInstanceOf($expectedType, $this->_validator->getAddressCollection());

		$session->setData($sessionKey, 23);
		$this->assertInstanceOf($expectedType, $this->_validator->getAddressCollection());

		$session->setData($sessionKey, new EbayEnterprise_Address_Model_Suggestion_Group());
		$this->assertInstanceOf($expectedType, $this->_validator->getAddressCollection());

		$session->unsetData($sessionKey);
		$this->assertInstanceOf($expectedType, $this->_validator->getAddressCollection());
	}

	/**
	 * Test updating the submitted address with data from the chosen suggestion.
	 * @dataProvider dataProvider
	 */
	public function testUpdateAddressWithSelections($postValue)
	{
		$originalAddress = $this->_createAddress([
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address',
		]);
		$suggestions = [
			$this->_createAddress([
				'street' => '321 Main Rd',
				'city' => 'Barton',
				'region_code' => 'NY',
				'country_id' => 'US',
				'postcode' => '54321-1234',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/0',
			]),
			$this->_createAddress([
				'street' => '321 Main St',
				'city' => 'Fooville',
				'country_id' => 'US',
				'postcode' => '12345-6789',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/1',
			])
		];
		$this->_setupSessionWithSuggestions($originalAddress, $suggestions);

		// set the submitted value in the request post data
		Mage::app()->getRequest()->setPost(EbayEnterprise_Address_Block_Suggestions::SUGGESTION_INPUT_NAME, $postValue);

		// create an address object to act as the address submitted by the user
		$submittedAddress = $this->_createAddress([
			'street' => '1 Street Rd',
			'city' => 'Foo',
			'region_code' => 'PA',
			'region_id' => 51,
			'country_id' => 'US',
			'postcode' => '23456',
		]);

		// this is necessary due to expectation not allowing a / in the key to get expectations
		$expectationKey = str_replace('/', '', $postValue);

		EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_validator, '_updateAddressWithSelection', [$submittedAddress]);

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
	 */
	public function testValidateAddressVerified()
	{
		// address to feed to the validator
		$address = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13021-9523',
			'has_been_validated' => true,
		]);
		// original address from the response model
		$origAddress = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13021',
		]);

		$this->_mockValidationResponse(true, false, $address, $address);
		$errorMessage = $this->_validator->validateAddress($origAddress);
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
	 */
	public function testValidateAddressMultiSuggestions()
	{
		// address to feed to validator
		$address = $this->_createAddress([
			'street'      => '1671 Clark Street Rd',
			'city'        => 'Auburn',
			'region_code' => 'NY',
			'country_id'  => 'US',
			'postcode'    => '13025',
		]);
		// original address from response model
		$origAddress = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025-1234',
			'has_been_validated' => true,
		]);
		// suggestions from the response model
		$suggestions = [
			$this->_createAddress([
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			]),
			$this->_createAddress([
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			]),
		];
		$this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		$errorMessage = $this->_validator->validateAddress($address);

		$this->assertTrue(
			$address->getHasBeenValidated(),
			'"has_been_validated" data added to the address object.'
		);
		$this->assertSame(
			$origAddress->getPostcode(),
			$address->getPostcode(),
			'Corrected data in the "original_address" fields from service should still be copied over.'
		);
		$this->assertSame(
			EbayEnterprise_Address_Model_Validator::SUGGESTIONS_ERROR_MESSAGE,
			$errorMessage,
			'Invalid address with suggestions should have the appropriate message.'
		);
	}

	/**
	 * If a previously validated address is passed to the validate method,
	 * validation should assume the address is correct and
	 * should be successful (no errors returned) and
	 * should clear out any session data.
	 */
	public function testWithValidatedAddress()
	{
		$address = $this->_createAddress([
			'has_been_validated' => true
		]);

		// add some data into the customer session mock.
		$session = $this->_getSession();
		$session->setData(EbayEnterprise_Address_Model_Validator::SESSION_KEY, 'this should be cleared out');
		$errors = $this->_validator->validateAddress($address);

		$this->assertNull($errors, 'An address that is marked as already having been validated is assumed valid, hence no errors.');
		$this->assertInstanceOf('EbayEnterprise_Address_Model_Suggestion_Group', $session->getData(EbayEnterprise_Address_Model_Validator::SESSION_KEY));
	}

	/**
	 * Test the session interactions of the validation.
	 * Test for when - address does not need to be validated/has already been validated
	 * and is a billing/use for shipping address.
	 * @dataProvider dataProvider
	 */
	public function testSessionInteractionsNoValidationNecessary($useForShipping)
	{
		$address = $this->_createAddress([
			'street' => '123 Main St',
			'city' => 'Foo',
			'region_id' => 51,
			'country_id' => 'US',
			'postcode' => '12345',
			'address_type' => 'billing',
			'has_been_validated' => true
		]);

		Mage::app()->getRequest()->setPost('billing', ['use_for_shipping' => $useForShipping]);

		$this->_validator->validateAddress($address);

		// inspect session to make sure everything that needed to get set was
		// properly set
		$session = $this->_getSession();
		$group = $session->getData(EbayEnterprise_Address_Model_Validator::SESSION_KEY);

		$this->assertNotNull($group);

		// as the address was not validated/already valid, there shouldn't be
		// any address data on the group object
		// make retrieving address/suggestions don't change the has_fresh_suggestions flag
		$this->assertNull($group->getOriginalAddress(true));
		$this->assertNull($group->getSuggestedAddresses(true));
		$this->assertNull($group->getResponseMessage());
		$this->assertFalse($group->getHasFreshSuggestions());

		$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $group->getValidatedAddress('billing'));
		if ($useForShipping) {
			$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $group->getValidatedAddress('shipping'));
		}

	}

	/**
	 * Test session interaction.
	 * Test for when - address is invalid
	 */
	public function testSessionInteractionsInvalidAddress()
	{
		// address to feed to validator
		$address = $this->_createAddress([
			'firstname' => 'Foo',
			'lastname' => 'Bar',
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'address_type' => 'shipping'
		]);
		// original address from response model
		$origAddress = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		]);
		// suggestions from the response model
		$suggestions = [
			$this->_createAddress([
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			]),
			$this->_createAddress([
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			]),
		];

		// invalid response, with suggestions, original address, no valid address and suggestions
		$this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		$this->_validator->validateAddress($address);

		$session = $this->_getSession();
		$group = $session->getData(EbayEnterprise_Address_Model_Validator::SESSION_KEY);

		$this->assertTrue($group->getHasFreshSuggestions());

		$originalAddress = $group->getOriginalAddress(true);
		$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $originalAddress);
		// ensure the stash key was added to the session object
		$this->assertSame('original_address', $originalAddress->getStashKey());
		// ensure name data was copied over to the address from the response
		$this->assertSame($address->getName(), $originalAddress->getName());

		$suggestions = $group->getSuggestedAddresses(true);
		// should have 2 suggestions from the response
		$this->assertEquals(2, count($suggestions));
		// make sure stash keys were added
		$this->assertSame('suggested_addresses/0', $suggestions[0]->getStashKey());
		$this->assertSame('suggested_addresses/1', $suggestions[1]->getStashKey());
		// make sure name data was all copied over
		$this->assertSame($address->getName(), $suggestions[0]->getName());
		$this->assertSame($address->getName(), $suggestions[1]->getName());

		// the response message should have been added to the session in case it needs to
		// be queried for address data again later
		$this->assertSame($this->_validatorResponse, $group->getResponseMessage());

		// make sure the validated address data was added to the session
		// should have a shipping address but no billing address
		$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $group->getValidatedAddress('shipping'));
		$this->assertNull($group->getValidatedAddress('billing'));
	}

	/**
	 * Test the removal of session values.
	 */
	public function testCleaningOfSession()
	{
		$mockSession = $this->_getSession();
		$staleData = 'STALE_DATA';
		$mockSession->setAddressValidationAddresses($staleData);
		$this->assertNotNull($mockSession->getAddressValidationAddresses(), 'Session has address validation data.');
		$this->_validator->clearSessionAddresses();
		$this->assertNull($mockSession->getAddressValidationAddresses(), 'Session does not have address valdidation data.');
	}

	/**
	 * Asking the validator model to validate a new address should clear out
	 * any values it has populated in the session.
	 */
	public function ensureSessionClearedOnNewValidation()
	{
		// address to feed to validator
		$address = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		]);
		// original address from response model
		$origAddress = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		]);
		// suggestions from the response model
		$suggestions = [
			$this->_createAddress([
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			]),
			$this->_createAddress([
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			]),
		];
		$this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		// set up some stale data in the session that should be overwritten by the validator model
		$staleSessionData = 'STALE_DATA';
		$sessionKey = EbayEnterprise_Address_Model_Validator::SESSION_KEY;
		$mockSession = $this->_getSession();
		$mockSession->setData($sessionKey, $staleSessionData);
		// make sure it has been set
		$this->assertSame($staleSessionData, $mockSession->getData($sessionKey), 'Session has initial address validation data.');

		$this->_validator->validateAddress($address);

		$this->assertNotEquals(
			$staleSessionData,
			$mockSession->getData($sessionKey),
			'Stale session data replaced by new address validation data.'
		);
	}

	/**
	 * This is a very odd scenario and really should never happen.
	 */
	public function errorMessageWithInvalidMessageAndNoSuggestions()
	{
		// address to feed to validator
		$address = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		]);
		// original address from response model
		$origAddress = $this->_createAddress([
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		]);
		$this->_mockValidationResponse(false, false, $origAddress, null);

		$errorMessage = $this->_validator->validateAddress($address);

		$this->assertSame(
			$errorMessage,
			EbayEnterprise_Address_Model_Validator::NO_SUGGESTIONS_ERROR_MESSAGE
		);
	}

	/**
	 * Test that when no message is received from the address validation service
	 * the address is considered valid.
	 */
	public function testEmptyResponseFromService()
	{
		// address to validate
		$address = $this->_createAddress([
			'street' => '123 Main St',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		]);
		$this->_sdkApi->expects($this->any())
			->method('send')
			->will($this->throwException(new eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError));
		$errors = $this->_validator->validateAddress($address);

		$this->assertNull($errors);
	}
	/**
	 * Test retrieval of address objects from the validator by key.
	 * Each address should have a stash_key which will be used to get
	 * the address back out of the address collection stored in the session.
	 */
	public function getStashedAddressByKeyByKey()
	{
		$originalAddress = $this->_createAddress([
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address',
		]);
		$suggestions = [
			$this->_createAddress([
				'street' => '321 Main Rd',
				'city' => 'Barton',
				'region_code' => 'NY',
				'country_id' => 'US',
				'postcode' => '54321-1234',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/0',
			]),
			$this->_createAddress([
				'street' => '321 Main St',
				'city' => 'Fooville',
				'country_id' => 'US',
				'postcode' => '12345-6789',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/1',
			])
		];
		$this->_setupSessionWithSuggestions($originalAddress, $suggestions);

		$this->assertSame(
			$originalAddress,
			$this->_validator->getStashedAddressByKey($originalAddress->getStashKey())
		);
		$this->assertSame(
			$suggestions[1],
			$this->_validator->getStashedAddressByKey($suggestions[1]->getStashKey())
		);
	}

	/**
	 * Trying to get a validated address with an unknown key will return null
	 */
	public function gettingValidatedAddressByUnknownKey()
	{
		$this->assertNull($this->_validator->getStashedAddressByKey('dont_know_about_this'), 'Unknown "stash_key" results in "null" response.');
	}

	/**
	 * Test getting the original address out of the session
	 */
	public function testGetOriginalAddress()
	{
		// set up an address object to be put into the session
		$origAddress = $this->_createAddress(([
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_code' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address'
		]));
		// create the EbayEnterprise_Address_Model_Suggestion_Group that stores address data in the session
		$addresses = new EbayEnterprise_Address_Model_Suggestion_Group();
		$addresses->setOriginalAddress($origAddress);
		// add the address data to the session
		$this->_getSession()->setData(
			EbayEnterprise_Address_Model_Validator::SESSION_KEY,
			$addresses
		);

		$this->assertSame(
			$origAddress,
			$this->_validator->getOriginalAddress()
		);
	}

	/**
	 * Test getting the list of suggested addresses.
	 */
	public function testGetSuggestedAddresses()
	{
		// set up suggested addresses to place into the session
		$suggestions = [
			$this->_createAddress(
				[
					'street' => '123 Main St',
					'city' => 'Fooville',
					'region_code' => 'NY',
					'country_code' => 'US',
					'postcode' => '12345',
				],
				[
					'street' => '321 Main Rd',
					'city' => 'Bartown',
					'region_code' => 'PA',
					'country_code' => 'US',
					'postcode' => '19231',
				]
			)
		];
		// create the EbayEnterprise_Address_Model_Suggestion_Group that stores the address data in the session
		$addressCollection = new EbayEnterprise_Address_Model_Suggestion_Group();
		$addressCollection->setSuggestedAddresses($suggestions);
		// add suggestions to the session
		$this->_getSession()->setData(
			EbayEnterprise_Address_Model_Validator::SESSION_KEY,
			$addressCollection
		);

		$this->assertSame(
			$suggestions,
			$this->_validator->getSuggestedAddresses()
		);
	}

	/**
	 * Test copying name data form the source address to the destination address.
	 */
	public function testCopyNameData()
	{
		$destData = [
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'PA',
			'country_code' => 'US',
			'postcode' => '12345',
		];
		$destAddr = $this->_createAddress($destData);
		$sourceData = [
			'firstname' => 'The',
			'lastname' => 'Bar',
			'street' => '555 Foo St',
			'city' => 'Bartown',
			'region_code' => 'NY',
			'country_code' => 'US',
			'postcode' => '12345-6789',
		];
		$sourceAddr = $this->_createAddress($sourceData);

		EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_validator, '_copyAddressName', [$destAddr, $sourceAddr]);

		// first and last name should be copied to the dest address
		$this->assertSame($sourceAddr->getFirstname(), $destAddr->getFirstname());
		$this->assertSame($sourceAddr->getLastname(), $destAddr->getLastname());
		// rest of the dest address should not have changed
		$this->assertSame($destData['city'], $destAddr->getCity());
		$this->assertSame($destData['postcode'], $destAddr->getPostcode());
	}

	/**
	 * Test checking for fresh suggestions.
	 * Really just a pass through to another object which is responsible for managing the flag.
	 */
	public function testCheckSessionFreshness()
	{
		$this->_setupSessionWithSuggestions(null, null, false);
		$this->assertFalse($this->_validator->hasFreshSuggestions());
	}

	/**
	 * Test the test for there to be suggestions.
	 * @dataProvider dataProvider
	 */
	public function testHasSuggestions($shouldHaveSuggestions)
	{
		// something to populate the Suggestion_Group suggestions with,
		// anything not empty will produce a true result for hasSuggestions
		$suggestions = ($shouldHaveSuggestions)
			? [Mage::getModel('customer/address'), Mage::getModel('customer/address')]
			: [];
		$group = $this->getModelMock('ebayenterprise_address/suggestion_group',
			['getSuggestedAddresses']
		);
		$group->expects($this->once())
			->method('getSuggestedAddresses')
			->with($this->equalTo(true))
			->will($this->returnValue($suggestions));
		$this->replaceByMock('model', 'ebayenterprise_address/suggestion_group', $group);

		$this->assertEquals($shouldHaveSuggestions, $this->_validator->hasSuggestions());
	}

	/**
	 * Test the should validate method for addresses that have already been validated,
	 * in particular, addresses that have had a 'has_been_validated' flag set,
	 * e.g. addresses that have been extracted from the response message.
	 */
	public function testShouldValidateFlaggedAsValidated()
	{
		$address = $this->getModelMock('customer/address', ['getHasBeenValidated']);
		$address->expects($this->once())
			->method('getHasBeenValidated')
			->will($this->returnValue(true));
		$this->assertFalse($this->_validator->shouldValidateAddress($address));
	}

	/**
	 * Test the should validate method for addresses that have already been validated,
	 * in particular addresses that match an address of the same type that has already
	 * been validated. This check is primarilly used for detecting superflous calls
	 * to the validateAddress method. Tests any scenarios that will result in a false response
	 * indicating the address has already been validated and should not be validated.
	 *
	 * @dataProvider dataProvider
	 */
	public function testShouldValidatedAlreadyValidatedAddress($addressType, $postBillingUseForShipping, $validatedType)
	{
		$address = $this->getModelMock('customer/address', ['getHasBeenValidated', 'getAddressType', 'getData']);
		$address->expects($this->any())
			->method('getHasBeenValidated')
			->will($this->returnValue(false));
		$address->expects($this->any())
			->method('getAddressType')
			->will($this->returnValue($addressType));
		$addressData = [
			['street',    null, '123 Main St'],
			['city',      null, 'Fooville'],
			['region_id', null, 51],
		];
		$address->expects($this->any())
			->method('getData')
			->will($this->returnValueMap($addressData));

		// setup the POST data accordingly
		if ($postBillingUseForShipping !== null) {
			$_POST['billing'] = ['use_for_shipping' => $postBillingUseForShipping];
		}

		$group = $this->getModelMock('ebayenterprise_address/suggestion_group', ['getValidatedAddress']);
		$validatedAddress = $this->getModelMock('customer/address', ['getData']);
		$validatedAddressData = [
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_id' => '51',
			'address_type' => 'shipping',
		];
		$validatedAddress->expects($this->any())
			->method('getData')
			->will($this->returnValue($validatedAddressData));
		$group->expects($this->any())
			->method('getValidatedAddress')
			->with($this->equalTo($validatedType))
			->will($this->returnValue($validatedAddress));
		$this->replaceByMock('model', 'ebayenterprise_address/suggestion_group', $group);

		$this->assertFalse($this->_validator->shouldValidateAddress($address));
	}

	/**
	 * Test that an address should be validated when:
	 * - not a checkout address
	 * - and has not yet been validated.
	 */
	public function testShouldValidateNonCheckouNotValidated()
	{
		$address = $this->getModelMock('customer/address', ['getHasBeenValidated', 'getAddressType', 'getData']);
		$address->expects($this->any())
			->method('getHasBeenValidated')
			->will($this->returnValue(false));
		$address->expects($this->any())
			->method('getAddressType')
			->will($this->returnValue('shipping'));
		// any one of these being different from the validatedAddressData below
		// will cause the comparison check fail
		$addressData = [
			['street',    null, 'Borg'],
			['city',      null, 'Barton'],
			['region_id', null, 41],
		];
		$address->expects($this->any())
			->method('getData')
			->will($this->returnValueMap($addressData));

		$group = $this->getModelMock('ebayenterprise_address/suggestion_group', ['getValidatedAddress']);
		$validatedAddress = $this->getModelMock('customer/address', ['getData']);
		$validatedAddressData = [
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_id' => '51',
			'address_type' => 'shipping',
		];
		$validatedAddress->expects($this->any())
			->method('getData')
			->will($this->returnValue($validatedAddressData));
		$group->expects($this->any())
			->method('getValidatedAddress')
			->will($this->returnValue($validatedAddress));
		$this->replaceByMock('model', 'ebayenterprise_address/suggestion_group', $group);

		$this->assertTrue($this->_validator->shouldValidateAddress($address));
	}

	/**
	 * Test detecting that an address is being used in checkout.
	 */
	public function testDetectingACheckoutAddress()
	{
		$address = Mage::getModel('customer/address');
		$address->addData([
			'street' => '123 Main St',
			'city' => 'Foo',
			'region_id' => 41,
			'country_id' => 'US',
		]);

		// checkout address will have a quote_id
		$address->setData('quote_id', 12);
		$this->assertTrue(EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_validator, '_isCheckoutAddress', [$address]));

		// non-checkout address will not
		$address->unsetData('quote_id');
		$this->assertFalse(EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_validator, '_isCheckoutAddress', [$address]));
	}

	/**
	 * Test that address being loaded from the address book are not validated when
	 * used in checkout.
	 * @dataProvider dataProvider
	 * @long
	 */
	public function testAddressBookAddressShouldNotBeValidated($id, $customerId, $customerAddressId)
	{
		$address = $this->getModelMock('customer/address', ['hasData', 'getId', 'getCustomerId', 'getCustomerAddressId']);
		// make sure this is a checkout address
		$address->expects($this->any())
			->method('hasData')
			->with($this->matches('quote_id'))
			->will($this->returnValue(1));
		$address->expects($this->any())
			->method('getId')
			->will($this->returnValue($id));
		$address->expects($this->any())
			->method('getCustomerId')
			->will($this->returnValue($customerId));
		$address->expects($this->any())
			->method('getCustomerAddressId')
			->will($this->returnValue($customerAddressId));

		$this->_validator = $this->getModelMock('ebayenterprise_address/validator', ['_isMissingRequiredFields']);
		$this->assertEquals(
			!($id && $customerId && $customerAddressId),
			$this->_validator->shouldValidateAddress($address)
		);
	}

	/**
	 * Check for an address that is marked to be saved in the users address book.
	 * As address that are saved get validated, which is the normal case,
	 * in order to ensure unique results for addresses being saved, we need to force
	 * one other the other, non-validation triggers to catch. Most direct way is likely by
	 * marking the order as a virtual order. As a result,
	 * this also tests should validate for virtual orders.
	 * @dataProvider dataProvider
	 */
	public function testAddressBeingSavedInAddressBookAndIsVirtual($postType, $postFlag, $checkoutMethod)
	{
		$address = Mage::getModel('customer/address');
		// address must be checkout address - have a quote_id,
		// and not be from the address book - no id, customer_id or customer_address_id
		$address->addData([
			'quote_id' => 12,
			'address_type' => 'shipping',
		]);

		// set up the checkout session with a quote mock which will report
		// it as being a virtual order
		$quote = $this->getModelMock('sales/quote', ['isVirtual']);
		$quote->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));

		$checkout = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(['getQuote']) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$checkout->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$this->replaceByMock('singleton', 'checkout/session', $checkout);

		/*
		 * set up the post data necessary, there needs to be either a
		 * shipping[save_in_address_book] or billing[save_in_address_book] submitted
		 */
		if ($postType === 'billing' || $postType === 'shipping') {
			$_POST[$postType] = ['save_in_address_book' => $postFlag];
		}

		// set up the checkout type
		$onepage = $this->getModelMock('checkout/type_onepage', ['getCheckoutMethod']);
		$onepage->expects($this->once())
			->method('getCheckoutMethod')
			->will($this->returnValue($checkoutMethod));
		$this->replaceByMock('singleton', 'checkout/type_onepage', $onepage);

		$expectation = $this->expected('%s-%s-%s', $postType, $postFlag, $checkoutMethod);
		$this->assertSame(
			$expectation->getShouldValidateAddress(),
			$this->_validator->shouldValidateAddress($address)
		);
	}

	/**
	 * When there is an order present, method should get the isVirtual result
	 * from it, otherwise should return false.
	 * @dataProvider dataProvider
	 */
	public function testVirtualQuoteAddress($hasQuote, $isVirtual)
	{
		$quote = null;
		if ($hasQuote) {
			$quote = $this->getModelMock('sales/quote', ['isVirtual']);
			$quote->expects($this->once())
				->method('isVirtual')
				->will($this->returnValue($isVirtual));
		}
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(['getQuote'])
			->getMock();
		$sessionMock->expects($this->once())
			->method('getQuote')
			->will($this->returnValue($quote));
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);

		$this->assertSame($isVirtual, EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_validator, '_isVirtualOrder'));
	}

	/**
	 * Detect that an address is only used for billing and does not
	 * need to be validated.
	 * @dataProvider dataProvider
	 */
	public function testShouldValidateBillingOnlyAddress($addressType, $hasBillingPost, $useForShipping)
	{
		$address = Mage::getModel('customer/address');
		// must be checkout address - have quote_id
		$address->addData([
			'quote_id' => 1,
			'address_type' => $addressType
		]);
		if ($hasBillingPost) {
			$_POST['billing'] = ['use_for_shipping' => $useForShipping];
		}

		$expectations = $this->expected('%s-%s-%s', $addressType, $hasBillingPost, $useForShipping);
		$this->_validator = $this->getModelMock('ebayenterprise_address/validator', ['_isMissingRequiredFields']);
		$this->assertSame(
			$expectations->getShouldValidate(),
			$this->_validator->shouldValidateAddress($address)
		);
	}

	/**
	 * When a shipping address is marked same_as_billing but is not valid, the
	 * address should no longer be marked same_as_billing. This is part of the
	 * means of preventing a non-validated billing only address from
	 * being used as a shipping address and bypassing validation.
	 *
	 * @dataProvider dataProvider
	 */
	public function testSetSameAsBillingFlagWhenAddressIsInvalid($isValid, $sameAsBilling)
	{
		$this->_validatorResponse->expects($this->any())
			->method('getOriginalAddress')
			->will($this->returnValue(Mage::getModel('customer/address')));
		$this->_validatorResponse->expects($this->any())
			->method('getAddressSuggestions')
			->will($this->returnValue([Mage::getModel('customer/address'), Mage::getModel('customer/address')]));
		$this->_validatorResponse->expects($this->any())
			->method('isAddressValid')
			->will($this->returnValue($isValid));
		$this->_validatorResponse->expects($this->any())
			->method('getValidAddress')
			->will($this->returnValue($isValid ? $this->_validatorResponse->getOriginalAddress() : null));

		$address = $this->getModelMock('customer/address', ['getSameAsBilling', 'setSameAsBilling']);
		// when valid, don't care about the same_as_billing flag so neither should be called
		if ($isValid) {
			$address->expects($this->never())
				->method('getSameAsBilling');
			$address->expects($this->never())
				->method('setSameAsBilling');
		} else {
			// when invalid address, need to check if the address is marked same as billing
			$address->expects($this->once())
				->method('getSameAsBilling')
				->will($this->returnValue($sameAsBilling));
			// when same_as_billing is true, flag should be set to false
			if ($sameAsBilling) {
				$address->expects($this->once())
					->method('setSameAsBilling')
					->with($this->matches(false));
			} else {
				// when not the same, no attempt to change it should be made
				$address->expects($this->never())
					->method('setSameAsBilling');
			}
		}

		$this->_validator->validateAddress($address);
	}

	/**
	 * Test for the last response from address validation to have
	 * contained a valid address.
	 * @dataProvider dataProvider
	 */
	public function testIsValid($hasResponse, $isValid)
	{
		$group = $this->getModelMock('ebayenterprise_address/suggestion_group', ['getResponseMessage']);
		if ($hasResponse) {
			$this->_validatorResponse->expects($this->any())
				->method('isAddressValid')
				->will($this->returnValue($isValid));
			$group->expects($this->any())
				->method('getResponseMessage')
				->will($this->returnValue($this->_validatorResponse));
		} else {
			$group->expects($this->any())
				->method('getResponseMessage')
				->will($this->returnValue(null));
		}
		$this->replaceByMock('model', 'ebayenterprise_address/suggestion_group', $group);
		$this->assertSame(
			$this->expected('%s-%s', $hasResponse, $isValid)->getIsValid(),
			$this->_validator->isValid()
		);
	}

	/**
	 * provide methods that should return the equivalent of a true result.
	 */
	public function provideNonEmptyGetters()
	{
		return [
			[['getStreet1', 'getCity', 'getCountry'], true],
			[['getStreet1', 'getCity'], false],
			[['getStreet1'], false],
			[[], false],
		];
	}
	/**
	 * if any one of the required fields are missing, return true; false otherwise.
	 * @param  array  $nonEmptyGetters
	 * @param  bool   $result
	 * @dataProvider provideNonEmptyGetters
	 */
	public function testShouldValidateIfIsMissingRequiredFields($nonEmptyGetters, $result) {
		$address = $this->getModelMock('customer/address', $nonEmptyGetters);
		$validator = $this->getModelMock('ebayenterprise_address/validator', [
			'_hasAddressBeenValidated',
			'_isCheckoutAddress',
			'_isAddressFromAddressBook',
			'_isAddressBeingSaved',
			'_isVirtualOrder',
			'_isAddressBillingOnly'
		]);
		$validator->expects($this->any())
			->method('_isCheckoutAddress')
			->will($this->returnValue(true));

		foreach ($nonEmptyGetters as $method) {
			$address->expects($this->once())
				->method($method)
				->will($this->returnValue(true));
		}
		$this->assertSame($result, $validator->shouldValidateAddress($address));
	}
}
