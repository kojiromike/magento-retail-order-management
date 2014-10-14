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

class EbayEnterprise_GiftCard_Model_Total_Quote extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
	// to be translated
	const GIFT_CARD_SYSTEM_MESSAGE = 'EbayEnterprise_GiftCard_%s';
	/**
	 * The code is used to determine the block renderer for the address line
	 * @see Mage_Checkout_Block_Cart_Totals::_getTotalRenderer
	 */
	protected $_code = 'ebayenterprise_giftcard';
	/** @var EbayEnterprise_GiftCard_Helper_Data */
	protected $_giftCardHelper;
	/** @var EbayEnterprise_GiftCard_Model_IContainer */
	protected $_giftCardContainer;

	/**
	 * Set up the helper and gift card container
	 * @param array
	 */
	public function __construct(array $initParams=array())
	{
		list($this->_giftCardHelper, $this->_giftCardContainer) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'gift_card_helper', Mage::helper('ebayenterprise_giftcard')),
			$this->_nullCoalesce($initParams, 'gift_card_container', Mage::getModel('ebayenterprise_giftcard/container'))
		);
	}
	/**
	 * Just for type hinting
	 *
	 * @param EbayEnterprise_GiftCard_Helper_Data
	 * @param EbayEnterprise_GiftCard_Model_IContainer
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_GiftCard_Helper_Data $helper,
		EbayEnterprise_GiftCard_Model_IContainer $container
	) {
		return array($helper, $container);
	}
	/**
	 * return the $field element of the array if it exists;
	 * otherwise return $default
	 * @param  array  $arr
	 * @param  string $field
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr=array(), $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Show how unredeemed gift cards will apply to the cart
	 * for display purposes.
	 *
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return self
	 */
	public function fetch(Mage_Sales_Model_Quote_Address $address)
	{
		/**
		 * Pass a copy of the unredeemed cards SPLObjectStorage to the total
		 * with applied amounts supplied as values to each object.
		 */
		$address->addTotal(array(
			'code' => $this->getCode(),
			'title' => $this->_giftCardHelper->__(static::GIFT_CARD_SYSTEM_MESSAGE),
			'value' => $address->getEbayEnterpriseGiftCardBaseAppliedAmount(),
			'cards' => $this->_giftCardContainer->getUnredeemedGiftCards(),
		));
		return $this;
	}

	/**
	 * Collect gift card totals for the specified address
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return self
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		// Cards are not specific to a single address so cards may have already been
		// partially applied to other address amounts.
		$cards = $this->_giftCardContainer->getUnredeemedGiftCards();
		$grandTotal = $address->getGrandTotal();
		$appliedAmount = 0.00;
		foreach ($cards as $card) {
			// Amount of this address total to apply to the gift card
			$amountToApply = min($this->_getGiftCardAvailableAmount($card), $grandTotal - $appliedAmount);
			// Update the amount expected to be redeemed from the gift card. Must add to any
			// existing amount exepcted to be redeemed to not clear out amounts
			// set while collecting other addresses.
			$card->setAmountToRedeem($card->getAmountToRedeem() + $amountToApply);
			// Accumulate amounts being redeemed for this address.
			$appliedAmount += $amountToApply;
		}
		// Only support one currency right now.
		$address
			->setEbayEnterpriseGiftCardBaseAppliedAmount($appliedAmount)
			->setEbayEnterpriseGiftCardAppliedAmount($appliedAmount)
			->setBaseGrandTotal($grandTotal - $appliedAmount)
			->setGrandTotal($grandTotal - $appliedAmount);
		return $this;
	}
	/**
	 * Get the amount available to redeem on a gift card - balance less any
	 * amount already slated to be redeemed
	 * @param  EbayEnterprise_GiftCard_Model_IGiftcard $card
	 * @return float
	 */
	protected function _getGiftCardAvailableAmount(EbayEnterprise_GiftCard_Model_IGiftcard $card)
	{
		return max(0.0, $card->getBalanceAmount() - $card->getAmountToRedeem());
	}
}
