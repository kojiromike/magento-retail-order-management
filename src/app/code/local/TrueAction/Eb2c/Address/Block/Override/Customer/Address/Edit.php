<?php

class TrueAction_Eb2c_Address_Block_Override_Customer_Address_Edit
	extends Mage_Customer_Block_Address_Edit
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
