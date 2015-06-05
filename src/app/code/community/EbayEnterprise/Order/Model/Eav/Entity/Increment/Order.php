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

class EbayEnterprise_Order_Model_Eav_Entity_Increment_Order extends Mage_Eav_Model_Entity_Increment_Abstract
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var EbayEnterprise_Order_Helper_Data */
    protected $_orderHelper;
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $_factory;

    public function __construct(array $initParams = [])
    {
        list($this->_coreHelper, $this->_orderHelper, $this->_factory) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
            $this->_nullCoalesce($initParams, 'order_helper', Mage::helper('ebayenterprise_order')),
            $this->_nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory'))
        );
        parent::__construct($this->_removeKnownKeys($initParams));
    }

    /**
     * Remove the all the require and optional keys from the $initParams
     * parameter.
     *
     * @param  array
     * @return array
     */
    protected function _removeKnownKeys(array $initParams)
    {
        foreach (['core_helper', 'order_helper', 'factory'] as $key) {
            if (isset($initParams[$key])) {
                unset($initParams[$key]);
            }
        }
        return $initParams;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @param  EbayEnterprise_Order_Helper_Data
     * @param  EbayEnterprise_Order_Helper_Factory
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_Order_Helper_Data $orderHelper,
        EbayEnterprise_Order_Helper_Factory $factory
    ) {
        return [$coreHelper, $orderHelper, $factory];
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Get the store id for the order. In non-admin stores, can use the current
     * store. In admin stores, must get the order the quote is actually
     * being created in.
     * @return int
     */
    protected function _getStoreId()
    {
        $storeEnv = Mage::app()->getStore();
        if ($storeEnv->isAdmin()) {
            /** @var Mage_Adminhtml_Model_Session_Quote */
            $quoteSession = $this->_factory->getAdminQuoteSessionModel();
            // when in the admin, the store id the order is actually being created
            // for should be used instead of the admin store id - should be
            // available in the session
            $storeEnv = $quoteSession->getStore();
        }
        return $storeEnv->getId();
    }
    /**
     * Get the next increment id by incrementing the last id
     * @return string
     */
    public function getNextId()
    {
        // remove any order prefixes from the last increment id
        $last = $this->_orderHelper->removeOrderIncrementPrefix($this->getLastId());
        // Using bcmath to avoid float/integer overflow.
        return $this->format(bcadd($last, 1));
    }
    /**
     * Prefix the order with the Client Order Id Prefix configured for the
     * current scope.
     * @return string
     */
    public function getPrefix()
    {
        return $this->_coreHelper->getConfigModel($this->_getStoreId())->clientOrderIdPrefix;
    }
}
