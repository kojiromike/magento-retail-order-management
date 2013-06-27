<?php
/**
 * Handles validating address via the EB2C address validation service,
 * storing and retrieving address suggestions.
 */
class TrueAction_Eb2c_Address_Model_Validator
{

	const SESSION_KEY = 'address_validation_addresses';
	const SUGGESTIONS_ERROR_MESSAGE = 'The address could not be validated. Please select one of the suggestions.';
	const NO_SUGGESTIONS_ERROR_MESSAGE = 'The address could not be validated. Please provide a new address.';
	/**
	 * Get the session object to use for storing address information.
	 * Currently will use the customer session but may be swapped out later.
	 * @return Mage_Core_Model_Session_Abstract
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Validate an address via the EB2C Address Validation service.
	 * Calls the EB2C API and feeds the results into a response model.
	 * Will also ensure that the supplied address is populated with
	 * the response from EB2C and suggested addresses are stashed in the session
	 * for later use.
	 * @param Mage_Customer_Model_Address_Abstract $address
	 * @return string - the error message generated in validation
	 */
	public function validateAddress(Mage_Customer_Model_Address_Abstract $address)
	{
		$errorMessage = null;
		if (!$address->getHasBeenValidated()) {
			$this->clearSessionAddresses();
			$helper = Mage::helper('eb2ccore');
			$request = Mage::getModel('eb2caddress/validation_request')->setAddress($address);
			$response = Mage::getModel('eb2caddress/validation_response')->setMessage(
				$helper->callApi(
					$request->getMessage(),
					$helper->apiUri(
						TrueAction_Eb2c_Address_Model_Validation_Request::API_SERVICE,
						TrueAction_Eb2c_Address_Model_Validation_Request::API_OPERATION
					)
				)
			);
			// copy over validated address data
			if ($response->isAddressValid()) {
				$address->addData($response->getValidAddress()->getData());
			} else {
				$address->addData($response->getOriginalAddress()->getData());
				$errorMessage = '';
				if ($response->hasSuggestions()) {
					$errorMessage = Mage::helper('eb2caddress')
						->__(self::SUGGESTIONS_ERROR_MESSAGE);
				} else {
					$errorMessage = Mage::helper('eb2caddress')
						->__(self::NO_SUGGESTIONS_ERROR_MESSAGE);
				}
			}
			$this->_stashAddresses($response);
		}
		return $errorMessage;
	}

	/**
	 * Store the necessary addresses and address data in the session.
	 * @param TrueAction_Eb2c_Address_Model_Validation_Response $response
	 */
	protected function _stashAddresses(TrueAction_Eb2c_Address_Model_Validation_Response $response)
	{
		$addressCollection = array();
		$addressCollection['original'] = $response->getOriginalAddress();
		$addressCollection['suggestions'] = array();
		foreach ($response->getAddressSuggestions() as $idx => $suggestedAddress) {
			$addressCollection['suggestions'][$idx] = $suggestedAddress;
		}
		$this->_getSession()->setAddressValidationAddresses($addressCollection);
	}

	/**
	 * Return the collection (key=>value pairs) of addresses for address validation.
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getAddressCollection()
	{
		return $this->_getSession()->getAddressValidationAddresses();
	}

	/**
	 * Get the address returned as the "original" address from EB2C.
	 * @return Mage_Customer_Model_Address
	 */
	public function getOriginalAddress()
	{
		$addresses = $this->getAddressCollection();
		return $addresses['original'];
	}

	/**
	 * Get the suggested address returned by EB2C
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getSuggestedAddresses()
	{
		$addresses = $this->getAddressCollection();
		return $addresses['suggestions'];
	}

	/**
	 * Return the address from the session represented by the given key.
	 * If no address for that key exists, returns null.
	 * @return Mage_Customer_Model_Address
	 */
	public function getValidatedAddress($key)
	{
		$addressCollection = $this->getAddressCollection();
		if (isset($addressCollection[$key])) {
			return $addressCollection[$key];
		}
		return null;
	}

	/**
	 * Remove the collection of addresses from the session.
	 * @return TrueAction_Eb2c_Address_Model_Validator $this
	 */
	public function clearSessionAddresses()
	{
		$this->_getSession()->unsetData(self::SESSION_KEY);
		return $this;
	}

}
