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

class EbayEnterprise_Eb2cOrder_Model_Resource_Summary_Order_Collection
	extends Mage_Sales_Model_Resource_Order_Collection
{
	// @var string prefix for events dispatched by this collection
	protected $_eventPrefix = 'eb2corder_summary_collection';

	// @var string customer id orders are for
	protected $_customerId;
	/**
	 * Set the id of the customer the order summaries are for and add a fileter
	 * for the customer_id
	 * @param string $customerId
	 * @return self
	 */
	public function setCustomerId($customerId)
	{
		$this->_customerId = $customerId;
		$this->addFieldToFilter('customer_id', $this->_customerId);
		return $this;
	}
	/**
	 * Get the customer id order summaries are for. If no customer had been set,
	 * use the current customer.
	 * @return string
	 */
	public function getCustomerId()
	{
		return $this->_customerId ?: Mage::getSingleton('customer/session')->getCustomer()->getId();
	}
}
