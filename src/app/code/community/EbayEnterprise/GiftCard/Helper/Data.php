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

class EbayEnterprise_GiftCard_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    const ZERO_BALANCE_CARD_MESSAGE = 'EbayEnterprise_GiftCard_Zero_Balance_Card';
    /** @var EbayEnterprise_GiftCard_Model_IContainer */
    protected $_giftCardContainer;

    public function __construct(array $initParams = array())
    {
        list($this->_giftCardContainer) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'gift_card_container', Mage::getModel('ebayenterprise_giftcard/container'))
        );
    }

    /**
     * Type checks for self::__construct $initParams
     * @param  EbayEnterprise_GiftCard_Model_IContainer $checkoutSession
     * @param  EbayEnterprise_MageLog_Helper_Data $logger
     * @return mixed[]
     */
    protected function _checkTypes(
        EbayEnterprise_GiftCard_Model_IContainer $container
    ) {
        return array($container);
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array      $arr
     * @param  string|int $field Valid array key
     * @param  mixed      $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
     * @param mixed $store
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_giftcard/config'));
    }

    /**
     * Add a gift card to the container. Will make the gift card balance check
     * and make sure card can be applied to the order.
     * @param EbayEnterprise_GiftCard_Model_IGiftcard $card
     * @return self
     */
    public function addGiftCardToOrder(EbayEnterprise_GiftCard_Model_IGiftcard $card)
    {
        try {
            $card->checkBalance();
            // only add cards with an available balance
            if ($card->getBalanceAmount() > 0) {
                $this->_giftCardContainer->updateGiftCard($card);
            } else {
                // throw exception to trigger error handling below, let 0 balance card
                // get handled like any other balance check failure
                throw Mage::exception('EbayEnterprise_GiftCard', $this->__(self::ZERO_BALANCE_CARD_MESSAGE, $card->getCardNumber()));
            }
        } catch (EbayEnterprise_GiftCard_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($this->__($e->getMessage()));
        }
        return $this;
    }
}
