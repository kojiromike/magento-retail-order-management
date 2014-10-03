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

class EbayEnterprise_Catalog_Model_Price_Event
{
	// XPath expression used to extract data from the Event node.
	const NODE_NAME_PRICE = 'Price';
	const NODE_NAME_ALTERNATE_PRICE = 'AlternatePrice1';
	const NODE_NAME_START_DATE = 'StartDate';
	const NODE_NAME_END_DATE = 'EndDate';

	// Default DateTime format
	const DEFAULT_DATE_FORMAT = 'Y-m-d';

	/**
	 * The price Event DOMNode this price event represents.
	 * @var DOMNode|null
	 */
	protected $_sourceEventNode;
	/**
	 * The value to use as the product "price" attribute
	 * @var float
	 */
	protected $_price;
	/**
	 * The value to use as the product "special_price" attribute
	 * @var float
	 */
	protected $_specialPrice;
	/**
	 * The start date of the special price.
	 * @var DateTime
	 */
	protected $_specialFromDate;
	/**
	 * the end date of the special price.
	 * @var DateTime
	 */
	protected $_specialToDate;

	/**
	 * Store the source event node provided, if there is one, and then import
	 * data from the source.
	 * The `$args` array should include the source event node via an `event_node`
	 * key/value pair.
	 * @param array $args Array of args to work with Magento factory methods
	 */
	public function __construct($args=array())
	{
		$this->_sourceEventNode = (isset($args['event_node']) && $args['event_node'] instanceof DOMNode) ?
			$args['event_node'] : null;
		$this->_importSourceData();
	}

	/**
	 * Import data from the XML node for the pricing event.
	 * @return self
	 */
	protected function _importSourceData()
	{
		// can't extract data if there is no source
		if (is_null($this->_sourceEventNode)) {
			return $this;
		}
		$price = $this->_extractElementValue($this->_sourceEventNode, self::NODE_NAME_PRICE);
		$altPrice = $this->_extractElementValue($this->_sourceEventNode, self::NODE_NAME_ALTERNATE_PRICE);
		$start = $this->_extractElementValue($this->_sourceEventNode, self::NODE_NAME_START_DATE);
		$end = $this->_extractElementValue($this->_sourceEventNode, self::NODE_NAME_END_DATE);
		$coreHelper = Mage::helper('eb2ccore');

		$this->_price = (float) ($altPrice ?: $price);
		$this->_specialPrice = $altPrice ? (float) $price : null;
		$this->_specialFromDate = $start ? $coreHelper->getNewDateTime($start) : null;
		$this->_specialToDate = $end ? $coreHelper->getNewDateTime($end) : null;
		return $this;
	}

	/**
	 * Extract the value of the first element specified by `$nodeName` in the
	 * contained within the given `$node`
	 * @param  DOMNode $node     DOMNode to search in
	 * @param  string  $nodeName Name of the node to find
	 * @return string|null Trimmed value if the node exists, null if the node isn't found
	 */
	private function _extractElementValue(DOMNode $node, $nodeName)
	{
		$ele = $node->getElementsByTagName($nodeName)->item(0);
		return $ele ? trim($ele->nodeValue) : null;
	}

	/**
	 * Get the price.
	 * @return float
	 */
	public function getPrice()
	{
		return $this->_price;
	}

	/**
	 * Get the "special_price" if there is one.
	 * @return float|null
	 */
	public function getSpecialPrice()
	{
		return $this->_specialPrice;
	}

	/**
	 * Format the date using the provided format string.
	 * @param  DateTime|null $date
	 * @param  string|null $format
	 * @return string
	 */
	private function _formatDate(DateTime $date=null, $format=null)
	{
		return $date ? $date->format($format ?: self::DEFAULT_DATE_FORMAT) : null;
	}

	/**
	 * Get the "special_from_date", if there is one, formatted by the provided
	 * format string or via a default.
	 * @see self::_formatDate
	 * @param  string|null $format Date format string to use
	 * @return string|null Formatted datetime
	 */
	public function getSpecialFromDate($format=null)
	{
		return $this->_formatDate($this->_specialFromDate, $format);
	}

	/**
	 * Get the "special_to_date", if there is one, formatted by the provided
	 * format string or via a default.
	 * @see self::_formatDate
	 * @param  string|null $format Date format string to use
	 * @return string|null Formatted datetime
	 */
	public function getSpecialToDate($format=null)
	{
		return $this->_formatDate($this->_specialToDate, $format);
	}
}
