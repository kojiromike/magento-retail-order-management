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
 * @copyright    Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license      http://opensource.org/licenses/osl-3.0.php    Open Software License (OSL 3.0)
 */

class EbayEnterprise_GiftCard_Block_Checkout_Cart_Total extends Mage_Checkout_Block_Total_Default
{
	const REMOVAL_URL_BASE = 'ebayenterprise_giftcard/cart/remove';
	protected $_template = 'ebayenterprise_giftcard/checkout/cart/total.phtml';
	/** @var Mage_Checkout_Helper_Data */
	protected $_checkoutHelper;

	protected function _construct()
	{
		parent::_construct();
		$this->_checkoutHelper = Mage::helper('checkout');
	}

	/**
	 * Get the cards for this cart
	 *
	 * @return array[] triples of card number, applied amount and removal url
	 */
	protected function _getCardTotals()
	{
		$totalObj = $this->getTotal();
		/** @var SPLObjectStorage $cards */
		$cards = $totalObj->getCards();
		$cardTotals = array();
		/** @var EbayEnterprise_GiftCard_Model_IGiftcard $card */
		foreach ($cards as $card) {
			/**
			 * @see EbayEnterprise_GiftCard_Model_Total_Quote::collect
			 * @var float
			 */
			$appliedAmount = $card->getAmountToRedeem();
			if ($appliedAmount) {
				$cardNumber = $this->_getCardNumber($card);
				$removalUrl = $this->_getRemovalUrl($cardNumber);
				$cardTotals[] = array($cardNumber, -$appliedAmount, $removalUrl);
			}
		}
		return $cardTotals;
	}

	/**
	 * @note the interface for totals objects is somewhat broken.
	 * Claims to be a Mage_Sales_Model_Quote_Total, but that class doesn't exist.
	 * Anyway, it's a Varien_Object with at least a 'value' float
	 * presumably a 'style' inline style string and an 'area'.
	 * This method exists just to provide documentation.
	 * @see Mage_Sales_Model_Quote_Address::addTotal
	 * @see EbayEnterprise_GiftCard_Model_Total_Quote::fetch
	 * @return Varien_Object
	 */
	public function getTotal()
	{
		return $this->getData('total');
	}

	/**
	 * Get the url for the remove action
	 *
	 * @return string
	 */
	protected function _getRemoveImageUrl()
	{
		return $this->getSkinUrl('images/btn_remove.gif');
	}

	/**
	 * If the current area is the rendering area.
	 *
	 * @return bool
	 */
	protected function _isRenderingArea()
	{
		return $this->getRenderingArea() === $this->getTotal()->getArea();
	}

	/**
	 * Localize and format the price
	 *
	 * @param float
	 * @return string
	 */
	protected function _formatPrice($amount)
	{
		return $this->_checkoutHelper->formatPrice($amount);
	}

	/**
	 * Return the url for removing this particular card from the cart.
	 * @param string
	 * @return string
	 */
	protected function _getRemovalUrl($cardNumber)
	{
		return Mage::getUrl(self::REMOVAL_URL_BASE, array('ebay_enterprise_giftcard_code' => $cardNumber));
	}

	/**
	 * Use the most appropriate card number.
	 * @param EbayEnterprise_GiftCard_Model_IGiftCard
	 * @return string
	 */
	protected function _getCardNumber(EbayEnterprise_GiftCard_Model_IGiftCard $card)
	{
		return $card->getCardNumber();
	}
}
