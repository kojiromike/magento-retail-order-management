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

class EbayEnterprise_Eb2cOrder_Model_Detail_Payment
	extends Mage_Sales_Model_Order_Payment
{
	/**
	 * @see parent::_construct()
	 * overriding this method to update ROM payment type
	 * with Magento payment type.
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		// @see parent::_order property.
		$this->setOrder($this->getData('order'));
		// remove the order key, we no long need it.
		$this->unsetData('order');
		if ($this->getPaymentTypeName()) {
			$this->_updatePayments();
		}
	}
	/**
	 * get the payment method name for the payment type
	 * @param  string $paymentTypeName
	 * @return string
	 * @throws EbayEnterprise_Eb2cCore_Exception_Critical If payment extraction mappings are not configured
	 */
	protected function _getPaymentMethod($paymentTypeName)
	{
		$mappings = Mage::helper('eb2corder')->getConfigModel()->detailPaymentMethodMapping;
		if (!isset($mappings[$paymentTypeName])) {
			throw new EbayEnterprise_Eb2cCore_Exception_Critical(
				"$paymentTypeName elements are not mapped to a valid Magento payment method."
			);
		}
		return $mappings[$paymentTypeName];
	}
	/**
	 * get update payment information
	 * @return self
	 */
	protected function _updatePayments()
	{
		$cards = array();
		$this->setMethod($this->_getPaymentMethod($this->getPaymentTypeName()));
		switch ($this->getPaymentTypeName()) {
			case 'CreditCard':
				$this->addData(array(
					'cc_last4' => substr($this->getAccountUniqueId(), -4),
					'cc_type' => $this->getTenderType(),
				));
				break;
			case 'StoredValueCard':
				$cards[] = array(
					'ba' => $this->getAmount(),
					'a' => $this->getAmount(),
					'c' => $this->getAccountUniqueId(),
				);
				break;
		}
		Mage::helper('enterprise_giftcardaccount')->setCards($this->getOrder(), $cards);
		return $this;
	}
}
