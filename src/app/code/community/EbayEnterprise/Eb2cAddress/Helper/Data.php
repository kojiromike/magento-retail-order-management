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
 * Class EbayEnterprise_Eb2cAddress_Helper_Data
 *
 * Methods for converting addresses represented in XML to Magento address model objects.
 */
class EbayEnterprise_Eb2cAddress_Helper_Data extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	// xpath expressions for extracting data from address xml
	const LINES_PATH    = 'eb2c:Line1|eb2c:Line2|eb2c:Line3|eb2c:Line4';
	const CITY_PATH     = 'eb2c:City';
	const REGION_PATH   = 'eb2c:MainDivision';
	const COUNTRY_PATH  = 'eb2c:CountryCode';
	const POSTCODE_PATH = 'eb2c:PostalCode';

	/**
	 * Make sure the given string does not exceed the max length specified.
	 *
	 * @param string $str    String to limit to given length
	 * @param int    $maxLen Max length of the string
	 * @return string         String which will be at most $maxLen characters
	 */
	protected function _limit($str, $maxLen)
	{
		return substr($str, 0, $maxLen);
	}

	/**
	 * Get the address validation config model
	 *
	 * @see EbayEnterprise_Eb2cCore_Model_Config_Registry::addConfigModel
	 * @param bool|int|Mage_Core_Model_Store|null|string $store Set the config model to use this store
	 * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('eb2caddress/config'));
	}

	/**
	 * Generate the xml to represent the Eb2c PhysicalAddressType from an address
	 *
	 * @param  Mage_Customer_Model_Address_Abstract $address The address to serialize
	 * @param  EbayEnterprise_Dom_Document $doc The DOMDocument to use for marshalling
	 * @param  string $nsUri The namespace uri for the xml
	 * @return DOMDocumentFragment
	 */
	public function addressToPhysicalAddressXml(
		Mage_Customer_Model_Address_Abstract $address,
		EbayEnterprise_Dom_Document $doc,
		$nsUri
	)
	{
		$frag = $doc->createDocumentFragment();
		/** @var array $addressLines (upstream docblock lies) */
		$addressLines = $address->getStreet();
		foreach ($addressLines as $idx => $line) {
			$frag->appendChild($doc->createElement('Line' . ($idx + 1), $this->_limit($line, 70), $nsUri));
		}
		$frag->appendChild($doc->createElement('City', $this->_limit($address->getCity(), 35), $nsUri));
		$regionCode = $address->getRegionCode();
		// Excluding the 'MainDivision' node when there's no region code.
		if (trim($regionCode) !== '') {
			$frag->appendChild($doc->createElement(
				'MainDivision', $this->_limit($regionCode, 35), $nsUri
			));
		}
		$frag->appendChild($doc->createElement('CountryCode', $this->_limit($address->getCountry(), 40), $nsUri));
		$frag->appendChild($doc->createElement('PostalCode', $this->_limit($address->getPostcode(), 15), $nsUri));
		return $frag;
	}

	/**
	 * Evaluate the given XPath to get the text content of the returned nodes
	 *
	 * @param  DOMNode $context the xpath is relative to this context node
	 * @param  string $path the xpath
	 * @return array|null|string
	 */
	public function getTextValueByXPath($path, DOMNode $context)
	{
		/** @var DomDocument $doc */
		$doc = $context->ownerDocument ?: $context;
		$xpath = new DOMXPath($doc);
		$ns = $doc->lookupNamespaceUri($doc->namespaceURI);
		$xpath->registerNamespace('eb2c', $ns);
		$nodes = $xpath->query($path, $context);
		if ($nodes->length === 1) {
			return $nodes->item(0)->textContent;
		} elseif ($nodes->length > 1) {
			$values = array();
			foreach ($nodes as $node) {
				$values[] = $node->textContent;
			}
			return $values;
		}
		return null;
	}

	/**
	 * Create a valid address object from Eb2c PhysicalAddressType xml nodes
	 *
	 * @param DOMElement $physicalAddressXml from which to extract the address
	 * @return Mage_Customer_Model_Address
	 */
	public function physicalAddressXmlToAddress(DOMElement $physicalAddressXml)
	{
		$address = Mage::getModel('customer/address');
		if ($physicalAddressXml->hasChildNodes()) {
			$address->setStreetFull($this->physicalAddressStreet($physicalAddressXml));
			$address->setCity($this->physicalAddressCity($physicalAddressXml));
			$address->setRegionId($this->physicalAddressRegionId($physicalAddressXml));
			$address->setCountryId($this->physicalAddressCountryId($physicalAddressXml));
			$address->setPostcode($this->physicalAddressPostcode($physicalAddressXml));
		}
		return $address;
	}

	/**
	 * Get the street lines from a physical address xml element
	 *
	 * @param DOMElement $physicalAddressXml from which to extract the street lines
	 * @return string[]
	 */
	public function physicalAddressStreet(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath(self::LINES_PATH, $physicalAddressXml);
	}

	/**
	 * Get the city from a physical address xml element
	 *
	 * @param DOMElement $physicalAddressXml from which to extract the city
	 * @return string
	 */
	public function physicalAddressCity(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath(self::CITY_PATH, $physicalAddressXml);
	}

	/**
	 * Get the region id from a physical address xml element
	 *
	 * @param DOMElement $physicalAddressXml from which to extract the region id
	 * @return string
	 */
	public function physicalAddressRegionId(DOMElement $physicalAddressXml)
	{
		return (int) Mage::getModel('directory/region')
			->loadByCode(
				$this->getTextValueByXPath(self::REGION_PATH, $physicalAddressXml),
				$this->physicalAddressCountryId($physicalAddressXml)
			)
			->getId();
	}

	/**
	 * Get the country id from a physical address xml element
	 *
	 * @param DOMElement $physicalAddressXml from which to extract the country id
	 * @return string
	 */
	public function physicalAddressCountryId(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath(self::COUNTRY_PATH, $physicalAddressXml);
	}

	/**
	 * Get the postcode from a physical address xml element
	 *
	 * @param DOMElement $physicalAddressXml from which to extract the postcode
	 * @return string
	 */
	public function physicalAddressPostcode(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath(self::POSTCODE_PATH, $physicalAddressXml);
	}
}
