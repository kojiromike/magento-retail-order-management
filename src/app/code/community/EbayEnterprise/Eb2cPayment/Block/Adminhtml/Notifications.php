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

class EbayEnterprise_Eb2cPayment_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
	const PAYMENT_NEED_CONFIGURATION_EB2C_TITLE = 'EbayEnterprise_Eb2cPayment_Admin_Dasboard_Payment_Config_Eb2c_Title';
	const PAYMENT_NEED_CONFIGURATION_NONE_EB2C_TITLE = 'EbayEnterprise_Eb2cPayment_Admin_Dasboard_Payment_Config_None_Eb2c_Title';

	/**
	 * Get array of payment methods that need to be configured
	 * @return array
	 */
	public function getEbcPaymentNotice()
	{
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			if (!Mage::getModel('eb2cpayment/suppression')->isEbcPaymentConfigured()) {
				return array(Mage::helper('eb2cpayment')->__(self::PAYMENT_NEED_CONFIGURATION_EB2C_TITLE));
			} else {
				if (Mage::getModel('eb2cpayment/suppression')->isAnyNonEb2CPaymentMethodEnabled()) {
					return array(Mage::helper('eb2cpayment')->__(self::PAYMENT_NEED_CONFIGURATION_NONE_EB2C_TITLE));
				}
			}
		}
		return array();
	}

	/**
	 * Get payment configuration section url
	 * @return string
	 */
	public function getPaymentSectionConfigurationUrl()
	{
		return $this->getUrl('adminhtml/system_config/edit', array('section' => 'payment'));
	}

	/**
	 * ACL validation before html generation
	 * @return string
	 */
	protected function _toHtml()
	{
		if (Mage::getSingleton('admin/session')->isAllowed('adminhtml/system_config')) {
			return parent::_toHtml();
		}
		return '';
	}
}
