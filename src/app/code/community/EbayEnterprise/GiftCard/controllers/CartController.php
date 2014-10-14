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

class EbayEnterprise_GiftCard_CartController extends EbayEnterprise_GiftCard_Controller_Abstract
{
	//** @see EbayEnterprise_GiftCard_Controller_Abstract */
	const REDIRECT_PATH = 'checkout/cart/';
	const GIFT_CARD_ADD_SUCCESS = 'EbayEnterprise_GiftCard_Cart_Add_Success';
	const GIFT_CARD_REMOVE_SUCCESS = 'EbayEnterprise_GiftCard_Cart_Remove_Success';
	/**
	 * add a giftcard to the cart.
	 */
	public function addAction()
	{
		list($cardNumber, $pin) = $this->_getCardInfoFromRequest();
		if ($cardNumber) {
			// try a balance request.
			$giftcard = $this->_getContainer()->getGiftCard($cardNumber)->setPin($pin);
			$this->_helper->addGiftCardToOrder($giftcard);
		}
		$this->_redirect(static::REDIRECT_PATH);
	}
	/**
	 * remove a giftcard from the cart.
	 */
	public function removeAction()
	{
		list($cardNumber) = $this->_getCardInfoFromRequest();
		$giftcard = $this->_getContainer()->getGiftCard($cardNumber);
		$this->_getContainer()->removeGiftCard($giftcard);
		Mage::getSingleton('checkout/session')->addSuccess($this->_helper->__(self::GIFT_CARD_REMOVE_SUCCESS, $giftcard->getCardNumber()));
		$this->_redirect(static::REDIRECT_PATH);
	}
}
