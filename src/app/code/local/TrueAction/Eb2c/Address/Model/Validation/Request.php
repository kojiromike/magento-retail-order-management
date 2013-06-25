<?php

/**
 * Generate the xml request to the AddressValidation service
 * @method Mage_Sales_Model_Quote getQuote
 * @method TrueAction_Eb2c_Address_Model_Validation_Request setQuote(Mage_Sales_Model_Quote $quote)
 */
class TrueAction_Eb2c_Address_Model_Validation_Request
	extends Mage_Core_Model_Abstract
{

	const API_SERVICE = 'address';
	const API_OPERATION = 'validate';
	const API_FORMAT = 'xml';

	const DOM_ROOT_NODE_NAME = 'AddressValidationRequest';
	const DOM_ROOT_NS = 'http://api.gsicommerce.com/sehcma/checkout/1.0';

	/**
	 * DOMDocument used to build the request message
	 * @var TrueAction_Dom_Document
	 */
	protected $_dom;

	/**
	 * Get the number of max suggestions from the address configuration
	 * @return string
	 */
	protected function _configMaxSuggestions()
	{
		return Mage::helper('eb2ccore/config')
			->addConfigModel(Mage::getSingleton('eb2caddress/config'))
			->maxAddressSuggestions;
	}

	/**
	 * Get the DOMDocument (TrueAction_Dom_Document)
	 * to be sent with this message.
	 * @return TrueAction_Dom_Document
	 */
	public function getMessage()
	{
		$this->_dom = new TrueAction_Dom_Document('1.0', 'UTF-8');
		if ($this->hasData('address')) {
			$this->_dom->addElement(self::DOM_ROOT_NODE_NAME, null, self::DOM_ROOT_NS);
			$this->_dom->documentElement->appendChild($this->_createMessageHeader());
			$this->_dom->documentElement->appendChild($this->_createMessageAddress());
		}
		return $this->_dom;
	}

	/**
	 * Create a document fragment for the <Header>...</Header> portion of the message.
	 * @return DOMDocumentFragment
	 */
	protected function _createMessageHeader()
	{
		$dom = $this->_dom;
		$fragment = $dom->createDocumentFragment();

		$fragment->appendChild(
			$dom->createElement('Header',
				$dom->createElement('MaxAddressSuggestions',
					$this->_configMaxSuggestions()
				)
			)
		);
		return $fragment;
	}

	/**
	 * Create a document fragment for the <Address>...</Address> portion of the message.
	 * @return DOMDocumentFragment
	 */
	protected function _createMessageAddress()
	{
		$dom = $this->_dom;
		$fragment = $dom->createDocumentFragment();

		$fragment->appendChild(
			$dom->createElement('Address',
				Mage::helper('eb2caddress')
					->addressToPhysicalAddressXml($this->getAddress(), $this->_dom)
			)
		);
		return $fragment;
	}

}
