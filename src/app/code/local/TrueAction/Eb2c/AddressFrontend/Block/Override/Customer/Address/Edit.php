<?php

class TrueAction_Eb2c_AddressFrontend_Block_Override_Customer_Address_Edit
	extends Mage_Customer_Block_Address_Edit
{

	/**
	 * Should the use be presented with a list of suggested addresses.
	 * @return boolean
	 */
	public function hasAddressSuggestions()
	{
		$suggestions = Mage::getSingleton('eb2caddress/validator')
			->getAddressCollection();
		return empty($suggestions);
	}

}