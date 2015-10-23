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
 * Memento of gift card data used by EbayEnterprise_GiftCard_Model_Giftcard.
 * This model will be saved in the session to persist gift card data so object
 * should be kept as lightweight as possible to prevent session bloat - e.g.
 * kept to primitive values or simple objects that can be serialized.
 */
class EbayEnterprise_GiftCard_Model_Giftcard_Memo implements EbayEnterprise_Eb2cCore_Model_ISessionsafe
{
    /** @var bool **/
    protected $panIsToken;
    /** @var string **/
    protected $orderId;
    /** @var string **/
    protected $cardNumber;
    /** @var string **/
    protected $pin;
    /** @var string **/
    protected $tokenizedCardNumber;
    /** @var string */
    protected $balanceRequestId;
    /** @var string */
    protected $redeemRequestId;
    /** @var string */
    protected $redeemVoidRequestId;
    /** @var float **/
    protected $amountToRedeem;
    /** @var float **/
    protected $amountRedeemed;
    /** @var DateTime **/
    protected $redeemedAt;
    /** @var string **/
    protected $redeemCurrencyCode;
    /** @var float **/
    protected $balanceAmount;
    /** @var string **/
    protected $balanceCurrencyCode;
    /** @var bool **/
    protected $isRedeemed;
    /** @var string **/
    protected $tenderType;

    /**
     * Id of the order the gift card is applied to.
     *
     * @param string
     * @return self
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set the card number of the gift card.
     *
     * @param string $cardNumber
     * @return self
     */
    public function setCardNumber($cardNumber)
    {
        $this->cardNumber = $cardNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardNumber()
    {
        return $this->cardNumber;
    }

    /**
     * Set the tender type of the gift card.
     *
     * @param string
     * @return self
     */
    public function setTenderType($tenderType)
    {
        $this->tenderType = $tenderType;
        return $this;
    }

    /**
     * @return string
     */
    public function getTenderType()
    {
        return $this->tenderType;
    }

    /**
     * Set the tokenized gift card number.
     *
     * @param string
     * @return self
     */
    public function setTokenizedCardNumber($tokenizedCardNumber)
    {
        $this->tokenizedCardNumber = $tokenizedCardNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getTokenizedCardNumber()
    {
        return $this->tokenizedCardNumber;
    }

    /**
     * Set the gift card pin.
     *
     * @param string
     * @return self
     */
    public function setPin($pin)
    {
        $this->pin = $pin;
        return $this;
    }

    /**
     * @return string
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * Set if the gift card number is tokenized.
     *
     * @param bool
     * @return self
     */
    public function setPanIsToken($isToken)
    {
        $this->panIsToken = $isToken;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPanIsToken()
    {
        return $this->panIsToken;
    }

    /**
     * Set the request id from the balance request.
     *
     * @param string
     * @return self
     */
    public function setBalanceRequestId($requestId)
    {
        $this->balanceRequestId = $requestId;
        return $this;
    }

    /**
     * @return string
     */
    public function getBalanceRequestId()
    {
        return $this->balanceRequestId;
    }

    /**
     * Set the request id of the gift card redeem request.
     *
     * @param string
     * @return self
     */
    public function setRedeemRequestId($requestId)
    {
        $this->redeemRequestId = $requestId;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedeemRequestId()
    {
        return $this->redeemRequestId;
    }

    /**
     * Set the request id of the gift card redeem void request.
     *
     * @param string
     * @return self
     */
    public function setRedeemVoidRequestId($requestId)
    {
        $this->redeemVoidRequestId = $requestId;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedeemVoidRequestId()
    {
        return $this->redeemVoidRequestId;
    }

    /**
     * Set the amount to redeem from the gift card for the order.
     *
     * @param float
     * @return self
     */
    public function setAmountToRedeem($amount)
    {
        $this->amountToRedeem = $amount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountToRedeem()
    {
        return (float) $this->amountToRedeem;
    }

    /**
     * Set the amount that has been redeemed from the gift card for the order.
     *
     * @param float
     * @return self
     */
    public function setAmountRedeemed($amount)
    {
        $this->amountRedeemed = $amount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountRedeemed()
    {
        return (float) $this->amountRedeemed;
    }

    /**
     * Set the currency code of the currency used to represent amounts.
     *
     * @param string
     * @return self
     */
    public function setRedeemCurrencyCode($currencyCode)
    {
        $this->redeemCurrencyCode = $currencyCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedeemCurrencyCode()
    {
        return $this->redeemCurrencyCode;
    }

    /**
     * Set the balance amount on the gift card.
     *
     * @param float
     * @return self
     */
    public function setBalanceAmount($amount)
    {
        $this->balanceAmount = $amount;
        return $this;
    }

    /**
     * @return float
     */
    public function getBalanceAmount()
    {
        return (float) $this->balanceAmount;
    }

    /**
     * Set the currency code used to represent the amount of the gift card balance.
     *
     * @param string
     * @return self
     */
    public function setBalanceCurrencyCode($currencyCode)
    {
        $this->balanceCurrencyCode = $currencyCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getBalanceCurrencyCode()
    {
        return $this->balanceCurrencyCode;
    }

    /**
     * Set if the gift card has been redeemed for the order.
     *
     * @param bool
     * @return self
     */
    public function setIsRedeemed($isRedeemed)
    {
        $this->isRedeemed = $isRedeemed;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsRedeemed()
    {
        return $this->isRedeemed;
    }

    /**
     * Set the time at which the gift card was redeemed.
     *
     * @param DateTime
     * @return self
     */
    public function setRedeemedAt(DateTime $redeemedAt)
    {
        $this->redeemedAt = $redeemedAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRedeemedAt()
    {
        return $this->redeemedAt ?: new DateTime();
    }
}
