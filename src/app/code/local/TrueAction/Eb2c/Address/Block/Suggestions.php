<?php

class TrueAction_Eb2c_Address_Block_Suggestions extends Mage_Core_Block_Template
{

	protected $_messages = array(
		'suggested_address' => 'Please choose one of the suggestions below.',
		'original_address' => 'Use the original address.',
		'new_address' => 'Provide a new address.',
	);

	/**
	 * Determines if there are suggestions to display to the user.
	 */
	public function shouldShowSuggestions()
	{
		return Mage::getSingleton('eb2caddress/validator')->hasSuggestions();
	}

	/**
	 * Return an array of suggested addresses.
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

	/**
	 * Return the formatted addresses, using the EB2C AddressFrontend address template.
	 */
	public function getRenderedAddress(Mage_Customer_Model_Address_Abstract $address)
	{
		return Mage::helper('customer/address')
			->getRenderer('eb2caddress/address_renderer')
			->render($address);

	}

	/**
	 * Get the user facing messages, ensuring they are all run through the
	 * __() translation method.
	 * @return string
	 */
	protected function _getMessage($name)
	{
		return Mage::helper('eb2caddress')->__($this->_messages[$name]);
	}

	/**
	 * Get the message to show above suggested addresses.
	 * @return string
	 */
	public function getSuggestedAddressMessage()
	{
		return $this->_getMessage('suggested_address');
	}

	/**
	 * Get the message to display with the selection to choose the original address.
	 * @return string
	 */
	public function getOriginalAddressMessage()
	{
		return $this->_getMessage('original_address');
	}

	/**
	 * Get the message to show with the selection to supply a new address.
	 * @return string
	 */
	public function getNewAddressMessage()
	{
		return $this->_getMessage('new_address');
	}

}
