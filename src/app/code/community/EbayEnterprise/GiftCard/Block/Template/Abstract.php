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
 * Base class for Gift Card template blocks. Will disable block output when
 * gift cards are not enabled
 */
class EbayEnterprise_GiftCard_Block_Template_Abstract extends Mage_Core_Block_Template
{
    /**
     * If gift cards are not enabled, do not output any block output.
     * @return string
     */
    protected function _toHtml()
    {
        if (Mage::helper('ebayenterprise_giftcard')->getConfigModel()->isEnabledFlag) {
            return parent::_toHtml();
        }
        return '';
    }
}
