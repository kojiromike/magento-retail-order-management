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
	 * Request body DOM Document
	 * @var TrueAction_Dom_Document
	 */
	public $requestDocument;

	protected function _construct()
	{
		$this->_requestDocument = new TrueAction_Dom_Document();
		$this->_root = $this->_requestDocument
			->createElement('AddressValidationRequest', '');
	}

	public function getRequestBody()
	{
		return $this->_requestDocument;
	}

	public function createRequestMessage()
	{
		$this->_root->addChild('Header', $this->_createRequestHeader())
			->addChild('Address', $this->_createRequestBody());
	}

	protected function _createRequestHeader()
	{
		$maxSuggestions = Mage::helper('eb2ccore/config')
			->addConfigModel(Mage::getModel('eb2caddress/config'))
			->maxAddressSuggestions;
		$ele = new TrueAction_Dom_Element('MaxAddressSuggestions', $maxSuggestions);
		return $ele;
	}

	protected function _createRequestBody()
	{
		$body = null;
		if ($this->getQuote()) {
			$body = Mage::helper('eb2caddress')
				->addressToPhysicalAddressXml($this->getQuote()->getShippingAddress());
		}
		return $body;
	}

}
