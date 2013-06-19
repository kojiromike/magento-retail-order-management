<?php

/**
 * Generate the xml request to the AddressValidation service
 * @method Mage_Sales_Model_Quote getQuote
 * @method TrueAction_Eb2c_Address_Model_Validation_Request setQuote(Mage_Sales_Model_Quote $quote)
 */
class TrueAction_Eb2c_Address_Model_Validation_Request
	extends Mage_Core_Model_Abstract
{

	/**
	 * Service URI has the following format:
	 * https://{env}-{rr}.gsipartners.com/v{M}.{m}/stores/{storeid}/{service}/{operation}{/parameters}.{format}
	 * - env - GSI Environment to access
	 * - rr - Geographic region - na, eu, ap
	 * - M - major version of the API
	 * - m - minor version of the API
	 * - storeid - GSI assigned store identifier
	 * - service - API call service/subject area
	 * - operation - specific API call of the specified service
	 * - parameters - optionally any parameters needed by the call
	 * - format - extension of the requested response format. Currently only xml is supported
	 */
	const URI_FORMAT = 'https://%s-%s.gsipartners.com/v%s.%s/stores/%s/%s/%s.%s';
	const API_ENV = 'developer';
	const API_REGION = 'na';
	const API_MAJOR_VERSION = '1';
	const API_MINOR_VERSION = '10';
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
	 * Get the GSI Store Id from core EB2C configuration
	 * @return string
	 */
	protected function _configStoreId()
	{
		return Mage::helper('eb2ccore/config')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'))
			->storeId;
	}

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
	 * Get the API URI for this message.
	 * @return string
	 * @todo simply pulling most all of this from class consts seems limiting, suspect this will need to change.
	 */
	public function getApiUri()
	{
		return sprintf(self::URI_FORMAT,
			self::API_ENV,
			self::API_REGION,
			self::API_MAJOR_VERSION,
			self::API_MINOR_VERSION,
			$this->_configStoreId(),
			self::API_SERVICE,
			self::API_OPERATION,
			self::API_FORMAT);
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
