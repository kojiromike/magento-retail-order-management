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

class EbayEnterprise_Eb2cOrder_Overrides_Block_Order_View extends Mage_Sales_Block_Order_View
{
	protected $_template = 'eb2corder/sales/order/ebayenterprise_history.phtml';
	/**
	 * Retrieve current rom_order model instance
	 *
	 * @return EbayEnterprise_Eb2cOrder_Model_Detail
	 */
	public function getOrder()
	{
		return Mage::registry('rom_order');
	}
}
