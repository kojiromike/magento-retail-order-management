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
 * Overridden system configuration tabs block
 */
class EbayEnterprise_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Tabs extends Mage_Adminhtml_Block_System_Config_Tabs
{
    /**
     * determine if the config section should have its tab displayed.
     * @param  string   $code
     * @return boolean  true if the tab for the config section should be displayed; false otherwise
     */
    public function checkSectionPermissions($code=null)
    {
        $suppression = Mage::getModel('eb2cpayment/suppression');
        return !$suppression->isConfigSuppressed((string) $code) and
            parent::checkSectionPermissions($code);
    }

}
