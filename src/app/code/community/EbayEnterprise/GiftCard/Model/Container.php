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
    /** @var SplObjectStorage */
    protected $defaultGiftCardStorage;
    /** @var EbayEnterprise_GiftCard_Model_Session */
    protected $session;

    /**
     * Depends on a session instance but a default instance is not created in
     * the constructor in case this model is instantiated before sessions are
     * started.
     *
     * @param array $init May contain:
     *                    - 'gift_card_storage' => SplObjectStorage
     *                    - 'session' => EbayEnterprise_GiftCard_Model_Session
     */
    public function __construct(array $init = array())
    {
        list(
            $this->defaultGiftCardStorage,
            // Allows injection but does not instantiate a default - prevents early session instantiation
            $this->session
        ) = $this->checkTypes(
            $this->nullCoalesce($init, 'gift_card_storage', new SplObjectStorage()),
            $this->nullCoalesce($init, 'session', null)
        );
    }

    /**
     * Type checks for self::__construct $init
     * @param  SplObjectStorage
     * @param  EbayEnterprise_GiftCard_Model_Session|null
     * @return mixed[]
     */
    protected function checkTypes(
        SplObjectStorage $defaultGiftCardStorage,
        EbayEnterprise_GiftCard_Model_Session $session = null
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array $arr
     * @param  string|int $field Valid array key
     * @param  mixed $default
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Get the gift card object storage.
     *
     * Will inject the existing gift card storage into the session if the
     * session does not already have one.
     *
     * @return SplObjectStorage
     */
    protected function getGiftCardStorage()
    {
        // When the session does not have a storage object already, use the
        // storage object in this instance as a starting point. If the session
        // does have a storage object already, ignore whatever this instance
        // was given and use what the session has.
        return $this->getSession()->getContainerStorageSetDefault($this->defaultGiftCardStorage);
    }

    public function updateGiftCard(EbayEnterprise_GiftCard_Model_IGiftcard $card)
    {
        $this->validateGiftCardForStorage($card);
        $this->getGiftCardStorage()->attach($card->getMemo());
        return $this;
    }

    /**
     * Validate a gift card, ensuring that it has at least the bare minimum
     * of data to be useful.
     *
     * @param EbayEnterprise_GiftCard_Model_IGiftcard
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If the gift card is invalid.
     */
    protected function validateGiftCardForStorage(EbayEnterprise_GiftCard_Model_IGiftcard $card)
    {
        if ($card->getCardNumber()) {
            return $this;
        }
        throw Mage::exception('EbayEnterprise_GiftCard', 'Invalid gift card.');
    }

    public function getUnredeemedGiftCards()
    {
        $cards = new SplObjectStorage();
        foreach ($this->getGiftCardStorage() as $cardMemo) {
            if (!$cardMemo->getIsRedeemed()) {
                $cards->attach($this->createGiftCardFromMemo($cardMemo));
            }
        }
        $cards->rewind();
        return $cards;
    }

    public function getRedeemedGiftCards()
    {
        $cards = new SplObjectStorage();
        foreach ($this->getGiftCardStorage() as $cardMemo) {
            if ($cardMemo->getIsRedeemed()) {
                $cards->attach($this->createGiftCardFromMemo($cardMemo));
            }
        }
        $cards->rewind();
        return $cards;
    }

    public function getAllGiftCards()
    {
        $cards = new SplObjectStorage();
        foreach ($this->getGiftCardStorage() as $cardMemo) {
            $cards->attach($this->createGiftCardFromMemo($cardMemo));
        }
        return $cards;
    }

    public function getGiftCard($cardNumber)
    {
        foreach ($this->getGiftCardStorage() as $cardMemo) {
            if ($cardMemo->getCardNumber() === $cardNumber) {
                return $this->createGiftCardFromMemo($cardMemo);
            }
        }
        return Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber);
    }

    public function removeGiftCard(EbayEnterprise_GiftCard_Model_IGiftcard $giftcard)
    {
        $this->getGiftCardStorage()->detach($giftcard->getMemo());
        return $this;
    }

    public function removeAllGiftCards()
    {
        $this->getGiftCardStorage()->removeAll($this->getGiftCardStorage());
    }

    /**
     * Create a gift card model, restoring it from the externalized memo data
     * from the session.
     *
     * @param EbayEnterprise_GiftCard_Model_Giftcard_Memo
     * @return EbayEnterprise_GiftCard_Model_Giftcard
     */
    protected function createGiftCardFromMemo(EbayEnterprise_GiftCard_Model_Giftcard_Memo $cardMemo)
    {
        return Mage::getModel('ebayenterprise_giftcard/giftcard')->restoreFromMemo($cardMemo);
    }

    /**
     * Get the session object used to persist gift card data.
     *
     * @return EbayEnterprise_GiftCard_Model_Session
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = Mage::getSingleton('ebayenterprise_giftcard/session');
        }
        return $this->session;
    }
}
