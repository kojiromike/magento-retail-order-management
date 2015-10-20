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
 * Session model for gift card session data. Provides a namespaced session
 * store for session data used by the EbayEnterprise_GiftCard module.
 */
class EbayEnterprise_GiftCard_Model_Session extends Mage_Core_Model_Session_Abstract
{
    const CURRENT_GIFT_CARD_MEMO_KEY = 'current_gift_card_memo';
    const CONTAINER_STORAGE_KEY = 'gift_card_container_memo_storage';

    /**
     * Session model constructor. Initialize the `ebayenterprise_giftcard`
     * session namespace.
     */
    public function __construct()
    {
        $this->init('ebayenterprise_giftcard');
    }

    /**
     * Set the current gift card being applied or checked.
     *
     * Only stores the gift cards memento state in the session. Getting the
     * data back out will but the data back into a gift card model.
     *
     * @param EbayEnterprise_GiftCard_Model_IGiftcard
     * @return self
     */
    public function setEbayEnterpriseCurrentGiftCard(EbayEnterprise_GiftCard_Model_IGiftcard $card)
    {
        return $this->setData(self::CURRENT_GIFT_CARD_MEMO_KEY, $card->getMemo());
    }

    /**
     * Get the gift card object currently being applied or checked.
     *
     * @return EbayEnterprise_GiftCard_Model_IGiftcard|null
     */
    public function getEbayEnterpriseCurrentGiftCard()
    {
        $memo = $this->getData(self::CURRENT_GIFT_CARD_MEMO_KEY);
        return $memo ? $this->createGiftCardFromMemo($memo) : null;
    }

    /**
     * Get the container storage object, setting the value to the provided
     * default if no container storage object exists yet.
     *
     * @param SplObjectStorage
     * @return SplObjectStorage
     */
    public function getContainerStorageSetDefault(SplObjectStorage $default)
    {
        return $this->getDataSetDefault(self::CONTAINER_STORAGE_KEY, $default);
    }

    /**
     * Create a new gift card model, injecting it with a gift card memo object.
     *
     * @param EbayEnterprise_GiftCard_Model_Giftcard_Memo
     * @return EbayEnterprise_GiftCard_Model_Giftcard
     */
    protected function createGiftCardFromMemo(EbayEnterprise_GiftCard_Model_Giftcard_Memo $memo)
    {
        return Mage::getModel('ebayenterprise_giftcard/giftcard')->restoreFromMemo($memo);
    }
}
