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

class EbayEnterprise_Order_Model_Detail_Process_Response_Shipgroup extends Varien_Object
{
    /** @var EbayEnterprise_Order_Model_Detail_Process_IResponse */
    protected $_order;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var Varien_Data_Collection */
    protected $_items;
    /** @var float */
    protected $_subTotal;
    /** @var float */
    protected $_shippingAmount;
    /** @var float */
    protected $_discountAmount;
    /** @var float */
    protected $_taxAmount;

    /**
     * @param array $initParams Must have this key:
     *                          - 'order' => EbayEnterprise_Order_Model_Detail_Process_IResponse
     */
    public function __construct(array $initParams)
    {
        list($this->_order, $this->_coreHelper) = $this->_checkTypes(
            $initParams['order'],
            $this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore'))
        );
        parent::__construct($this->_removeKnownKeys($initParams));
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Order_Model_Detail_Process_IResponse
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Order_Model_Detail_Process_IResponse $order,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
    ) {
        return [$order, $coreHelper];
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
     * Remove the all the require and optional keys from the $initParams
     * parameter.
     *
     * @param  array
     * @return array
     */
    protected function _removeKnownKeys(array $initParams)
    {
        foreach (['order', 'core_helper'] as $key) {
            if (isset($initParams[$key])) {
                unset($initParams[$key]);
            }
        }
        return $initParams;
    }

    /**
     * Get the shipping method by the ref id
     *
     * @return EbayEnterprise_Order_Model_Detail_Process_Response_Address | null
     */
    public function getShippingAddress()
    {
        return $this->_order->getAddressesCollection()->getItemById($this->getRefId());
    }

    /**
     * Get the shipping method.
     *
     * @return string | null
     */
    public function getShippingDescription()
    {
        foreach ($this->getItemsCollection() as $item) {
            /** @var string */
            $description = $item->getShippingDescription();
            if ($description) {
                return $description;
            }
        }
        return null;
    }

    /**
     * @param bool
     * @return Varien_Data_Collection
     */
    public function getItemsCollection($includeHidden = false)
    {
        if (!$this->_items || !isset($this->_items[$includeHidden])) {
            $this->_items[$includeHidden] = $this->_buildItemsCollection($includeHidden);
        }
        return $this->_items[$includeHidden];
    }

    /**
     * Build a collection of order item from this particular ship group
     *
     * @param bool
     * @return Varien_Data_Collection
     */
    protected function _buildItemsCollection($includeHidden)
    {
        $items = $this->_coreHelper->getNewVarienDataCollection();
        foreach ($this->getOrderItems() as $itemId) {
            $item = $this->_order->getItemsCollection()->getItemByColumnValue('ref_id', $itemId);
            if ($item && ($includeHidden || !$item->getIsHiddenGift())) {
                $items->addItem($item);
            }
        }
        return $items;
    }

    /**
     * Calculating this ship group items sub totals.
     *
     * @return Varien_Data_Collection
     */
    public function getSubtotal()
    {
        if (!is_numeric($this->_subTotal)) {
            $this->_subTotal = 0;
            foreach ($this->getItemsCollection() as $item) {
                $this->_subTotal += (float) $item->getRowTotal();
            }
        }
        return $this->_subTotal;
    }

    /**
     * Calculating this ship group items shipping totals.
     *
     * @return Varien_Data_Collection
     */
    public function getShippingAmount()
    {
        if (!is_numeric($this->_shippingAmount)) {
            $this->_shippingAmount = 0;
            foreach ($this->getItemsCollection() as $item) {
                $this->_shippingAmount += (float) $item->getShippingAmount();
            }
        }
        return $this->_shippingAmount;
    }

    /**
     * Calculating this ship group items discount totals.
     *
     * @return Varien_Data_Collection
     */
    public function getDiscountAmount()
    {
        if (!is_numeric($this->_discountAmount)) {
            $this->_discountAmount = 0;
            foreach ($this->getItemsCollection() as $item) {
                $this->_discountAmount += (float) $item->getDiscountAmount();
            }
        }
        return $this->_discountAmount;
    }

    /**
     * Calculating this ship group items tax totals.
     *
     * @return Varien_Data_Collection
     */
    public function getTaxAmount()
    {
        if (!is_numeric($this->_taxAmount)) {
            $this->_taxAmount = 0;
            foreach ($this->getItemsCollection() as $item) {
                $this->_taxAmount += (float) $item->getTaxAmount();
            }
        }
        return $this->_taxAmount;
    }

    /**
     * Calculate the grand order total for this ship group.
     *
     * @return float
     */
    public function getOrderTotal()
    {
        return ($this->getSubtotal() + $this->getShippingAmount() + $this->getTaxAmount()) - $this->getDiscountAmount();
    }
}
