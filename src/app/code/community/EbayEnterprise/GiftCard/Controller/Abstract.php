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

class EbayEnterprise_GiftCard_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    // names of form fields used to submit gift card number and pin
    const CARDNUMBER_FIELD = 'ebay_enterprise_giftcard_code';
    const PIN_FIELD = 'ebay_enterprise_giftcard_pin';
    // path to redirect to after the action is performed.
    const REDIRECT_PATH = '*/*/';

    /** @var EbayEnterprise_GiftCard_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry Loaded with Gift Card config */
    protected $_config;
    /** @var EbayEnterprise_GiftCard_Model_IContainer */
    protected $_container;
    /** @var Mage_Checkout_Model_Session */
    protected $_checkoutSession;
    /** @var EbayEnterprise_GiftCard_Model_Session */
    protected $_giftCardSession;

    protected function _construct()
    {
        $this->_helper = Mage::helper('ebayenterprise_giftcard');
        $this->_config = $this->_helper->getConfigModel();
    }
    /**
     * Get the container that stores all the gift cards. Cannot be instantiated
     * during the controller's `_construct` like other dependencies as the conteiner
     * depends on the session, which will not have been initialized when the
     * controller is constructed.
     * @return EbayEnterprise_GiftCard_Model_IContainer
     */
    protected function _getContainer()
    {
        if (is_null($this->_container)) {
            $this->_container = Mage::getModel('ebayenterprise_giftcard/container');
        }
        return $this->_container;
    }
    /**
     * Get the checkout session - not set in constructor to help prevent any
     * potential issues with instiating the session prior to the session being set
     * up by Magento.
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        if (!$this->_checkoutSession) {
            $this->_checkoutSession = Mage::getSingleton('checkout/session');
        }
        return $this->_checkoutSession;
    }
    /**
     * Get the gift card session.
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getGiftCardSession()
    {
        if (!$this->_giftCardSession) {
            $this->_giftCardSession = Mage::getSingleton('ebayenterprise_giftcard/session');
        }
        return $this->_giftCardSession;
    }
    /**
     * Disable controller actions when gift cards are disabled.
     * @return self
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!$this->_config->isEnabledFlag) {
            // when gift cards are disabled, forward requests to 404 pages
            $this->setFlag('', static::FLAG_NO_DISPATCH, true)->_forward('/noroute');
        }
        return $this;
    }
    /**
     * Check the balance of a gift card.
     */
    public function balanceAction()
    {
        $checkoutSession = $this->_getCheckoutSession();
        $giftcardSession = $this->_getGiftCardSession();
        list($cardNumber, $pin) = $this->_getCardInfoFromRequest();
        // try a balance request.
        $giftcard = $this->_getContainer()->getGiftCard($cardNumber)->setPin($pin);
        try {
            $giftcard->checkBalance();
            $giftcardSession->setEbayEnterpriseCurrentGiftCard($giftcard);
        } catch (EbayEnterprise_GiftCard_Exception $e) {
            $checkoutSession->addError($this->_helper->__($e->getMessage()));
        }
        if ($this->getRequest()->isAjax()) {
            $this->loadLayout();
            $this->renderLayout();
        } else {
            $this->_redirect(static::REDIRECT_PATH);
        }
    }
    /**
     * Extract the card number and pin from the request.
     * from the request.
     * @return string[]
     */
    protected function _getCardInfoFromRequest()
    {
        // the form requires that both pin and cardnumber fields be
        // non-empty, so no special handling for empty parameters should
        // be necessary
        $request = $this->getRequest();
        $cardNumber = trim($request->getParam(self::CARDNUMBER_FIELD, ''));
        $pin = trim($request->getParam(self::PIN_FIELD, ''));
        return array($cardNumber, $pin);
    }
}
