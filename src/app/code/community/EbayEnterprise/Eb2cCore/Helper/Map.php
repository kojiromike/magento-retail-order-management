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
 * Functions to help extracting data from xml.
 *
 * Each function is passed a DOMNodeList of nodes matching the configured xpath expression and the product object currently being processed.
 *
 * @example: public function prototypicalMapFunction(DOMNodeList $nodes, Mage_Catalog_Model_Product $product);
 *
 * <code>
 * // Return the mapped type_id if the product doesn't already have one.
 * // Otherwise return the product's existing value.
 * public function getTypeIdIfNew(DOMNodeList $nodes, Mage_Catalog_Model_Product $product) {
 *   return $product->getTypeId() ?: $nodes->item(0)->nodeValue;
 * }
 * </code>
 */
class EbayEnterprise_Eb2cCore_Helper_Map
{
	/**
	 * extract the first element of a dom node list and return a string value
	 * @param DOMNodeList $nodes
	 * @return string
	 */
	public function extractStringValue(DOMNodeList $nodes)
	{
		return ($nodes->length)? $nodes->item(0)->nodeValue : null;
	}
	/**
	 * extract the first element of a dom node list and return a boolean
	 * value of the extract string
	 * @param DOMNodeList $nodes
	 * @return bool
	 */
	public function extractBoolValue(DOMNodeList $nodes)
	{
		return Mage::helper('eb2ccore')->parseBool(($nodes->length)? $nodes->item(0)->nodeValue : null);
	}
	/**
	 * extract the first element of a dom node list and return the string value cast as integer value
	 * @param DOMNodeList $nodes
	 * @return int
	 */
	public function extractIntValue(DOMNodeList $nodes)
	{
		return ($nodes->length)? (int) $nodes->item(0)->nodeValue : 0;
	}
	/**
	 * extract the first element of a dom node list and return the string value cast as float value
	 * @param DOMNodeList $nodes
	 * @return int
	 */
	public function extractFloatValue(DOMNodeList $nodes)
	{
		return ($nodes->length)? (float) $nodes->item(0)->nodeValue : 0;
	}
	/**
	 * it return the pass in value parameter
	 * it's a callback to return static value set in the config
	 * @param mixed $value
	 * @return mixed
	 */
	public function passThrough($value)
	{
		return $value;
	}
	/**
	 * Always return false.
	 * This is useful for clearing a value to have it fallback to a higher scope.
	 */
	public function extractFalse()
	{
		return false;
	}
	/**
	 * return a sum of the data for all elements retrieved by the xpath.
	 * @param mixed $value
	 * @return float
	 */
	public function extractFloatSum(DOMNodeList $nodes)
	{
		$sum = 0.0;
		foreach ($nodes as $node) {
			$sum += (float) $node->nodeValue;
		}
		return $sum;
	}
}
