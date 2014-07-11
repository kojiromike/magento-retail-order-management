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

class EbayEnterprise_Eb2cCore_Model_Xml_Mapper extends Varien_Object
{
	/**
	 * load all mapped data
	 *
	 * @param DOMNode $contextNode a valid DOMNode that is attached to a document
	 * @param array $mapping
	 * @param DOMXPath $xpath a preconfigured xpath object
	 * @throws EbayEnterprise_Eb2cCore_Exception
	 * @return self
	 */
	public function loadXml(DOMNode $contextNode, array $mapping, DOMXPath $xpath)
	{
		if ($contextNode instanceof DOMDocument) {
			$doc = $contextNode;
		} else {
			if (!isset($contextNode->ownerDocument)) {
				throw new EbayEnterprise_Eb2cCore_Exception('contextNode must be attached to an EbayEnterprise_Dom_Document');
			}
			$doc = $contextNode->ownerDocument;
		}
		foreach ($mapping as $key => $callback) {
			$callback = $mapping[$key];
			if ($callback['type'] !== 'disabled') {
				$result = $xpath->query($callback['xpath'], $contextNode);
				if ($result->length) {
					$callback['parameters'] = array($result);
					$coreHelper = Mage::helper('eb2ccore');
					$this->_data[$key] = $coreHelper->invokeCallback($callback);
				}
			}
		}
		return $this;
	}
}
