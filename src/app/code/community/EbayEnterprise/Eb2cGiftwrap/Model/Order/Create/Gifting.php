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
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use eBayEnterprise\RetailOrderManagement\Payload\Order\IGifting;

class EbayEnterprise_Eb2cGiftwrap_Model_Order_Create_Gifting
{
    /** @var EbayEnterprise_Eb2cGiftwrap_Helper_Data */
    protected $_helper;

    public function __construct($args = array())
    {
        list(
            $this->_helper
        ) = $this->_checkTypes(
            $this->_nullCoalesce($args, 'helper', Mage::helper('eb2cgiftwrap'))
        );
    }

    /**
     * enforce injected types
     * @param  EbayEnterprise_Eb2cGiftwrap_Helper_Data
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Eb2cGiftwrap_Helper_Data $helper
    ) {
        return func_get_args();
    }

    /**
     * @param array
     * @param string|int
     * @param mixed
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $key, $default = null)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    public function injectGifting(
        Varien_Object $giftingItem,
        IGifting $giftingPayload
    ) {
        $this->_addEnvelopeInfo($giftingPayload, $giftingItem)
            ->_addGiftWrapItem($giftingPayload, $giftingItem)
            ->_addGiftWrapPricing($giftingPayload, $giftingItem);
        return $this;
    }

    /**
     * add giftwrap/giftcard pricing to the payload
     * @param  IGifting
     * @param  Varien_Object
     * @return self
     */
    protected function _addGiftWrapPricing(IGifting $giftingPayload, Varien_Object $giftingItem)
    {
        $gwPrice = $giftingItem->getGwPrice();
        $gwCardPrice = $giftingItem->getGwCardPrice();
        if ($gwPrice || $gwCardPrice) {
            $amount = $this->_helper->calculateGwItemRowTotal($giftingItem);
            $pg = $this->_getPriceGroup($giftingPayload);
            $pg->setAmount($amount)
                ->setUnitPrice($gwPrice + $gwCardPrice);
        }
        return $this;
    }
    /**
     * get the gifting pricegroup; a new price group is created
     * and attached if one doesn't exist.
     * @param  IGifting
     * @return IPriceGroup
     */
    protected function _getPriceGroup(IGifting $giftingPayload)
    {
        $pg = $giftingPayload->getGiftPricing();
        if (!$pg) {
            $pg = $giftingPayload->getEmptyGiftingPriceGroup();
            $giftingPayload->setGiftPricing($pg);
        }
        return $pg;
    }
    /**
     * add the sku for the chosen gift wrapping
     * @param  IGifting
     * @param  Varien_Object
     * @return self
     */
    protected function _addGiftWrapItem(IGifting $giftingPayload, Varien_Object $giftingItem)
    {
        $giftWrapId = $giftingItem->getGwId();
        if ($giftWrapId) {
            $giftwrap = Mage::getModel('enterprise_giftwrapping/wrapping')->load($giftWrapId);
            $giftingPayload
                ->setGiftItemId($giftwrap->getEb2cSku())
                ->setIncludeGiftWrapping(true);
            $this->_getPriceGroup($giftingPayload)
                ->setTaxClass($giftwrap->getEb2cTaxClass());
        }
        return $this;
    }

    /**
     * add the gift sender, recipient and message to payload
     * @param  IGifting
     * @param  Varien_Object
     * @return self
     */
    protected function _addEnvelopeInfo(IGifting $giftingPayload, Varien_Object $giftingItem)
    {
        $messageId = $giftingItem->getGiftMessageId();
        if ($messageId) {
            $message = Mage::getModel('giftmessage/message')->load($messageId);
            if ($giftingItem->getGwAddCard()) {
                $this->_addAsGiftCard($giftingPayload, $message);
            } else {
                $this->_addAsPackSlip($giftingPayload, $message);
            }
        }
        return $this;
    }

    /**
     * add envelope information as a gift card
     * @param IGifting
     * @param Mage_GiftMessage_Model_Message
     */
    protected function _addAsGiftCard(IGifting $giftingPayload, Mage_GiftMessage_Model_Message $message)
    {
        $giftingPayload
            ->setLocalizedToLabel($this->_helper->__('To'))
            ->setLocalizedFromLabel($this->_helper->__('From'))
            ->setGiftCardTo($message->getRecipient())
            ->setGiftCardFrom($message->getSender())
            ->setGiftCardMessage($message->getMessage());
    }

    /**
     * add envelope information as a pack slip
     * @param IGifting
     * @param Mage_GiftMessage_Model_Message
     */
    protected function _addAsPackSlip(IGifting $giftingPayload, Mage_GiftMessage_Model_Message $message)
    {
        $giftingPayload
            ->setLocalizedToLabel($this->_helper->__('To'))
            ->setLocalizedFromLabel($this->_helper->__('From'))
            ->setPackSlipTo($message->getRecipient())
            ->setPackSlipFrom($message->getSender())
            ->setPackSlipMessage($message->getMessage());
    }
}
