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

class EbayEnterprise_Eb2cPayment_Overrides_Model_Giftcardaccount extends Enterprise_GiftCardAccount_Model_Giftcardaccount
{
	const EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_ACCOUNT_EXISTS = 'EbayEnterprise_Eb2cPayment_GiftCard_Account_Exists';
	/**
	 * overrriding addToCart method in order to save the eb2c pan and pin field in the quote
	 * Add gift card to quote gift card storage
	 *
	 * @param bool $saveQuote
	 * @param null $quote
	 * @throws Mage_Core_Exception
	 * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function addToCart($saveQuote=true, $quote=null)
	{
		if (is_null($quote)) {
			$quote = $this->_getCheckoutSession()->getQuote();
		}
		$website = Mage::app()->getStore($quote->getStoreId())->getWebsite();
		if ($this->isValid(true, true, $website)) {
			$cards = Mage::helper('enterprise_giftcardaccount')->getCards($quote);
			if (!$cards) {
				$cards = array();
			} else {
				foreach ($cards as $one) {
					if ($one['i'] == $this->getId()) {
						throw Mage::exception('Mage_Core',
							Mage::helper('enterprise_giftcardaccount')->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_ACCOUNT_EXISTS)
						);
					}
				}
			}
			$cards[] = array(
				'i' => $this->getId(),
				'c' => $this->getCode(),
				'a' => $this->getBalance(), // amount
				'ba' => $this->getBalance(), // base amount
				'pan' => $this->getEb2cPan(),
				'pin' => $this->getEb2cPin(),
			);
			Mage::helper('enterprise_giftcardaccount')->setCards($quote, $cards);
			if ($saveQuote) {
				$quote->save();
			}
		}
		return $this;
	}
}
