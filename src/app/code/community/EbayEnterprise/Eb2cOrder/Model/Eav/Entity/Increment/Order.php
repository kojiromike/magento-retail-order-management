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

class EbayEnterprise_Eb2cOrder_Model_Eav_Entity_Increment_Order
	extends Mage_Eav_Model_Entity_Increment_Abstract
{
	/**
	 * Get the store id for the order. In non-admin stores, can use the current
	 * store. In admin stores, must get the order the quote is actually
	 * being created in.
	 * @return int
	 */
	protected function _getStoreId()
	{
		$storeEnv = Mage::app()->getStore();
		if ($storeEnv->isAdmin()) {
			// when in the admin, the store id the order is actually being created
			// for should be used instead of the admin store id - should be
			// available in the session
			$storeEnv = Mage::getSingleton('adminhtml/session_quote')->getStore();
		}
		return $storeEnv->getId();
	}
	/**
	 * Get the next increment id by incrementing the last id
	 * @return string
	 */
	public function getNextId()
	{
		// remove any order prefixes from the last increment id
		$last = (int) Mage::helper('eb2corder')->removeOrderIncrementPrefix($this->getLastId());
		$next = $last + 1;
		return $this->format($next);
	}
	/**
	 * Prefix the order with the Client Order Id Prefix configured for the
	 * current scope.
	 * @return string
	 */
	public function getPrefix()
	{
		return Mage::helper('eb2corder')->getConfig($this->_getStoreId())->clientOrderIdPrefix;
	}
}
