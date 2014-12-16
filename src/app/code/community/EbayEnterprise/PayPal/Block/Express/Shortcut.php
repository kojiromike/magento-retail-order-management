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
 * Paypal expess checkout shortcut link
 *
 * @method string getShortcutHtmlId()
 * @method string getImageUrl()
 * @method string getCheckoutUrl()
 * @method string getConfirmationUrl()
 * @method string getIsInCatalogProduct()
 * @method string getConfirmationMessage()
 */
class EbayEnterprise_PayPal_Block_Express_Shortcut
	extends Mage_Core_Block_Template
{
	/**
	 * Position of "OR" label against shortcut
	 */
	const POSITION_BEFORE = 'before';
	const POSITION_AFTER = 'after';
	/**
	 * Whether the block should be eventually rendered
	 *
	 * @var bool
	 */
	protected $_shouldRender = true;
	protected $_paymentMethodCode = 'ebayenterprise_paypal_express';
	/**
	 * Start express action
	 *
	 * @var string
	 */
	protected $_startAction = 'ebayenterprise_paypal_express/checkout/start/button/1';
	/**
	 * Express checkout model factory name
	 *
	 * @var string
	 */
	protected $_checkoutType = 'ebayenterprise_paypal/express_checkout';

	/**
	 * @return Mage_Core_Block_Abstract
	 */
	protected function _beforeToHtml()
	{
		$result = parent::_beforeToHtml();
		$config = Mage::helper('ebayenterprise_paypal')->getConfigModel();
		$isOnProductPage = $this->getIsOnCatalogProductPage();
		$quote = ($isOnProductPage || '' == $this->getIsQuoteAllowed())
			? null : Mage::getSingleton('checkout/session')->getQuote();

		$context = $isOnProductPage ? 'visible_on_product' : 'visible_on_cart';
		if (!$config->getConfigFlag($context)) {
			$this->_shouldRender = false;
			return $result;
		}
		if ($isOnProductPage) {
			// Show PayPal shortcut on a product view page only if product has nonzero price
			/** @var $currentProduct Mage_Catalog_Model_Product */
			$currentProduct = Mage::registry('current_product');
			if (!is_null($currentProduct)) {
				$productPrice = (float)$currentProduct->getFinalPrice();
				if (empty($productPrice) && !$currentProduct->isGrouped()) {
					$this->_shouldRender = false;
					return $result;
				}
			}
		}
		// validate minimum quote amount and validate quote for zero grandtotal
		if (null !== $quote
			&& (!$quote->validateMinimumAmount()
				|| (!$quote->getGrandTotal() && !$quote->hasNominalItems()))
		) {
			$this->_shouldRender = false;
			return $result;
		}
		// check payment method availability
		$methodInstance = Mage::helper('payment')->getMethodInstance(
			$this->_paymentMethodCode
		);
		if (!$methodInstance || !$methodInstance->isAvailable($quote)) {
			$this->_shouldRender = false;
			return $result;
		}
		// set misc data
		$this->setShortcutHtmlId(
			$this->helper('core')->uniqHash('ebayenterprise_paypal_shortcut_')
		)
			->setCheckoutUrl($this->getUrl($this->_startAction));

		$this->setImageUrl(
			'https://www.paypal.com/' . Mage::app()->getLocale()->getLocaleCode(
			) . '/i/btn/btn_xpressCheckout.gif'
		);
		return $result;
	}

	/**
	 * Check is "OR" label position before shortcut
	 *
	 * @return bool
	 */
	public function isOrPositionBefore()
	{
		return
			($this->getIsOnCatalogProductPage() && !$this->getShowOrPosition())
			|| ($this->getShowOrPosition()
				&& $this->getShowOrPosition() == self::POSITION_BEFORE);

	}

	/**
	 * Check is "OR" label position after shortcut
	 *
	 * @return bool
	 */
	public function isOrPositionAfter()
	{
		return
			(!$this->getIsOnCatalogProductPage() && !$this->getShowOrPosition())
			|| ($this->getShowOrPosition()
				&& $this->getShowOrPosition() == self::POSITION_AFTER);
	}
}
