<?php

class TrueAction_Eb2c_Address_Block_Suggestions extends Mage_Core_Block_Template
{

	/**
	 * Return the list of suggested addresses.
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getSuggestedAddresses()
	{
		return Mage::getSingleton('eb2caddress/validator')->getSuggestedAddresses();
	}

	/**
	 * Get the address object for the original address submitted to the service.
	 * @return Mage_Customer_Model_Address
	 */
	public function getOriginalAddress()
	{
		return Mage::getSingleton('eb2caddress/validation')->getOriginalAddress();
	}

}
