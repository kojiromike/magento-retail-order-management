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

class EbayEnterprise_Multishipping_Test_Model_Override_Sales_OrderTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Create a mock order address of the provided type.
	 *
	 * @param string
	 * @return Mage_Sales_Model_Order_Address
	 */
	protected function _buildAddress($type)
	{
		$address = Mage::getModel('sales/order_address', ['address_type' => $type]);
		return $address;
	}

	/**
	 * When getting all shipping addresses, all addresses in the order's
	 * address collection should be filtered down to only those that are
	 * shipping addresses.
	 */
	public function testGetAllShippingAddresses()
	{
		$order = Mage::getModel('sales/order');
		$order->addAddress($this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_SHIPPING))
			->addAddress($this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_BILLING))
			->addAddress($this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_SHIPPING));

		$orderShippingAddresses = $order->getAllShippingAddresses();
		$this->assertCount(2, $orderShippingAddresses);
		foreach ($orderShippingAddresses as $address) {
			$this->assertSame(
				Mage_Sales_Model_Order_Address::TYPE_SHIPPING,
				$address->getAddressType()
			);
		}
	}

	/**
	 * When order shipment amounts are collected, any address level totals
	 * for addresses in the order should be summed and the total of each total
	 * for all addresses set on the order.
	 */
	public function testCollectShipmentAmounts()
	{
		$multishippingConfig = $this->buildCoreConfigRegistry([
			'orderShipmentAmounts' => ['shipping_amount' => 'shipping_amount']
		]);
		$order = Mage::getModel('sales/order', ['multishipping_config' => $multishippingConfig]);
		// Add some addresses to the order. Give each a shipping amount which
		// will be used as the target total - if shipping amount on the order
		// ends up being right, the others are likely right as well.
		$order
			->addAddress($this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_SHIPPING)->setShippingAmount(1.00))
			->addAddress($this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_SHIPPING)->setShippingAmount(5.00))
			->addAddress($this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_BILLING)->setShippingAmount(0.00));
		$order->collectShipmentAmounts();
		// Shipping amount on the order should match the sum of the shipping
		// amounts of all addresses. More totals could be added but all totals
		// are handled the same way so not sure how beneficial that would really
		// be. Just testing more of the same.
		$this->assertSame(6.00, $order->getShippingAmount());
	}

	/**
	 * When adding a new address to an existing order that has already had
	 * shipment totals collected, adding a new address to the order should
	 * allow collectShipmentAmounts to recollect amounts to ensure totals on the
	 * order are updated to reflect the newly added address.
	 */
	public function testAddingNewAddressFlagsShipmentRecollection()
	{
		$order = Mage::getModel('sales/order');
		$originalAddress = $this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_SHIPPING)->setShippingAmount(5.00);
		$order->addAddress($originalAddress);
		// Run an initial collection of shipment amounts - should gather all the
		// address totals and set them on the order, flagging that they do not
		// need to be collected again.
		$order->collectShipmentAmounts();
		// Pre-check for correct total with just the original address.
		$this->assertSame(5.00, $order->getShippingAmount());
		$newAddress = $this->_buildAddress(Mage_Sales_Model_Order_Address::TYPE_SHIPPING)->setShippingAmount(5.00);
		// Adding the new address should re-flag shipment totals for collection.
		$order->addAddress($newAddress);
		// This should now recollect amounts even through they have already been
		// collected for the order - a new address was added so expect them to
		// have changed.
		$order->collectShipmentAmounts();
		// Finally verify that the shipping amount was collected again and now
		// reflects the sum of both address' shipping amounts.
		$this->assertSame(10.00, $order->getShippingAmount());
	}
}
