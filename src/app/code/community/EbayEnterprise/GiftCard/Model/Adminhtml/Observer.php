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
 * Observer for gift card events in the admin.
 */
class EbayEnterprise_GiftCard_Model_Adminhtml_Observer
{
    // post data fields for gift cards
    const CARD_NUMBER_PARAM = 'ebay_enterprise_giftcard_code';
    const CARD_PIN_PARAM = 'ebay_enterprise_giftcard_pin';
    const ACTION_PARAM = 'ebay_enterprise_giftcard_action';
    // action "flags" expected to be sent in the post data
    const ADD_ACTION = 'add';
    const REMOVE_ACTION = 'remove';
    /** @var array post data */
    protected $request;
    /** @var EbayEnterprise_GiftCard_Model_IContainer */
    protected $container;
    /** @var EbayEnterprise_GiftCard_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var Mage_Adminhtml_Model_Session_Quote */
    protected $session;

    /**
     * @param array
     */
    public function __construct(array $args = [])
    {
        list(
            $this->container,
            $this->helper,
            $this->logger,
            $this->logContext,
            $this->session
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'container', Mage::getModel('ebayenterprise_giftcard/container')),
            $this->nullCoalesce($args, 'helper', Mage::helper('ebayenterprise_giftcard')),
            $this->nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($args, 'session', null)
        );
    }

    /**
     * @param EbayEnterprise_GiftCard_Model_IContainer
     * @param EbayEnterprise_GiftCard_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @param Mage_Adminhtml_Model_Session_Quote|null
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_GiftCard_Model_IContainer $container,
        EbayEnterprise_GiftCard_Helper_Data $helper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext,
        Mage_Adminhtml_Model_Session_Quote $session = null
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Process post data and set usage of GC into order creation model
     *
     * @param Varien_Event_Observer $observer
     * @return self
     */
    public function processOrderCreationData(Varien_Event_Observer $observer)
    {
        if ($this->helper->getConfigModel()->isEnabled) {
            $this->request = $observer->getEvent()->getRequest();
            list($cardNumber, $pin) = $this->getCardInfoFromRequest();
            if ($cardNumber) {
                $this->processCard($cardNumber, $pin);
            }
        }
        return $this;
    }

    /**
     * Add or remove the gift card, depending on the requested action.
     *
     * @param string $cardNumber
     * @param string $pin
     * @return self
     */
    protected function processCard($cardNumber, $pin)
    {
        if ($this->isAddRequest()) {
            $this->addGiftCard($cardNumber, $pin);
        } elseif ($this->isRemoveRequest()) {
            $this->removeGiftCard($cardNumber);
        }
        return $this;
    }

    /**
     * Is the gift card action param in the request for an add.
     *
     * @return boolean
     */
    protected function isAddRequest()
    {
        return $this->getPostData(self::ACTION_PARAM, '') === self::ADD_ACTION;
    }

    /**
     * Is the gift card action param in the request for a remove.
     *
     * @return boolean
     */
    protected function isRemoveRequest()
    {
        return $this->getPostData(self::ACTION_PARAM, '') === self::REMOVE_ACTION;
    }

    /**
     * add a giftcard.
     *
     * @param string $cardNumber
     * @param string $pin
     * @return self
     */
    protected function addGiftCard($cardNumber, $pin)
    {
        $giftcard = $this->container->getGiftCard($cardNumber)->setPin($pin);
        try {
            $this->helper->addGiftCardToOrder($giftcard, $this->container);
            $this->getSession()->addSuccess($this->helper->__(EbayEnterprise_GiftCard_Helper_Data::GIFT_CARD_ADD_SUCCESS, $cardNumber));
        } catch (EbayEnterprise_GiftCard_Exception $e) {
            $this->getSession()->addError($this->helper->__($e->getMessage()));
            $this->logger->debug('Failed to add gift card to admin order. See exception log for more details.', $this->logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
        }
        return $this;
    }

    /**
     * remove a giftcard.
     *
     * @param string $cardNumber
     * @return self
     */
    protected function removeGiftCard($cardNumber)
    {
        $giftcard = $this->container->getGiftCard($cardNumber);
        $this->container->removeGiftCard($giftcard);
        return $this;
    }

    /**
     * Extract the card number and pin from the request. If either is not present,
     * will return an empty string for that value.
     *
     * @return string[] Tuple of card number and pin
     */
    protected function getCardInfoFromRequest()
    {
        return [$this->getPostData(self::CARD_NUMBER_PARAM, ''), $this->getPostData(self::CARD_PIN_PARAM, '')];
    }

    /**
     * Get post data from the request. If not set, return the default value.
     *
     * @param string|int $field
     * @param string $default
     * @return string
     */
    protected function getPostData($field, $default)
    {
        return $this->nullCoalesce($this->request, $field, $default);
    }

    /**
     * Get the adminhtml quote session.
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = Mage::getSingleton('adminhtml/session_quote');
        }
        return $this->session;
    }
}
