<?php

/**
 * Observer for address validation events.
 */
class TrueAction_Eb2c_Address_Model_Observer
{

	public function validateAddress($observer)
	{
		$request = Mage::getModel('eb2caddress/validation_request');
		$response = Mage::getModel('eb2caddres/validation_response');
		$response->setMessage(
			Mage::helper('eb2ccore')->callApi($request->getMessage(), $request->getApiUri())
		);
		if (!$response->isAddressValid()) {
			$session = Mage::getSingleton('eb2caddress/session')
				->setOriginalAddress($response->getOriginalAddress())
				->setSuggestedAddresses($response->getSuggestions());
				// throw exception or otherwise indicate an error
		}
	}

}