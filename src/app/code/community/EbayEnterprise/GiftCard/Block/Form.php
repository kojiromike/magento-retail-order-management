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

class EbayEnterprise_GiftCard_Block_Form extends EbayEnterprise_GiftCard_Block_Template_Abstract
{
    const DEFAULT_BALANCE_ACTION = 'ebayenterprise_giftcard/cart/balance';
    const DEFAULT_ADD_ACTION = 'ebayenterprise_giftcard/cart/add';
    /** @var bool */
    protected $allowAdd = true;
    /** @var bool */
    protected $allowBalance = true;
    /** @var string */
    protected $addAction;
    /** @var string */
    protected $balanceAction;
    /** @var EbayEnterprise_GiftCard_Model_IGiftcard */
    protected $giftCard;
    /** @var EbayEnterprise_GiftCard_Model_Session */
    protected $giftCardSession;

    /**
     * Get the checkout session.
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
     * Get the current gift card, may be empty gift card instance if no gift card in session.
     * Not set in self::_construct to prevent accessing the session too early.
     * @return EbayEnterprise_GiftCard_Model_IGiftcard
     */
    public function getGiftCard()
    {
        if (is_null($this->giftCard)) {
            // Look for a current gift card in the session to repopulate the form with,
            // otherwise use an empty instance. Do not clear the gift card from the
            // session. The balance block will be responsible for that.
            $this->giftCard = $this->getGiftCardSession()->getEbayEnterpriseCurrentGiftCard() ?: Mage::getModel('ebayenterprise_giftcard/giftcard');
        }
        return $this->giftCard;
    }
    /**
     * Set if the form allows adding gift cards to the order.
     * @param  bool $allow
     * @return self
     */
    public function allowAdd($allow)
    {
        $this->allowAdd = $allow;
        return $this;
    }
    /**
     * Indicates if the form should allow adding gift cards to the order.
     * @return bool
     */
    public function isAddAllowed()
    {
        return $this->allowAdd;
    }
    /**
     * Set if the form allows checking a gift card balance
     * @param  bool $allow
     * @return self
     */
    public function allowBalance($allow)
    {
        $this->allowBalance = $allow;
        return $this;
    }
    /**
     * Indicates if the form should allow checking a gift card balance
     * @return bool
     */
    public function isBalanceAllowed()
    {
        return $this->allowBalance;
    }
    /**
     * Get the default action for the form to post to. If add is allowed from the
     * form, defaults to the add action. Otherwise the balance action.
     * @return string
     */
    public function getPostAction()
    {
        return $this->isAddAllowed() ? $this->getAddActionUrl() : $this->getBalanceActionUrl();
    }
    /**
     * Set the balance action path
     * @param string $action
     * @return self
     */
    public function setBalanceAction($action)
    {
        $this->balanceAction = $action;
        return $this;
    }
    /**
     * Get the balance action path - defaults to const value
     * @return string
     */
    public function getBalanceAction()
    {
        return $this->balanceAction ?: static::DEFAULT_BALANCE_ACTION;
    }
    /**
     * Set the add action path
     * @param string $action
     * @return self
     */
    public function setAddAction($action)
    {
        $this->addAction = $action;
        return $this;
    }
    /**
     * Get the add action path - defaults to const value
     * @return string
     */
    public function getAddAction()
    {
        return $this->addAction ?: static::DEFAULT_ADD_ACTION;
    }
    /**
     * Get the URL for the controller action to add a gift card to the cart
     * @return string
     */
    public function getAddActionUrl()
    {
        return Mage::getUrl($this->getAddAction());
    }
    /**
     * Get the URL for the controller action to check a gift card balance
     * @return string
     */
    public function getBalanceActionUrl()
    {
        return Mage::getUrl($this->getBalanceAction());
    }
}
