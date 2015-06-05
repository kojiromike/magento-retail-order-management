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

class EbayEnterprise_GiftCard_Model_Container implements EbayEnterprise_GiftCard_Model_IContainer
{
    const SESSION_STORAGE_KEY = 'ebay_enterprise_gift_card_container_storage';
    /** @var SplObjectStorage */
    protected $_giftCardStorage;
    /** @var Mage_Core_Model_Session_Abstract checkout/session */
    protected $_session;
    /**
     * Depends on the checkout/session but is not injected via the constructor as
     * the constructor may be called prior to the session being initialized.
     * @param array $initParams May contain:
     *                          - 'gift_card_storage' => SplObjectStorage
     */
    public function __construct(array $initParams = array())
    {
        list($this->_giftCardStorage) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'gift_card_storage', new SplObjectStorage())
        );
    }
    /**
     * Type checks for self::__construct $initParams
     * @param  Mage_Core_Model_Session_Abstract $checkoutSession
     * @param  SplObjectStorage            $giftCardStorage
     * @return mixed[]
     */
    protected function _checkTypes(
        SplObjectStorage $giftCardStorage
    ) {
        return array($giftCardStorage);
    }
    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array $arr
     * @param  string|int $field Valid array key
     * @param  mixed $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }
    /**
     * Get the gift card object storage. Includes logic to load the object storage
     * from the session the first time.
     * @return SplObjectStorage
     */
    protected function _getGiftCardStorage()
    {
        // Session will be null prior to checking for existing gift card object storage.
        if (is_null($this->_session)) {
            $this->_session = Mage::getSingleton('checkout/session');
            // If the session has gift card data, it should always replace any data
            // that may have been injected. If the session does not have data, make
            // sure it gets an object storage set to hold the gift cards.
            $this->_giftCardStorage = $this->_session->getDataSetDefault(self::SESSION_STORAGE_KEY, $this->_giftCardStorage);
        }
        return $this->_giftCardStorage;
    }
    public function updateGiftCard(EbayEnterprise_GiftCard_Model_IGiftcard $card)
    {
        $this->_validateGiftCardForStorate($card);
        $this->_getGiftCardStorage()->attach($card);
        return $this;
    }
    protected function _validateGiftCardForStorate(EbayEnterprise_GiftCard_Model_IGiftcard $card)
    {
        if ($card->getCardNumber()) {
            return $this;
        }
        throw Mage::exception('EbayEnterprise_GiftCard', 'Invalid gift card.');
    }
    public function getUnredeemedGiftCards()
    {
        $cards = new SplObjectStorage();
        foreach ($this->_getGiftCardStorage() as $card) {
            if (!$card->getIsRedeemed()) {
                $cards->attach($card);
            }
        }
        $cards->rewind();
        return $cards;
    }
    public function getRedeemedGiftCards()
    {
        $cards = new SplObjectStorage();
        foreach ($this->_getGiftCardStorage() as $card) {
            if ($card->getIsRedeemed()) {
                $cards->attach($card);
            }
        }
        $cards->rewind();
        return $cards;
    }
    public function getAllGiftCards()
    {
        return clone $this->_getGiftCardStorage();
    }
    public function getGiftCard($cardNumber)
    {
        foreach ($this->_getGiftCardStorage() as $card) {
            if ($card->getCardNumber() === $cardNumber) {
                return $card;
            }
        }
        return Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber);
    }
    public function removeGiftCard(EbayEnterprise_GiftCard_Model_IGiftcard $giftcard)
    {
        $this->_getGiftCardStorage()->detach($giftcard);
        return $this;
    }
    public function removeAllGiftCards()
    {
        $this->_getGiftCardStorage()->removeAll($this->_getGiftCardStorage());
    }
}
