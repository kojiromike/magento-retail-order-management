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

class EbayEnterprise_GiftCard_Block_Balance extends EbayEnterprise_GiftCard_Block_Template_Abstract
{
    /** @var EbayEnterprise_GiftCard_Model_IGiftcard */
    protected $giftCard;
    /** @var array */
    protected $errors = [];
    /** @var bool */
    protected $isHandleMessages = false;
    /** @var Mage_Checkout_Model_Session */
    protected $checkoutSession;
    /** @var EbayEnterprise_GiftCard_Model_Session */
    protected $giftCardSession;

    /**
     * Get the checkout session - not set in constructor to help prevent any
     * potential issues with instiating the session prior to the session being set
     * up by Magento.
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function getCheckoutSession()
    {
        if (!$this->checkoutSession) {
            $this->checkoutSession = Mage::getSingleton('checkout/session');
        }
        return $this->checkoutSession;
    }

    /**
     * Get the gift card session.
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function getGiftCardSession()
    {
        if (!$this->giftCardSession) {
            $this->giftCardSession = Mage::getSingleton('ebayenterprise_giftcard/session');
        }
        return $this->giftCardSession;
    }

    /**
     * Get the current gift card pulled from the session or null of no gift card in session.
     * @return EbayEnterprise_GiftCard_Model_IGiftcard|null
     */
    public function getGiftCard()
    {
        if (is_null($this->giftCard)) {
            $this->giftCard = $this->getGiftCardSession()->getEbayEnterpriseCurrentGiftCard(true);
        }
        return $this->giftCard;
    }
    /**
     * Get the balance of the gift card, formatted as currency for output.
     * @return string
     */
    public function getCardBalance()
    {
        return $this->giftCard
            ? Mage::helper('core')->formatCurrency($this->giftCard->getBalanceAmount(), true, false)
            : '';
    }
    /**
     * Get any error messages from the session.
     * @return Mage_Core_Model_Message_Abstract[]
     */
    public function getErrorMessages()
    {
        // Check flag which may have been set in layout XML or controller action (post-construct).
        // If this block should be handling session messages, get the messages from
        // the session and store them. Then clear out any session messages.
        if ($this->getHandleSessionMessages() && !$this->errors) {
            $sessionMessages = $this->getCheckoutSession()->getMessages();
            $this->errors = $sessionMessages->getErrors();
            $sessionMessages->clear();
        }
        return $this->errors;
    }
}
