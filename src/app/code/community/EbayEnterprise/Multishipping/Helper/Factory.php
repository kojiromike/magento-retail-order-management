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

class EbayEnterprise_Multishipping_Helper_Factory
{
    /**
     * Create a new sales quote service.
     *
     * @param Mage_Sales_Model_Quote
     * @param bool
     * @return Mage_Sales_Model_Service_Quote
     */
    public function createQuoteService(Mage_Sales_Model_Quote $quote, $isMultishipping = false)
    {
        $serviceQuote = Mage::getModel('sales/service_quote', $quote);
        if ($isMultishipping) {
            $serviceQuote->setCheckoutDispatcher(Mage::helper('ebayenterprise_multishipping/dispatcher_multishipping'));
        }
        return $serviceQuote;
    }

    /**
     * Create a transaction that can be used to save the order and related
     * models while placing a new order.
     *
     * @param Mage_Sales_Model_Quote
     * @param Mage_Sales_Model_Order
     * @param Mage_Customer_Model_Customer|null
     * @return Mage_Core_Model_Resource_Transaction
     */
    public function createOrderSaveTransaction(
        Mage_Sales_Model_Quote $quote,
        Mage_Sales_Model_Order $order,
        Mage_Customer_Model_Customer $customer = null
    ) {
        $transaction = Mage::getModel('core/resource_transaction');
        if ($customer) {
            $transaction->addObject($customer);
        }
        $transaction->addObject($quote)
            ->addObject($order)
            ->addCommitCallback(array($order, 'place'))
            ->addCommitCallback(array($order, 'save'));
        return $transaction;
    }

    /**
     * Load the order address model the item is associated with.
     *
     * Currently, this will load a new order address instance each time it is
     * called. In most cases, items retrieved from an order or order address
     * will already have an order address instance associated with it. Use of
     * this method should really only be necessary in instances where an order
     * item has been created either independently of the order or order address
     * or has otherwise become detached from the order address.
     *
     * @param Mage_Sales_Model_Order_Item
     * @return Mage_Sales_Model_Order_Address|null
     */
    public function loadAddressForItem(Mage_Sales_Model_Order_Item $item)
    {
        $addressId = $item->getOrderAddressId();
        if ($addressId) {
            return Mage::getModel('sales/order_address')->load($addressId);
        }
        return $this->_loadDefaultAddressForItem($item);
    }

    /**
     * Get a default address for an item if one exists. For virtual items,
     * the default address will be the billing address of the order the item
     * belongs to. For non-virtual items, the default address will be the
     * primary shipping address of the order the item belongs to. If the
     * appropriate default address for the item does not exits, no address
     * will be returned.
     *
     * @param Mage_Sales_Model_Order_Item
     * @return Mage_Sales_Model_Order_Address|null
     */
    protected function _loadDefaultAddressForItem(Mage_Sales_Model_Order_Item $item)
    {
        $order = $item->getOrder();
        if (!$order) {
            return null;
        }
        $defaultAddress = $item->getIsVirtual()
            ? $order->getBillingAddress()
            : $order->getShippingAddress();
        // If the appropriate default address did not exist in the order, false
        // will have been returned. In such cases, return null to indicate that
        // no such address exists.
        return $defaultAddress ?: null;
    }

    /**
     * Get an order item collection for all items that belong to a given
     * order address.
     *
     * @param Mage_Sales_Model_Order_Address
     * @return Mage_Sales_Model_Resource_Order_Item_Collection
     */
    public function createItemCollectionForAddress(Mage_Sales_Model_Order_Address $address)
    {
        $itemFilters = $address->isPrimaryShippingAddress() || $this->_isAddressBillingAddress($address)
            ? $this->_getPrimaryAddressFilters($address)
            : $this->_getSecondaryAddressFilters($address);

        $items = Mage::getResourceModel('sales/order_item_collection');
        foreach ($itemFilters as $field => $conditions) {
            $items->addFieldToFilter($field, $conditions);
        }
        // Add a link to the address object (this) to each item as well as
        // ensure all items have the correct order address id (mainly for
        // items that may not have an order address id when this is the
        // primary shipping address)
        $items->setDataToAll([
            'order_address' => $address,
            'order_address_id' => $address->getId(),
        ]);
        return $items;
    }

    /**
     * Get item collection filters for any type of primary address - the primary
     * shipping address or the billing address.
     *
     * @param Mage_Sales_Model_Order_Address
     * @return array
     */
    protected function _getPrimaryAddressFilters(Mage_Sales_Model_Order_Address $address)
    {
        return array_merge(
            $this->_getDefaultItemFilters($address),
            ['order_address_id' => [['eq' => $address->getId()], ['null' => true]]]
        );
    }

    /**
     * Get item collection filters for any secondary shipping address.
     *
     * @param Mage_Sales_Model_Order_Address
     * @return array
     */
    protected function _getSecondaryAddressFilters(Mage_Sales_Model_Order_Address $address)
    {
        return array_merge(
            $this->_getDefaultItemFilters($address),
            ['order_address_id' => $address->getId()]
        );
    }

    /**
     * Get item collection filters common for all address types.
     *
     * @param Mage_Sales_Model_Order_Address
     * @return array
     */
    protected function _getDefaultItemFilters(Mage_Sales_Model_Order_Address $address)
    {
        return [
            'order_id' => $address->getParentId(),
            'is_virtual' => $this->_isAddressBillingAddress($address)
        ];
    }

    /**
     * Check if the address is a billing address.
     *
     * @param Mage_Sales_Model_Order_Address
     * @return bool
     */
    protected function _isAddressBillingAddress(Mage_Sales_Model_Order_Address $address)
    {
        return $address->getAddressType() === Mage_Sales_Model_Order_Address::TYPE_BILLING;
    }
}
