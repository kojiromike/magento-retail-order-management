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

/**
 * PayPal Standard payment "form"
 */
class EbayEnterprise_PayPal_Block_Express_Form extends Mage_Payment_Block_Form
{
	const WILL_REDIRECT_MESSAGE = 'EBAYENTERPRISE_PAYPAL_WILL_REDIRECT_MESSAGE';

	/**
	 * Set template and redirect message
	 */
	protected function _construct()
	{
		$mark = Mage::getConfig()->getBlockClassName(
			'ebayenterprise_paypal/payment_mark'
		);
		$mark = new $mark;
		$this->setTemplate('ebayenterprise_paypal/payment/redirect.phtml')
			->setMethodTitle('')// Output PayPal mark, omit title
			->setMethodLabelAfterHtml($mark->toHtml())
			->setRedirectMessage(
				Mage::helper('paypal')->__(static::WILL_REDIRECT_MESSAGE)
			);
		return parent::_construct();
	}
}

