<?php

class TrueAction_Eb2c_Address_Block_Override_Giftregistry_Customer_Address_Edit
	extends Enterprise_GiftRegistry_Block_Customer_Address_Edit
{

	/**
	 * Are there suggestions to show.
	 * @return boolean
	 */
	public function hasSuggestions()
	{
		return Mage::getModel('eb2caddress/validator')->hasSuggestions();
	}

}