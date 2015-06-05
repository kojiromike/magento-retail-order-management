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

class EbayEnterprise_Tax_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    /**
     * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
     * @param mixed
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_tax/config'));
    }

    /**
     * Get the HTS code for a product in a given country.
     *
     * @param Mage_Catalog_Model_Product
     * @param string $countryCode The two letter code for a country (US, CA, DE, etc...)
     * @return string|null The HTS Code for the product/country combination. Null if no HTS code is available.
     */
    public function getProductHtsCodeByCountry(Mage_Catalog_Model_Product $product, $countryCode)
    {
        $htsCodes = unserialize($product->getHtsCodes());
        if (is_array($htsCodes)) {
            foreach ($htsCodes as $htsCode) {
                if ($countryCode === $htsCode['destination_country']) {
                    return $htsCode['hts_code'];
                }
            }
        }

        return null;
    }
}
