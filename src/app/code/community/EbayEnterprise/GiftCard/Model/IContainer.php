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

interface EbayEnterprise_GiftCard_Model_IContainer
{
    /**
     * Add a gift card to the container
     * @param EbayEnterprise_GiftCard_Model_IGiftcard $card
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If card is not valid to be added to the container - must have card number.
     */
    public function updateGiftCard(EbayEnterprise_GiftCard_Model_IGiftcard $card);
    /**
     * Get any gift cards that have not been redeemed.
     * @return SplObjectStorage
     */
    public function getUnredeemedGiftCards();
    /**
     * Get all gift cards that have been redeemed.
     * @return SplObjectStorage
     */
    public function getRedeemedGiftCards();
    /**
     * Get all gift cards.
     * @return SplObjectStorage
     */
    public function getAllGiftCards();
    /**
     * Get a gift card by card number. If no card with that number exists in the
     * container, a new instance with that card number will be returned.
     * @param  string $cardNumber
     * @return EbayEnterprise_GiftCard_Model_IGiftcard
     */
    public function getGiftCard($cardNumber);
    /**
     * Remove the gift card from the container.
     * @param  EbayEnterprise_GiftCard_Model_IGiftcard $giftcard
     * @return self
     */
    public function removeGiftCard(EbayEnterprise_GiftCard_Model_IGiftcard $giftcard);
    /**
     * Remove all gift cards from the container.
     * @return self
     */
    public function removeAllGiftCards();
}
