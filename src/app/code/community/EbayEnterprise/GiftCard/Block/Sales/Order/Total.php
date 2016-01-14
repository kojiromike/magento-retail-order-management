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
 * Block for rendering eBay Enterprise gift card totals for email templates.
 */
class EbayEnterprise_GiftCard_Block_Sales_Order_Total extends Mage_Core_Block_Template
{
    const TOTAL_CODE = 'ebayenterprise_giftcard';
    const TOTAL_LABEL = 'EbayEnterprise_GiftCard_Order_Total_Label';

    /** @var EbayEnterprise_GiftCard_Model_Container */
    protected $giftcardContainer;
    /** @var EbayEnterprise_GiftCard_Helper_Data */
    protected $helper;

    public function __construct(array $args = [])
    {
        list(
            $this->giftcardContainer,
            $this->helper
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'giftcard_container', Mage::getModel('ebayenterprise_giftcard/container')),
            $this->nullCoalesce($args, 'helper', Mage::helper('ebayenterprise_giftcard'))
        );
        parent::__construct($args);
    }

    /**
     * Enforce type checks on construct args array.
     *
     * @param EbayEnterprise_GiftCard_Model_Container
     * @param EbayEnterprise_GiftCard_Helper_Data
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_GiftCard_Model_Container $giftcardContainer,
        EbayEnterprise_GiftCard_Helper_Data $helper
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param array
     * @param string
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Get the current order model.
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Register gift card totals with the order totals block.
     *
     * @return self
     */
    public function initTotals()
    {
        $total = new Varien_Object([
            'code' => self::TOTAL_CODE,
            // Using a block name instead of just adding values so a template
            // can be used to render the gift card totals.
            'block_name' => $this->getNameInLayout(),
            'label' => $this->helper->__(self::TOTAL_LABEL),
        ]);
        $this->getParentBlock()->addTotalBefore($total, ['customerbalance', 'grand_total']);
        return $this;
    }

    /**
     * Get all gift cards redeemed for the order.
     *
     * @return SPLObjectStorage
     */
    public function getGiftCards()
    {
        return $this->giftcardContainer->getRedeemedGiftCards();
    }

    /**
     * Get the label to use for a gift card in the totals.
     *
     * @param EbayEnterprise_GiftCard_Model_Giftcard
     * @return string
     */
    public function getCardLabel(EbayEnterprise_GiftCard_Model_Giftcard $card)
    {
        return $this->__(self::TOTAL_LABEL, $card->getCardNumber());
    }

    /**
     * Get the value of the gift card to display in the totals.
     *
     * @param EbayEnterprise_GiftCard_Model_Giftcard
     * @return string
     */
    public function getCardValue(EbayEnterprise_GiftCard_Model_Giftcard $card)
    {
        return $this->getOrder()->formatPrice($card->getAmountRedeemed() * -1);
    }

    /**
     * Attributes to add to the html element wrapping the total's label.
     *
     * @return string
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Attributes to add to the html element wrapping the total's value.
     *
     * @return string
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }
}
