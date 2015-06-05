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
    protected $_allowAdd = true;
    /** @var bool */
    protected $_allowBalance = true;
    /** @var string */
    protected $_addAction;
    /** @var string */
    protected $_balanceAction;
    /** @var EbayEnterprise_GiftCard_Model_IGiftcard */
    protected $_giftCard;

    /**
     * Get the checkout session.
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
    /**
     * Get the current gift card, may be empty gift card instance if no gift card in session.
     * Not set in self::_construct to prevent accessing the session too early.
     * @return EbayEnterprise_GiftCard_Model_IGiftcard
     */
    public function getGiftCard()
    {
        if (is_null($this->_giftCard)) {
            // Look for a current gift card in the session to repopulate the form with,
            // otherwise use an empty instance. Do not clear the gift card from the
            // session. The balance block will be responsible for that.
            $this->_giftCard = $this->_getCheckoutSession()->getEbayEnterpriseCurrentGiftCard() ?: Mage::getModel('ebayenterprise_giftcard/giftcard');
        }
        return $this->_giftCard;
    }
    /**
     * Set if the form allows adding gift cards to the order.
     * @param  bool $allow
     * @return self
     */
    public function allowAdd($allow)
    {
        $this->_allowAdd = $allow;
        return $this;
    }
    /**
     * Indicates if the form should allow adding gift cards to the order.
     * @return bool
     */
    public function isAddAllowed()
    {
        return $this->_allowAdd;
    }
    /**
     * Set if the form allows checking a gift card balance
     * @param  bool $allow
     * @return self
     */
    public function allowBalance($allow)
    {
        $this->_allowBalance = $allow;
        return $this;
    }
    /**
     * Indicates if the form should allow checking a gift card balance
     * @return bool
     */
    public function isBalanceAllowed()
    {
        return $this->_allowBalance;
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
        $this->_balanceAction = $action;
        return $this;
    }
    /**
     * Get the balance action path - defaults to const value
     * @return string
     */
    public function getBalanceAction()
    {
        return $this->_balanceAction ?: static::DEFAULT_BALANCE_ACTION;
    }
    /**
     * Set the add action path
     * @param string $action
     * @return self
     */
    public function setAddAction($action)
    {
        $this->_addAction = $action;
        return $this;
    }
    /**
     * Get the add action path - defaults to const value
     * @return string
     */
    public function getAddAction()
    {
        return $this->_addAction ?: static::DEFAULT_ADD_ACTION;
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
