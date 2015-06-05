<?php
/**
 * Copyright (c) 2013-2015 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Multishipping_Override_Model_Sales_Order_Item extends Mage_Sales_Model_Order_Item
{
    /** @var EbayEnterprise_Multishipping_Helper_Factory */
    protected $_multishippingFactory;

    protected function _construct()
    {
        parent::_construct();
        list(
            $this->_multishippingFactory
        ) = $this->_checkTypes(
            $this->getData('multishipping_factory') ?: Mage::helper('ebayenterprise_multishipping/factory')
        );
    }

    /**
     * Enforce type checks on construct args array.
     *
     * @param EbayEnterprise_Multishipping_Helper_Factory
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Multishipping_Helper_Factory $multishippingFactory
    ) {
        return func_get_args();
    }

    /**
     * Ensure id of the order address the item is associated with is saved.
     *
     * @return self
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $orderAddress = $this->getOrderAddress();
        if ($orderAddress) {
            $this->setOrderAddressId($orderAddress->getId());
        }
        return $this;
    }

    /**
     * Get the order address. If only an order address id is available, will
     * attempt to load the order address.
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getOrderAddress()
    {
        if (!$this->hasOrderAddress()) {
            $this->setOrderAddress($this->_multishippingFactory->loadAddressForItem($this));
        }
        return $this->getData('order_address');
    }
}
