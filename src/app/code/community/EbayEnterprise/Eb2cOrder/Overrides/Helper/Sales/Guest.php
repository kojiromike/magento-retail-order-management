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

class EbayEnterprise_Eb2cOrder_Overrides_Helper_Sales_Guest extends Mage_Sales_Helper_Guest
{
	/**
	 * Overriding this helper class in order to redirect guest order search
	 * to eb2c.
	 * Try to load valid order by $_POST or $_COOKIE
	 * @return bool|null
	 */
	public function loadValidOrder()
	{
		if ((bool) parent::loadValidOrder()) {
			$post = Mage::app()->getRequest()->getPost();
			$incrementId = $post['oar_order_id'];
			// Making eb2c call to make sure the order exists in the OMS
			$orderSearchObj = Mage::getModel('eb2corder/customer_order_search');

			$cfg = Mage::helper('eb2corder')->getConfigModel();

			// making eb2c customer order search request base on current order id pass in the post in the guest order search form
			// and then parse result in a collection of varien object
			$orderHistorySearchResults = $orderSearchObj->parseResponse($orderSearchObj->requestOrderSummary(
				sprintf('%s%s', $cfg->clientCustomerIdPrefix, 0), $incrementId
			));

			if (!isset($orderHistorySearchResults[$incrementId])) {
				// order was not found in the eb2c oms.
				Mage::getSingleton('core/session')->addError(
					$this->__('Entered data is incorrect. Please try again.')
				);
				Mage::app()->getResponse()->setRedirect(Mage::getUrl('sales/guest/form'));
				return false;
			}

			// let's set the result in the session so that the view block can use the result instead of making another eb2c order search
			Mage::getSingleton('core/session')->setEbcGuestCustomerOrderResults($orderHistorySearchResults);
			return true;
		}

		return false;
	}
}
