<?php

/**
 * Methods for converting addresses represented in XML
 * to Magento address model objects.
 */
class TrueAction_Eb2c_Address_Helper_Data extends Mage_Core_Helper_Abstract
{

	const LINES_PATH     = 'Line1|Line2|Line3|Line4';
	const CITY_PATH      = 'City';
	const REGION_PATH    = 'MainDivision';
	const COUNTRY_PATH   = 'CountryCode';
	const POSTCODE_PATH  = 'PostalCode';

	/**
	 * Generate the xml to represent the Eb2c PhysicalAddressType from an address
	 * @param Mage_Customer_Model_Address_Abstract
	 * @return DOMDocumentFragment
	 */
	public function addressToPhysicalAddressXml(Mage_Customer_Model_Address_Abstract $address, TrueAction_Dom_Document $doc)
	{
		$frag = $doc->createDocumentFragment();
		$addressLines = $address->getStreet();
		foreach ($addressLines as $idx => $line) {
			$frag->appendChild($doc->createElement('Line' . ($idx + 1), $line));
		}
		$frag->appendChild($doc->createElement('City', $address->getCity()));
		$frag->appendChild($doc->createElement('MainDivision', $address->getRegionCode()));
		$frag->appendChild($doc->createElement('CountryCode', $address->getCountry()));
		$frag->appendChild($doc->createElement('PostalCode', $address->getPostcode()));
		return $frag;
	}

	/**
	 * Evaluate the given XPath get get the text content of the returned nodes
	 * @param DOMNode $context
	 * @param string $path
	 * @return array|string
	 */
	public function getTextValueByXPath(DOMNode $context, $path)
	{
		$xpath = new DOMXPath($context->ownerDocument ?: $context);
		$nodes = $xpath->evaluate($path, $context);
		if ($nodes->length === 1) {
			return $nodes->item(0)->textContent;
		} else if ($nodes->length > 1) {
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
	 * @param DOMElement
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
	 * @return array
	 */
	public function physicalAddressStreet(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath($physicalAddressXml, self::LINES_PATH);
	}

	/**
	 * Get the city from a physical address xml element
	 * @return string
	 */
	public function physicalAddressCity(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath($physicalAddressXml, self::CITY_PATH);
	}

	/**
	 * Get the region id from a physical address xml element
	 * @return string
	 */
	public function physicalAddressRegionId(DOMElement $physicalAddressXml)
	{
		return Mage::getModel('directory/region')
			->loadByCode(
				$this->getTextValueByXPath($physicalAddressXml, self::REGION_PATH),
				$this->physicalAddressCountryId($physicalAddressXml))
			->getId();
	}

	/**
	 * Get the country id from a physical address xml element
	 * @return string
	 */
	public function physicalAddressCountryId(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath($physicalAddressXml, self::COUNTRY_PATH);
	}

	/**
	 * Get the postcode from a physical address xml element
	 * @return string
	 */
	public function physicalAddressPostcode(DOMElement $physicalAddressXml)
	{
		return $this->getTextValueByXPath($physicalAddressXml, self::POSTCODE_PATH);
	}
}