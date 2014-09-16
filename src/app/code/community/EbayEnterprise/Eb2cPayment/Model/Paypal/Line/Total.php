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

class EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total
{
	/** @var string $_name */
	protected $_name;
	/** @var float $_price */
	protected $_price;
	/** @var int $_int */
	protected $_qty;
	/**
	 * @param mixed[] $params Required to have following key/value pairs
	 *                        'name' string Name for the line total
	 *                        'price' float Unit price of the line
	 *                        'qty' int Quantity of the item
	 */
	public function __construct($params=array())
	{
		$this->_name = $params['name'];
		$this->_price = (float) $params['price'];
		$this->_qty = (int) $params['qty'];
	}
	/**
	 * Get the name of the line item
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}
	/**
	 * Get the price of the line item
	 * @return float
	 */
	public function getPrice()
	{
		return $this->_price;
	}
	/**
	 * Get the price in the given format, default to float to a precision of 2
	 * @param string $format format string to use
	 * @return string
	 */
	public function getFormattedPrice($format)
	{
		return sprintf($format, $this->_price);
	}
	/**
	 * Qty of the line
	 * @return int
	 */
	public function getQty()
	{
		return $this->_qty;
	}
}
