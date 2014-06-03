<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Generate the xml request to the AddressValidation service
 * @method Mage_Customer_Model_Address_Abstract getQuote
 * @method EbayEnterprise_Eb2cAddress_Model_Validation_Request setADdress(Mage_Customer_Model_Address_Abstract $address)
 */
class EbayEnterprise_Eb2cAddress_Model_Validation_Request extends Varien_Object
{
	const API_SERVICE        = 'address';
	const API_OPERATION      = 'validate';
	const API_FORMAT         = 'xml';
	const DOM_ROOT_NODE_NAME = 'AddressValidationRequest';

	/**
	 * DOMDocument used to build the request message
	 * @var EbayEnterprise_Dom_Document
	 */
	protected $_dom;

	/**
	 * Get the DOMDocument (EbayEnterprise_Dom_Document)
	 * to be sent with this message.
	 * @return EbayEnterprise_Dom_Document
	 */
	public function getMessage()
	{
		$cfg = Mage::helper('eb2ccore')->getConfigModel();
		$this->_dom = Mage::helper('eb2ccore')->getNewDomDocument();
		$this->_dom->addElement(self::DOM_ROOT_NODE_NAME, null, $cfg->apiNamespace);
		$this->_dom->documentElement->appendChild($this->_createMessageHeader());
		$this->_dom->documentElement->appendChild($this->_createMessageAddress());
		return $this->_dom;
	}

	/**
	 * Create a document fragment for the <Header>...</Header> portion of the message.
	 * @return DOMDocumentFragment
	 */
	protected function _createMessageHeader()
	{
		$cfg = Mage::helper('eb2caddress')->getConfigModel();
		$fragment = $this->_dom->createDocumentFragment();

		$fragment->appendChild(
			$this->_dom->createElement('Header',
				$this->_dom->createElement('MaxAddressSuggestions',
					$cfg->maxAddressSuggestions,
					$this->_dom->documentElement->namespaceURI
				),
				$this->_dom->documentElement->namespaceURI
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
		$fragment = $this->_dom->createDocumentFragment();
		$nsUri = $this->_dom->documentElement->namespaceURI;

		$fragment->appendChild(
			$this->_dom->createElement(
				'Address',
				Mage::helper('eb2caddress')->addressToPhysicalAddressXml($this->getAddress(), $this->_dom, $nsUri),
				$nsUri
			)
		);
		return $fragment;
	}
}
