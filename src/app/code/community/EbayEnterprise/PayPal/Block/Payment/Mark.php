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
 * PayPal Standard payment "form"
 */
class EbayEnterprise_PayPal_Block_Payment_Mark extends Mage_Core_Block_Template
{
	const PAYMENT_MARK_37x23   = '37x23';
	const PAYMENT_MARK_50x34   = '50x34';
	const PAYMENT_MARK_60x38   = '60x38';
	const PAYMENT_MARK_180x113 = '180x113';
	/**
	 * Config model instance
	 * @var EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	protected $_config;
	/**
	 * Set template and redirect message
	 */
	protected function _construct()
	{
		$this->_config = Mage::helper('ebayenterprise_paypal')->getConfigModel();
		$locale = Mage::app()->getLocale();
		$this->setTemplate('ebayenterprise_paypal/payment/mark.phtml')
			->setPaymentAcceptanceMarkHref($this->getPaymentMarkWhatIsPaypalUrl($locale))
			->setPaymentAcceptanceMarkSrc($this->getPaymentMarkImageUrl($locale->getLocaleCode()));
		return parent::_construct();
	}

	/**
	 * Get PayPal "mark" image URL
	 * Supposed to be used on payment methods selection
	 * $staticSize is applicable for static images only
	 *
	 * @param string $localeCode
	 * @param float $orderTotal
	 * @param string $pal
	 * @param string $staticSize
	 */
	public function getPaymentMarkImageUrl($localeCode, $orderTotal = null, $pal = null, $staticSize = null)
	{
		if (null === $staticSize) {
			$staticSize = $this->_config->paymentMarkSize;
		}
		switch ($staticSize) {
			case self::PAYMENT_MARK_37x23:
			case self::PAYMENT_MARK_50x34:
			case self::PAYMENT_MARK_60x38:
			case self::PAYMENT_MARK_180x113:
				break;
			default:
				$staticSize = self::PAYMENT_MARK_37x23;
		}
		return sprintf('https://www.paypal.com/%s/i/logo/PayPal_mark_%s.gif', $localeCode, $staticSize);
	}
	public function getAcceptanceMarkMessage()
	{
		Mage::helper('ebayenterprise_paypal')->__('Acceptance Mark');
	}
}
