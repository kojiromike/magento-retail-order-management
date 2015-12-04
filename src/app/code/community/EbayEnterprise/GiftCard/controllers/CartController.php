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

    /**
     * add a giftcard to the cart.
     */
    public function addAction()
    {
        list($cardNumber, $pin) = $this->_getCardInfoFromRequest();

        if ($cardNumber) {

            $container = $this->_getContainer();
            // try a balance request.
            $giftcard = $container->getGiftCard($cardNumber)->setPin($pin);

            try {

                $this->_helper->addGiftCardToOrder($giftcard, $container);
                $this->_getCheckoutSession()
                    ->addSuccess($this->_helper->__(EbayEnterprise_GiftCard_Helper_Data::GIFT_CARD_ADD_SUCCESS, $giftcard->getCardNumber()));

            } catch (EbayEnterprise_GiftCard_Exception $e) {

                $this->_getCheckoutSession()->addError($this->_helper->__($e->getMessage()));

            }
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

        $this->_getCheckoutSession()
            ->addSuccess($this->_helper->__(EbayEnterprise_GiftCard_Helper_Data::GIFT_CARD_REMOVE_SUCCESS, $giftcard->getCardNumber()));

        $this->_redirect(static::REDIRECT_PATH);
    }
}
