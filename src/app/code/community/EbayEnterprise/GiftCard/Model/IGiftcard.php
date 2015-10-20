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

interface EbayEnterprise_GiftCard_Model_IGiftcard
{
    /**
     * Order increment id of the order the gift card is being applied to.
     * @param string $orderId
     * @return self
     */
    public function setOrderId($orderId);
    /**
     * Get the order increment id of the order the gift card is being applied to.
     * @return string
     */
    public function getOrderId();
    /**
     * Set the Gift Card number
     * @param string $cardNumber
     * @return self
     */
    public function setCardNumber($cardNumber);
    /**
     * Get the Gift Card number.
     * @return string
     */
    public function getCardNumber();
    /**
     * Get the tender type for the gift card.
     * @return string
     */
    public function getTenderType();
    /**
     * Set the tokenized Gift Card number
     * @param string $tokenizedCardNumber
     * @return self
     */
    public function setTokenizedCardNumber($tokenizedCardNumber);
    /**
     * Get the tokenized Gift Card number.
     * @return string
     */
    public function getTokenizedCardNumber();
    /**
     * Set the Gift Card PIN.
     * @param string $pin
     * @return self
     */
    public function setPin($pin);
    /**
     * Get the Gift Card PIN
     * @return string
     */
    public function getPin();
    /**
     * Set if the card number is tokenized
     * @param bool $isToken
     * @return self
     */
    public function setPanIsToken($isToken);
    /**
     * Get if the card number is tokenized
     * @return bool
     */
    public function getPanIsToken();
    /**
     * Set the request id to use for the Gift Card balance request. If making
     * a new request - not a duplicate request - should be reset prior to making
     * the new request.
     * @param string $requestId
     * @return self
     */
    public function setBalanceRequestId($requestId);
    /**
     * Get the request id to use for the Gift Card balance request.
     * @return string
     */
    public function getBalanceRequestId();
    /**
     * Set the request id to use for the Gift Card redeem request. If making
     * a new request - not a duplicate request - should be reset prior to making
     * the new request.
     * @param string $requestId
     * @return self
     */
    public function setRedeemRequestId($requestId);
    /**
     * Get the request id used for the Gift Card redeem request.
     * @return string
     */
    public function getRedeemRequestId();
    /**
     * Set the request id to use for redeem void requests. If making a new
     * request - not a duplicate request - should be reset prior to making the
     * new request.
     * @param string $requestId
     * @return self
     */
    public function setRedeemVoidRequestId($requestId);
    /**
     * Get the request id to use for redeem void requests.
     * @return string
     */
    public function getRedeemVoidRequestId();
    /**
     * Set the amount to redeem from teh gift card.
     * @param float $amount
     * @return self
     */
    public function setAmountToRedeem($amount);
    /**
     * Get the amount to redeem from the gift card.
     * @return float
     */
    public function getAmountToRedeem();
    /**
     * Set the amount that has been redeemed from the gift card.
     * @param float $amount
     * @return self
     */
    public function setAmountRedeemed($amount);
    /**
     * Get the amount that has been redeemed from the gift card.
     * @return float
     */
    public function getAmountRedeemed();
    /**
     * Set the currency code for the currency the redeem amount is in.
     * @param string $currencyCode
     * @return self
     */
    public function setRedeemCurrencyCode($currencyCode);
    /**
     * Get the currency code for the currency the redeem amount is in.
     * @return string
     */
    public function getRedeemCurrencyCode();
    /**
     * Set the remaining balance on the gift card.
     * @param float $amount
     * @return self
     */
    public function setBalanceAmount($amount);
    /**
     * Get the remaining balance on the gift card.
     * @return float
     */
    public function getBalanceAmount();
    /**
     * Set the currency code for the currency the balance amount is in.
     * @param string $currencyCode
     * @return self
     */
    public function setBalanceCurrencyCode($currencyCode);
    /**
     * Get the currency code for the currency the balance amount is in.
     * @return string
     */
    public function getBalanceCurrencyCode();
    /**
     * Set if the card has been redeemed for the order.
     * @param bool $isRedeemed
     * @return self
     */
    public function setIsRedeemed($isRedeemed);
    /**
     * Get if the card has been redeemed for the order.
     * @return bool
     */
    public function getIsRedeemed();
    /**
     * Restore gift card data from an external memento of gift card data.
     * @param EbayEnterprise_GiftCard_Model_Giftcard_Memo
     * @return self
     */
    public function restoreFromMemo(EbayEnterprise_GiftCard_Model_Giftcard_Memo $memo);
    /**
     * Retrieve the memo object of the gift card's data for storage and persistence
     * external to the gift card model.
     * @return EbayEnterprise_GiftCard_Model_Giftcard_Memo
     */
    public function getMemo();
    /**
     * Make a request to the ROM API to check the balance of the gift card and
     * update the card information based on the response.
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If card balance could not be checked
     */
    public function checkBalance();
    /**
     * Make a reqest to the ROM API to redeem the gift card and update the card
     * information with the response.
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If card could not be redeemed
     */
    public function redeem();
    /**
     * Make a request to the ROM API to void a redemption against the card and
     * update the card information with the response.
     * @return self
     * @throws EbayEnterprise_GiftCard_Exception If card redeem could not be voided
     */
    public function void();
}
