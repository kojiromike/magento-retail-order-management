<?php
/**
 * Handles validating address via the EB2C address validation service,
 * storing and retrieving address suggestions.
 */
class TrueAction_Eb2c_Address_Model_Validator
	extends Mage_Core_Model_Abstract
{

	/**
	 * Validate an address via the EB2C Address Validation service.
	 * @param Mage_Customer_Model_Address_Abstract $address
	 */
	public function validateAddress(Mage_Customer_Model_Address_Abstract $address)
	{
		if (!$address->getHasBeenValidated()) {
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
			$address->addData($response->getOriginalAddress()->getData());
			Mage::getSingleton('customer/session')
				->setAddressSuggestions($response->getAddressSuggestions());
		}
	}

}
