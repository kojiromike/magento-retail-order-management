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
    const GIFT_CARD_ADD_SUCCESS = 'EbayEnterprise_GiftCard_Cart_Add_Success';
    const GIFT_CARD_REMOVE_SUCCESS = 'EbayEnterprise_GiftCard_Cart_Remove_Success';

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
     * @throws EbayEnterprise_GiftCard_Exception If gift card could not be added to the order.
     */
    public function addGiftCardToOrder(
        EbayEnterprise_GiftCard_Model_IGiftcard $card,
        EbayEnterprise_GiftCard_Model_IContainer $giftCardContainer
    ) {
        $card->checkBalance();
        // Treat 0 balance gift cards as invalid.
        if ($card->getBalanceAmount() <= 0) {
            throw Mage::exception('EbayEnterprise_GiftCard', $this->__(self::ZERO_BALANCE_CARD_MESSAGE, $card->getCardNumber()));
        }
        $giftCardContainer->updateGiftCard($card);
        return $this;
    }
}
