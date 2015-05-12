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

class EbayEnterprise_Eb2cOrder_Helper_Factory
{
	/**
	 * Get a singleton core/session object.
	 *
	 * @return Mage_Core_Model_Session
	 */
	public function getCoreSessionModel()
	{
		return Mage::getSingleton('core/session');
	}

	/**
	 * Get a new eb2corder/detail instance.
	 *
	 * @return EbayEnterprise_Eb2cOrder_Model_Detail
	 */
	public function getNewRomOrderDetailModel()
	{
		return Mage::getModel('eb2corder/detail');
	}
}
