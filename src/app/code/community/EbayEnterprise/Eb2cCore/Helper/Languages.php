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

class EbayEnterprise_Eb2cCore_Helper_Languages extends Mage_Core_Helper_Abstract
{
	/**
	 * Return an array of stores and attach a language code to them, Varien_Object style
	 * @param string langCode (optional) if passed, only stores using that langCode are returned.
	 * @return array of Mage_Core_Model_Store, each element of which has new magic getter 'getLanguageCode()'
	 */
	public function getStores($langCode=null)
	{
		$stores = array();
		$config = Mage::helper('eb2ccore')->getConfigModel();

		foreach (Mage::app()->getWebsites() as $website) {
			foreach ($website->getGroups() as $group) {
				foreach ($group->getStores() as $store) {
					$config->setStore($store->getStoreId());
					if (!$langCode || ($langCode && $langCode === $config->languageCode)) {
						$store->setLanguageCode($config->languageCode);
						$stores[] = $store;
					}
				}
			}
		}
		return $stores;
	}
	/**
	 * Get a simple array of all language codes used in this installation
	 * @return array
	 */
	public function getLanguageCodesList()
	{
		$languages = array();
		foreach ($this->getStores() as $store) {
			$languages[] = $store->getLanguageCode();
		}
		return array_unique($languages);
	}
}
