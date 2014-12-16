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
 * @license	 http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_Info extends Mage_Sales_Block_Order_Info
{
	const OVERRIDDEN_TEMPLATE = 'eb2corder/sales/order/ebayenterprise_info.phtml';
	protected function _construct()
	{
		// We have to have a constructor to preserve our template, because the parent constructor sets it.
		parent::_construct();
		$this->setTemplate(self::OVERRIDDEN_TEMPLATE);
	}
	/**
	 * Retrieve current order model instance
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		return Mage::registry('rom_order');
	}
	/**
	 * Returns Helper
	 * @param helper type (default eb2corder)
	 * @return EbayEnterprise_Eb2cOrder_Helper_Data
	 */
	public function getHelper($type='eb2corder')
    {
		return Mage::helper($type);
	}
}

