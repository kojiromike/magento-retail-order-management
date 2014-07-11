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

class EbayEnterprise_Eb2cOrder_Model_Detail_Order
	extends Mage_Sales_Model_Order
	implements EbayEnterprise_Eb2cOrder_Model_Detail_Order_Interface
{
	protected function _construct()
	{
		parent::_construct();
		// disabled data from saving
		$this->_dataSaveAllowed = false;
		// Initialize the various collections that need to overridden to use
		// plain Varien_Data_Collections instead of DB backed resource collections.
		// This eliminates the need for most method overrides as all of the methods
		// will simply return the existing collection when it is already set.
		$this->_addresses = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$this->_shipments = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$this->_items = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$this->_payments = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$this->_statusHistory = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$this->_invoices = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$this->_tracks = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$this->_creditmemos = Mage::helper('eb2ccore')->getNewVarienDataCollection();
	}
	/**
	 * adding shipment to shipment collection
	 * @param Mage_Sales_Model_Order_Shipment $shipment
	 * @return self
	 */
	public function addShipment(Mage_Sales_Model_Order_Shipment $shipment)
	{
		$this->_shipments->addItem($shipment);
		return $this;
	}
	/**
	 * @see parent::getStatusHistoryCollection()
	 * Override method to remove/ignore the $reload param and always return the
	 * collection it already has. When dealing with non-DB backed collection, the
	 * reload flag doesn't really mean anything as the collection can't really
	 * be reloaded.
	 * @return Varien_Data_Collection
	 */
	public function getStatusHistoryCollection()
	{
		return $this->_statusHistory;
	}
}
