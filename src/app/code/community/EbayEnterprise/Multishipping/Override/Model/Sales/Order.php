<?php
/**
 * Copyright (c) 2013-2015 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Multishipping_Override_Model_Sales_Order extends Mage_Sales_Model_Order
{
	/** @var bool */
	protected $_shipmentTotalsCollectedFlag = false;
	/** @var EbayEnterprise_Eb2cCore_Model_Confgi_Registry */
	protected $_multishippingConfig;

	protected function _construct()
	{
		parent::_construct();
		list(
			$this->_multishippingConfig
		) = $this->_checkTypes(
			$this->getData('multishipping_config') ?: Mage::helper('ebayenterprise_multishipping')->getConfigModel()
		);
	}

	/**
	 * Enforce type checks on construct args array.
	 *
	 * @param EbayEnterprise_Eb2cCore_Model_Config_Registry
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Eb2cCore_Model_Config_Registry $multishippingConfig
	) {
		return func_get_args();
	}

	/**
	 * Get an array of order shipping addresses.
	 *
	 * @return Mage_Sales_Model_Order_Address[]
	 */
	public function getAllShippingAddresses()
	{
		return $this->getAddressesCollection()->getItemsByColumnValue('address_type', Mage_Sales_Model_Order_Address::TYPE_SHIPPING);
	}

	/**
	 * Add an address to the order.
	 *
	 * Adding a new address to the order should require shipment amounts to
	 * be re-collected.
	 *
	 * @param Mage_Sales_Model_Order_Address
	 * @return self
	 */
	public function addAddress(Mage_Sales_Model_Order_Address $address)
	{
		parent::addAddress($address);
		$this->_shipmentTotalsCollectedFlag = false;
		return $this;
	}

	/**
	 * This method operates similar to the quote's collectTotals method, only
	 * much simpler. Some totals that were only at the order level may now also
	 * be at the address level to support multi-shipping address checkout. To
	 * maintain compatibility with expectations of these totals at the order level,
	 * this method will sum any such totals from each of the order addresses and
	 * set the sum totals on the order.
	 *
	 * @param bool $force Override the totals collected flag and force totals to be collected.
	 * @return self
	 */
	public function collectShipmentAmounts($force = false)
	{
		if ($force || !$this->_shipmentTotalsCollectedFlag) {
			foreach ($this->_multishippingConfig->orderShipmentAmounts as $amount) {
				$this->setDataUsingMethod($amount, $this->_sumAddressTotals($amount));
			}
			$this->_shipmentTotalsCollectedFlag = true;
		}
		return $this;
	}

	/**
	 * Sum totals field from each address for the order.
	 *
	 * @param string
	 * @return float
	 */
	protected function _sumAddressTotals($totalField)
	{
		$total = 0;
		foreach ($this->getAddressesCollection() as $address) {
			$total += $address->getDataUsingMethod($totalField);
		}
		return $total;
	}
}
