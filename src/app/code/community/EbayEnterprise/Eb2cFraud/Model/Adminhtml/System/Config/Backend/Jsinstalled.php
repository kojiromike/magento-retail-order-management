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
 * A class to help detect if any .js files are installed in the location where
 *  we expect to find 41st Parameter JavaScript Files.
 */
class EbayEnterprise_Eb2cFraud_Model_Adminhtml_System_Config_Backend_Jsinstalled extends Mage_Core_Model_Config_Data
{
    /**
     * Load display variable with 'Yes' or 'No' regarding javascript file presence in eb2cfraud location
     * @return self
     * @codeCoverageIgnore Magento promises to display the value; glob promises to deliver an array of matching files.
     */
    public function _afterLoad()
    {
        $filePattern  = Mage::getBaseDir() . '/js/ebayenterprise_eb2cfraud/*.js';
        $jsFiles = glob($filePattern);
        if (!$jsFiles) {
            $publicDisplay = 'Not installed; fraud information will not be collected';
        } else {
            $publicDisplay = 'Yes, ' . count($jsFiles) . ' collection scripts found';
        }
        $this->setValue($publicDisplay);
        return $this;
    }

    /**
     * Simply turn of data saving. This is a display-only field in admin dependent upon presence of
     *  files in the filesystem, not any configuration data.
     * @return self
     * @codeCoverageIgnore Magento promises to not save anything if _dataSaveAllowed is false.
     */
    public function _beforeSave()
    {
        $this->_dataSaveAllowed = false;
        return $this;
    }
}
