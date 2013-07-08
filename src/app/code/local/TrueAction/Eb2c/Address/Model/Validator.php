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
	 * If a selection has been made, update the address object with data
	 * from the stashed address. This will include copying over the
	 * has_been_validated flag, which will bypass re-validating the address.
	 * @return Mage_Customer_Model_Address_Abstract
	 */
	protected function _updateAddressWithSelection(Mage_Customer_Model_Address_Abstract $address)
	{
		$suggestionAddress = $this->getValidatedAddress(
			Mage::app()
				->getRequest()
				->getPost(TrueAction_Eb2c_Address_Block_Suggestions::SUGGESTION_INPUT_NAME)
		);
		if ($suggestionAddress) {
			$address->addData($suggestionAddress->getData());
		}
		return $address;
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
		$address = $this->_updateAddressWithSelection($address);
		if (!$address->getHasBeenValidated()) {
			$this->clearSessionAddresses();
			$helper = Mage::helper('eb2ccore');
			$request = Mage::getModel('eb2caddress/validation_request')->setAddress($address);
			$response = Mage::getModel('eb2caddress/validation_response')
				->setMessage(
					Mage::getModel('eb2ccore/api')
						->setUri($helper->getApiUri(
							TrueAction_Eb2c_Address_Model_Validation_Request::API_SERVICE,
							TrueAction_Eb2c_Address_Model_Validation_Request::API_OPERATION
						))
						->request($request->getMessage())
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
					// I don't think we ever want this to happen.
					Mage::log('EB2C Address: Address considered invalid but not suggestions given.', Zend_Log::WARN);
					$errorMessage = Mage::helper('eb2caddress')
						->__(self::NO_SUGGESTIONS_ERROR_MESSAGE);
				}
			}
			$this->_stashAddresses($response, $address);
		} else {
			// When the address has already been validated, it means the address has come
			// from the session. When this happens, the session data has been used and
			// should be cleared out to prevent it from being reused.
			$this->clearSessionAddresses();
		}
		return $errorMessage;
	}

	/**
	 * Merge "magic" data from source to destination.
	 * Will create a clone of the destination object and return the new object.
	 * Neither of the original objects will be modified.
	 * @param Varien_Object $dest
	 * @param Varien_Object $source
	 * @return Varien_Object
	 */
	protected function _cloneMerge(Varien_Object $dest, Varien_Object $source)
	{
		$clone = clone $dest;
		return $clone->addData($source->getData());
	}

	/**
	 * Store the necessary addresses and address data in the session.
	 * @param TrueAction_Eb2c_Address_Model_Validation_Response $response
	 */
	protected function _stashAddresses(
		TrueAction_Eb2c_Address_Model_Validation_Response $response,
		Mage_Customer_Model_Address_Abstract $requestAddress
	) {
		$addressCollection = Mage::getModel('eb2caddress/suggestion_group');
		$addressCollection->setOriginalAddress(
			$this->_cloneMerge(
				$requestAddress,
				$response->getOriginalAddress()->setStashKey('original_address')
			)
		);
		$suggestions = $response->getAddressSuggestions();
		$mergedSuggestions = array();
		foreach ($suggestions as $idx => $suggestion) {
			$mergedSuggestions[] = $this->_cloneMerge(
				$requestAddress,
				$suggestion->setStashKey('suggested_addresses/' . $idx)
			);
		}
		$addressCollection->setSuggestedAddresses($suggestions);
		$addressCollection->setResponseMessage($response);
		$this->_getSession()->setAddressValidationAddresses($addressCollection);
	}

	/**
	 * Return a Varien_Object containing stashed data about address validation and
	 * validated addresses. Most of the properties it contains are retrievable
	 * from this class so it is unlikely this will need to be called publicly.
	 * @return Varien_Object
	 */
	public function getAddressCollection()
	{
		$collection = $this->_getSession()->getData(self::SESSION_KEY);
		return ($collection instanceof TrueAction_Eb2c_Address_Model_Suggestion_Group)
			? $collection
			: new TrueAction_Eb2c_Address_Model_Suggestion_Group();
	}

	/**
	 * Get the address returned as the "original" address from EB2C.
	 * @return Mage_Customer_Model_Address
	 */
	public function getOriginalAddress()
	{
		return $this->getAddressCollection()->getOriginalAddress();
	}

	/**
	 * Get the suggested address returned by EB2C
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getSuggestedAddresses()
	{
		return $this->getAddressCollection()->getSuggestedAddresses();
	}

	/**
	 * Return the address from the session represented by the given key.
	 * If no address for that key exists, returns null.
	 * @return Mage_Customer_Model_Address
	 */
	public function getValidatedAddress($key)
	{
		return $this->getAddressCollection()->getData($key);
	}

	/**
	 * Returns whether or not there are address suggestions stored in the session
	 * and they should be shown to the user.
	 * @return boolean
	 */
	public function hasSuggestions()
	{
		// @TODO this logic is likely going to need to be a more
		// robust to prevent stale session data from being used.
		$suggestions = $this->getSuggestedAddresses();
		return !empty($suggestions);
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
