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

class EbayEnterprise_Multishipping_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * When handling a sales order before save event, ensure that any shipment
     * amounts for that order have been collected before the order is saved.
     */
    public function testHandleSalesOrderBeforeSave()
    {
        $order = $this->getModelMock('sales/order', ['collectShipmentAmounts']);
        $order->expects($this->once())
            ->method('collectShipmentAmounts')
            ->will($this->returnSelf());
        $event = new Varien_Event(['order' => $order]);
        $eventObserver = new Varien_Event_Observer(['event' => $event]);

        $observer = Mage::getModel('ebayenterprise_multishipping/observer');
        $observer->handleSalesOrderSaveBefore($eventObserver);
    }

    /**
     * When converting a quote to an order, discount data from quote addresses
     * needs to bubble up to the order.
     */
    public function testHandleSalesConvertQuoteToOrder()
    {
        $shipAddressDiscountAmt = 5.00;
        $shipAddressBaseDiscountAmt = 7.00;
        $altShipAddressDiscountAmt = 3.00;
        $altShipAddressBaseDiscountAmt = 5.00;
        $discountTotal = 8.00;
        $discountBaseTotal = 12.00;
        $discountDescription = 'Discount Description';
        $altDiscountDescription = 'Alt Discount Description';

        $order = Mage::getModel('sales/order');
        $quote = $this->getModelMock('sales/quote', ['getAllAddresses']);
        $shippingAddress = Mage::getModel(
            'sales/quote_address',
            ['discount_amount' => $shipAddressDiscountAmt, 'base_discount_amount' => $shipAddressBaseDiscountAmt, 'discount_description' => $discountDescription]
        );
        $altShippingAddress = Mage::getModel(
            'sales/quote_address',
            ['discount_amount' => $altShipAddressDiscountAmt, 'base_discount_amount' => $altShipAddressBaseDiscountAmt, 'discount_description' => $altDiscountDescription]
        );
        $billingAddress = Mage::getModel('sales/quote_address', []);

        $quote->method('getAllAddresses')->willReturn([$shippingAddress, $altShippingAddress, $billingAddress]);

        $event = new Varien_Event(['order' => $order, 'quote' => $quote]);
        $eventObserver = new Varien_Event_Observer(['event' => $event]);

        $observer = Mage::getModel('ebayenterprise_multishipping/observer');
        $observer->handleSalesConvertQuoteToOrder($eventObserver);

        $this->assertSame($discountTotal, $order->getDiscountAmount());
        $this->assertSame($discountBaseTotal, $order->getBaseDiscountAmount());
        // Only the first discount description encountered should be used for the
        // order. If there are multiple discount descriptions, in OOTB Magento,
        // they will always be the same (here "discount" really only means coupons).
        // In the case that they are, for some reason, different on different addresses,
        // the order still only takes a single discount description so only
        // the first should be used.
        $this->assertSame($discountDescription, $order->getDiscountDescription());
    }
}
