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

class EbayEnterprise_Order_Overrides_Block_Order_Recent extends EbayEnterprise_Order_Overrides_Block_Order_Abstract
{
    // carried over from the parent class where it is a hard-coded value
    const ORDERS_TO_SHOW = 5;

    /**
     * Returns ROM order summary data in a collection.
     *
     * @return EbayEnterprise_Order_Model_Search_Process_Response_ICollection
     */
    public function getOrders()
    {
        $orders = Mage::registry('rom_order_collection');
        if (!$orders) {
            Mage::unregister('rom_order_collection');
            $orders = $this->_orderHelper->getCurCustomerOrders();
            Mage::register('rom_order_collection', $orders);
        }
        return $orders;
    }

    /**
     * Return the default number of orders to show
     *
     * @return int number of orders
     */
    public function getMaxOrdersToShow()
    {
        return static::ORDERS_TO_SHOW;
    }

    /**
     * Returns URL for view a specific order id.
     * @param  string
     * @return string
     */
    public function getViewUrl($orderId)
    {
        return $this->getUrl('sales/order/romview', ['order_id' => $orderId]);
    }

    /**
     * Returns URL to cancel an order.
     *
     * @param  string
     * @return string
     */
    public function getCancelUrl($orderId)
    {
        return $this->getUrl('sales/order/romcancel', ['order_id' => $orderId]);
    }

    /**
     * Returns URL to view an order tracking.
     *
     * @param  Varien_Object
     * @return string
     */
    public function getTrackUrl(Varien_Object $order)
    {
        return $this->getUrl('sales/order/track', ['order_id' => $order->getCustomerOrderId()]);
    }

    /**
     * @see Mage_Core_Block_Template::_toHtml()
     */
    protected function _toHtml()
    {
        if ($this->getOrders()->getSize() > 0) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Returns URL to reorder all the order items.
     *
     * @param  Varien_Object
     * @return string
     */
    public function getReorderUrl(Varien_Object $order)
    {
        return $this->getUrl('sales/order/reorder', ['order_id' => $order->getCustomerOrderId()]);
    }
}
