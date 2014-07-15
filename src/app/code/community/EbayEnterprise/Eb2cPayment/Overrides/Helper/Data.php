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


class EbayEnterprise_Eb2cPayment_Overrides_Helper_Data extends Enterprise_GiftCardAccount_Helper_Data
{
	/**
	 * Maximal gift card pan length according to database table definitions (longer codes are truncated)
	 */
	const GIFT_CARD_PAN_MAX_LENGTH = 22;

	/**
	 * Maximal gift card pin length according to database table definitions (longer codes are truncated)
	 */
	const GIFT_CARD_PIN_MAX_LENGTH = 8;
}
