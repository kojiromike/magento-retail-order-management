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

class EbayEnterprise_Order_Helper_Data extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	/**
	 * Gets a combined configuration model from core and order
	 * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
	 * @param mixed $store
	 * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('ebayenterprise_order/config'));
	}

	/**
	 * Given an order, returns the public order history URL.
	 *
	 * @param Mage_Sales_Model_Order
	 * @return string
	 */
	public function getOrderHistoryUrl(Mage_Sales_Model_Order $order)
	{
		$params = [
			'oar_billing_lastname' => $order->getCustomerLastname(),
			'oar_email' => $order->getCustomerEmail(),
			'oar_order_id' => $order->getIncrementId(),
			'oar_type' => 'email',
		];
		$mageUrlParams = [
			'_nosid' => true,
			'_query' => $params,
		];
		return Mage::getUrl('sales/guest/view', $mageUrlParams);
	}

	/**
	 * Get the configurable order cancel reasons. Return an array
	 * if the order cancel reasons are configured with data otherwise return null.
	 *
	 * @return array | null
	 */
	protected function _getOrderCancelReason()
	{
		$map = $this->getConfigModel()->cancelReasonMap;
		return is_array($map) ? $map : null;
	}

	/**
	 * Determine if order cancel reasons are configured.
	 *
	 * @return array | null
	 */
	public function hasOrderCancelReason()
	{
		$reasons = $this->_getOrderCancelReason();
		return !empty($reasons);
	}

	/**
	 * Build an order cancel reasons array with key 'value' and `label'.
	 *
	 * @return array
	 */
	public function getCancelReasonOptionArray()
	{
		$reasons = [];
		$map = $this->_getOrderCancelReason() ?: [];
		$reasons[] = ['value' => '', 'label' => ''];
		foreach ($map as $value => $label) {
			$reasons[] = ['value' => $value, 'label' => $label];
		}
		return $reasons;
	}

	/**
	 * Get the order can reason description base on the passed in reason code.
	 * If not found return a null value.
	 *
	 * @param  string
	 * @return string | null
	 */
	public function getCancelReasonDescription($reasonCode)
	{
		$map = (array) $this->_getOrderCancelReason();
		return isset($map[$reasonCode]) ? $map[$reasonCode] : null;
	}
}
