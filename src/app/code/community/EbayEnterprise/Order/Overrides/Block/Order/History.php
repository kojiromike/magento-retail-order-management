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

class EbayEnterprise_Order_Overrides_Block_Order_History extends EbayEnterprise_Order_Overrides_Block_Order_Recent
{
	/**
	 * Template for this block doesn't get set by Magento in layout XML. In
	 * the overridden block, the default template is set in the constructor.
	 * Setting this property does the same thing but doesn't require the call in
	 * the constructor.
	 * @var string template path
	 */
	protected $_template = 'ebayenterprise_order/order/history.phtml';
}
