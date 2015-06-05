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

class EbayEnterprise_GiftCard_Block_Adminhtml_Form extends EbayEnterprise_GiftCard_Block_Form
{
    /** @var Mage_Core_Helper_Data **/
    protected $_mageCoreHelper;
    /** @var EbayEnterprise_GiftCard_Model_Container */
    protected $_giftCardContainer;

    protected function _construct()
    {
        parent::_construct();
        $this->_mageCoreHelper = Mage::helper('core');
        $this->_giftCardContainer = Mage::getModel('ebayenterprise_giftcard/container');
    }
    /**
     * Get the cards for this cart
     * @return SplObjectStorage
     */
    protected function getGiftCards()
    {
        return $this->_giftCardContainer->getUnredeemedGiftCards();
    }
    /**
     * Get the amount formatted as currency
     * @param  float $amount
     * @return string
     */
    protected function _formatPrice($amount)
    {
        return $this->_mageCoreHelper->formatPrice($amount);
    }
}
