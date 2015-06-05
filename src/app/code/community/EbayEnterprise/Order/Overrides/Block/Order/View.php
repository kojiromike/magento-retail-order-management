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

class EbayEnterprise_Order_Overrides_Block_Order_View extends Mage_Sales_Block_Order_View
{
    const HELPER_CLASS = 'ebayenterprise_order';
    const OVERRIDDEN_TEMPLATE = 'ebayenterprise_order/order/view.phtml';

    /** @var EbayEnterprise_Order_Helper_Data */
    protected $_orderHelper;

    protected function _construct()
    {
        // We have to have a constructor to preserve our template, because the parent constructor sets it.
        parent::_construct();
        $this->setTemplate(self::OVERRIDDEN_TEMPLATE);
        $this->_orderHelper = Mage::helper('ebayenterprise_order');
    }

    /**
     * Retrieve current rom_order model instance
     *
     * @return EbayEnterprise_Order_Model_Detail_Process_IResponse
     */
    public function getOrder()
    {
        return Mage::registry('rom_order');
    }

    /**
     * @see Mage_Core_Block_Abstract::getHelper()
     * Returns a helper instance.
     *
     * @return EbayEnterprise_Order_Helper_Data
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getHelper($type)
    {
        return $this->_orderHelper;
    }

    /**
     * Return the self::HELPER_CLASS class constant.
     *
     * @return string
     */
    public function getHelperClass()
    {
        return static::HELPER_CLASS;
    }
}
