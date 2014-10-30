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

class EbayEnterprise_CreditCard_Helper_Data
	extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('ebayenterprise_creditcard/config'));
	}
	/**
	 * Get the ROM Tender Type for the Magento CC Type.
	 * @param  string $creditCardType
	 * @return string
	 */
	public function getTenderTypeForCcType($creditCardType)
	{
		$configKey = 'tenderType' . ucwords(strtolower($creditCardType));
		return $this->getConfigModel()->$configKey;
	}
	/**
	 * Scrub the auth request XML message of any sensitive data - CVV, CC number.
	 * @param  string $xml
	 * @return string
	 */
	public function cleanAuthXml($xml)
	{
		$xml = preg_replace('#(\<(?:Encrypted)?CardSecurityCode\>).*(\</(?:Encrypted)?CardSecurityCode\>)#', '$1***$2', $xml);
		$xml = preg_replace('#(\<(?:Encrypted)?PaymentAccountUniqueId.*?\>).*(\</(?:Encrypted)?PaymentAccountUniqueId\>)#', '$1***$2', $xml);
		return $xml;
	}
}
