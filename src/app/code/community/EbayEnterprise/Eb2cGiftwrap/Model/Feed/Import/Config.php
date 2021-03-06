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

class EbayEnterprise_Eb2cGiftwrap_Model_Feed_Import_Config implements EbayEnterprise_Catalog_Interface_Import_Config
{
    const IMPORT_CONFIG_PATH = 'eb2cgiftwrap/feed/import_configuration';
    /**
     * @see EbayEnterprise_Catalog_Interface_Import_Config::getImportConfigData
     * @return array of key/pairs
     */
    public function getImportConfigData()
    {
        return Mage::helper('eb2cgiftwrap')
            ->getConfigModel()
            ->getConfigData(self::IMPORT_CONFIG_PATH);
    }
}
