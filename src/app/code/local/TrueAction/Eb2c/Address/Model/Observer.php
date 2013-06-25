<?php

/**
 * Observer for address validation events.
 */
class TrueAction_Eb2c_Address_Model_Observer
{

	public function validateAddress($observer)
	{
		Mage::getModel('eb2caddress/validator')
			->validateAddress($observer->getEvent()->getAddress());
	}

}