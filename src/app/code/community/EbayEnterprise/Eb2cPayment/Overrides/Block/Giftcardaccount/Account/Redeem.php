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

class EbayEnterprise_Eb2cPayment_Overrides_Block_Giftcardaccount_Account_Redeem
	extends Enterprise_GiftCardAccount_Block_Account_Redeem
{

	/**
	 * Disables redeeming gift cards in the My Account page for Gift Cards.
	 * For Magento Gift Card Accounts, this means redeeming the gift card and
	 * adding the value to the customer's account balance. This functionality
	 * is not supported with ROM. Some form of this functionality may be added
	 * in the future for ROM in which case this override should be replaced with
	 * a more meaningful check to determine if SVC can be redeemed from the
	 * customer's My Account pages.
	 * @return bool
	 */
	public function canRedeem()
	{
		return false;
	}

}
