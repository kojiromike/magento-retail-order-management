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


class EbayEnterprise_Eb2cCustomerService_Model_Token_Response
	extends Varien_Object
{
	/**
	 * If given anything but an empty response message, assume it is true.
	 * @return boolean
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
