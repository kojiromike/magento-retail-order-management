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
 * Responsible for handling the AddressValidationResponse message.
 */
class EbayEnterprise_Address_Model_Validation_Response extends Varien_Object
{
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/**
	 * Mapping of XPath expressions used to fetch various parts of the message
	 * @var array
	 */
	protected static $_paths = array(
		'provider_error'   => 'a:AddressValidationResponse/a:Result/a:ProviderErrorText',
		'request_address'  => 'a:AddressValidationResponse/a:RequestAddress',
		'result_code'      => 'a:AddressValidationResponse/a:Result/a:ResultCode',
		'result_errors'    => 'a:AddressValidationResponse/a:Result/a:ErrorLocations/a:ErrorLocation',
		'suggestion_count' => 'a:AddressValidationResponse/a:Result/a:ResultSuggestionCount',
		'suggestions'      => 'a:AddressValidationResponse/a:Result/a:SuggestedAddresses/a:SuggestedAddress',
	);
	/**
	 * @var EbayEnterprise_Dom_Document
	 */
	protected $_doc;
	protected function _construct()
	{
		$this->_logger = Mage::helper('ebayenterprise_magelog');
		$this->_doc = Mage::helper('eb2ccore')->getNewDomDocument();
		// Apply the side-effects of setMessage
		if ($this->hasMessage()) {
			$this->setMessage($this->getMessage());
		}
	}

	/**
	 * Load the response message into the dom document.
	 * @param string $message - the XML response message
	 * @return EbayEnterprise_Address_Model_Validation_Response $this
	 */
	public function setMessage($message)
	{
		// new message means any stored data on this instance is probably invalid, so nuke it
		$this->unsetData();
		$this->_doc->loadXML($message);
		return $this;
	}

	/**
	 * Pass through to the EbayEnterprise_Address_Helper_Data::getTextValueByXPath method.
	 *
	 * @param string $pathKey
	 * @param DOMNode $context - when unspecified, will use the stored DOMDocument for the message - $this->_doc
	 * @return string|array
	 */
	protected function _lookupPath($pathKey, DOMNode $context=null)
	{
		return Mage::helper('ebayenterprise_address')
			->getTextValueByXPath(self::$_paths[$pathKey], $context ?: $this->_doc);
	}

	/**
	 * When there is a valid address in the response, return it. Otherwise
	 * this should return null, as a "valid address" does not exist in the response.
	 * @return Mage_Customer_Model_Address
	 */
	public function getValidAddress()
	{
		if (!$this->hasData('valid_address')) {
			$validAddress = null;
			if ($this->isAddressValid()) {
				if ((int) $this->_lookupPath('suggestion_count') === 1) {
					$suggestions = $this->getAddressSuggestions();
					$validAddress = $suggestions[0];
				} else {
					$validAddress = $this->getOriginalAddress();
				}
			}
			$this->setData('valid_address', $validAddress);
		}
		return $this->getData('valid_address');
	}

	/**
	 * Gets the original address submitted to the service.
	 * @return Mage_Customer_Model_Address
	 */
	public function getOriginalAddress()
	{
		if (!$this->hasData('original_address')) {
			$xpath = new DOMXPath($this->_doc);
			$xpath->registerNamespace(
				'a',
				$this->_doc->lookupNamespaceUri($this->_doc->namespaceURI)
			);

			$physicalAddressElement = $xpath->query(
				self::$_paths['request_address'],
				$this->_doc
			)->item(0);
			$this->setData(
				'original_address',
				Mage::helper('ebayenterprise_address')->physicalAddressXmlToAddress($physicalAddressElement)->setHasBeenValidated(true)
			);
		}
		return $this->getData('original_address');
	}

	/**
	 * Does the response message include suggestions?
	 * @return bool
	 */
	public function hasAddressSuggestions()
	{
		if (!$this->hasData('has_address_suggestions')) {
			$suggestionCount = (int) $this->_lookupPath('suggestion_count');
			$this->setData('has_address_suggestions', ($suggestionCount > 0));
		}
		return $this->getData('has_address_suggestions');
	}

	/**
	 * Get the list of suggested addresses returned by the service.
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getAddressSuggestions()
	{
		if (!$this->hasData('address_suggestions')) {
			$xpath = new DOMXPath($this->_doc);
			$xpath->registerNamespace(
				'a',
				$this->_doc->lookupNamespaceUri($this->_doc->namespaceURI)
			);

			$physicalAddressElements = $xpath->query(self::$_paths['suggestions'], $this->_doc);
			$suggestionAddresses = array();
			foreach ($physicalAddressElements as $physicalAddress) {
				$suggestionAddresses[] = Mage::helper('ebayenterprise_address')
					->physicalAddressXmlToAddress($physicalAddress)
					->setHasBeenValidated(true);
			}
			$this->setData('address_suggestions', $suggestionAddresses);
		}
		return $this->getData('address_suggestions');
	}

	/**
	 * Indicates if the address should be considered valid.
	 * @return bool
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function isAddressValid()
	{
		if (!$this->hasData('is_valid')) {
			$resultCode = $this->_lookupPath('result_code');
			switch ($resultCode) {
				case 'V':
				case 'N':
					$validity = true;
					break;
				case 'C':
					$validity = !$this->hasAddressSuggestions();
					break;
				case 'K':
					$validity = false;
					break;
				case 'U':
					$this->_logger->logWarn('[%s] Unable to contact provider', array(__CLASS__));
					$validity = true;
					break;
				case 'T':
					$this->_logger->logWarn('[%s] Provider timed out', array(__CLASS__));
					$validity = true;
					break;
				case 'P':
					$this->_logger->logWarn('[%s] Provider returned a system error: %s', array(__CLASS__, $this->_lookupPath('provider_error')));
					$validity = true;
					break;
				case 'M':
					$this->_logger->logWarn('[%s] The request message was malformed or contained invalid data', array(__CLASS__));
					$validity = true;
					break;
				default:
					$this->_logger->logWarn('[%s] Response message did not contain a known result code. Result Code: %s', array(__CLASS__, $resultCode));
					$validity = true;
					break;
			}
			$this->setData('is_valid', $validity);
			$this->_logger->logDebug('[%s] Response with status code "%s" is %s.', array(__CLASS__, $resultCode, ($validity ? 'valid' : 'invalid')));
		}
		return $this->getData('is_valid');
	}
}
