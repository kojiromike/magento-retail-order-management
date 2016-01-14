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

class EbayEnterprise_Multishipping_Model_Observer
{
    /**
     * Ensure shipment amounts have been collected for the order before it
     * is saved.
     *
     * @param Varien_Event_Observer
     * @return self
     */
    public function handleSalesOrderSaveBefore(Varien_Event_Observer $observer)
    {
        $observer->getEvent()->getOrder()->collectShipmentAmounts();
        return $this;
    }

    /**
     * Collect discount amounts from quote addresses and apply them to the
     * newly created order.
     *
     * @param Varien_Event_Observer
     * @return self
     */
    public function handleSalesConvertQuoteToOrder(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $quote = $event->getQuote();

        $discountAmount = 0.00;
        $baseDiscountAmount = 0.00;
        foreach ($quote->getAllAddresses() as $address) {
            $discountAmount += $address->getDiscountAmount();
            $baseDiscountAmount += $address->getBaseDiscountAmount();

            // Each address *may* have a discount description but the order
            // can only have one. The discount description appears to only apply
            // to discounts added with a coupon. OOTB Magento only supports a
            // single coupon per order. So, while each address may have a discount
            // description, any address that has one will have the same one -
            // the one coupon discount that can be applied. As the description
            // can be on any, all or none of the addresses, check each address
            // for one and take the description from the first address that has one.
            if (!$order->hasDiscountDescription() && $address->hasDiscountDescription()) {
                $order->setDiscountDescription($address->getDiscountDescription());
            }
        }

        $order->setDiscountAmount($discountAmount)
            ->setBaseDiscountAmount($baseDiscountAmount);

        return $this;
    }
}
