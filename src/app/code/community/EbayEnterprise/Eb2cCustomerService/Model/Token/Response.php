<?php

class EbayEnterprise_Eb2cCustomerService_Model_Token_Response
	extends Varien_Object
{
	/**
	 * If given anything but an empty response message, assume it is true.
	 * @return bool
	 */
	public function isTokenValid()
	{
		return (bool) $this->getMessage();
	}
	/**
	 * Return an array of data extracted from the response message.
	 * @return array
	 */
	public function getCSRData()
	{
		if (!$this->getMessage()) {
			return array();
		}
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($this->getMessage());
		$dataNodes = $doc->getElementsByTagName('Field');
		$csrData = array();
		foreach ($dataNodes as $element) {
			if ($element->hasAttribute('key')) {
				$csrData[$element->getAttribute('key')] = $element->nodeValue;
			}
		}
		return $csrData;
	}
}
