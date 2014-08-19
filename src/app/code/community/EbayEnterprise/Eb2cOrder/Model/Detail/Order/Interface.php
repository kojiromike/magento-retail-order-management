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

interface EbayEnterprise_Eb2cOrder_Model_Detail_Order_Interface {

	/**
	 * Prevent the sales/order object from loading
	 * sales address data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getAddressesCollection();

	/**
	 * Prevent the sales/order object from loading
	 * sales items data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getItemsCollection();
	/**
	 * Prevent the sales/order object from loading
	 * sales payment data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getPaymentsCollection();

	/**
	 * Prevent the sales/order object from loading
	 * sales status history data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getStatusHistoryCollection();
	/**
	 * Prevent the sales/order object from loading
	 * sales invoice data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getInvoiceCollection();
	/**
	 * Prevent the sales/order object from loading
	 * sales shipment tracking data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getTracksCollection();
	/**
	 * Prevent the sales/order object from loading
	 * sales shipment data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getShipmentsCollection();
	/**
	 * Prevent the sales/order object from loading
	 * sales credit memo data from the database.
	 * @return Varien_Data_Collection
	 */
	public function getCreditmemosCollection();
}
