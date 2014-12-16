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
class EbayEnterprise_PayPal_Block_Logo extends Mage_Core_Block_Template
{
	/** @var bool Whether the block should be eventually rendered */
	protected $_shouldRender = true;

	/**
	 * Get the url for the PayPal "about" page
	 * @return string
	 */
	public function getAboutPaypalPageUrl()
	{
		$local = Mage::app()->getLocale();
		return sprintf(
			'https://www.paypal.com/%s/cgi-bin/webscr?cmd=xpt/Marketing/popup/OLCWhatIsPayPal-outside',
			$locale->getLocaleCode()
		);
	}

	/**
	 * override to ensure we set the logo type based on what's in
	 * the layout update xml
	 * @return string
	 */
	protected function _toHtml()
	{
		$config = Mage::helper('ebayenterprise_paypal')->getConfigModel();
		$localeCode = Mage::app()->getLocale()->getLocaleCode();
		$type = $this->getLogoType() ?: $config->logoType; // can be assigned in layout
		$logoUrl = sprintf(
			'https://www.paypalobjects.com/%s/i/bnr/bnr_%s.gif',
			$localeCode,
			$type
		);
		$this->setLogoImageUrl($logoUrl);
	}
}
