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

class EbayEnterprise_Multishipping_Helper_Dispatcher_Onepage implements EbayEnterprise_Multishipping_Helper_Dispatcher_Interface
{
    /**
     * Dispatch events for before the order has been submitted.
     *
     * @param Mage_Sales_Model_Quote
     * @param Mage_Sales_Model_Order
     * @return self
     */
    public function dispatchBeforeOrderSubmit(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
    {
        Mage::dispatchEvent('checkout_type_onepage_save_order', ['order' => $order, 'quote' => $quote]);
        Mage::dispatchEvent('sales_model_service_quote_submit_before', ['order' => $order, 'quote' => $quote]);
        return $this;
    }

    /**
     * Dispatch events for immediately after an order has been submitted.
     *
     * @param Mage_Sales_Model_Quote
     * @param Mage_Sales_Model_Order
     * @return self
     */
    public function dispatchOrderSubmitSuccess(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
    {
        Mage::dispatchEvent('sales_model_service_quote_submit_success', ['order' => $order, 'quote' => $quote]);
        return $this;
    }

    /**
     * Dispatch events for when the order fails to be submitted.
     *
     * @param Mage_Sales_Model_Quote
     * @param Mage_Sales_Model_Order
     * @return self
     */
    public function dispatchOrderSubmitFailure(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
    {
        Mage::dispatchEvent('sales_model_service_quote_submit_failure', ['order' => $order, 'quote' => $quote]);
        return $this;
    }

    /**
     * Dispatch events for when the order has been completely submitted
     * successfully.
     *
     * @param Mage_Sales_Model_Quote
     * @param Mage_Sales_Model_Order
     * @return self
     */
    public function dispatchAfterOrderSubmit(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
    {
        Mage::dispatchEvent('sales_model_service_quote_submit_after', ['order' => $order, 'quote' => $quote]);
        return $this;
    }
}
