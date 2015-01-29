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
}
