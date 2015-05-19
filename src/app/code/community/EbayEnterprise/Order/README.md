![ebay logo](/docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# Distributed Order Management

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents
- [Introduction](#introduction)
- [Order Submission](#order-submission)
- [Order History](#order-history)
- [Order Events](#order-events)
- [Transactional Emails](#transactional-emails)
- [Dependencies](#dependencies)

## Introduction

When using the Magento Retail Order Management Extension, the Retail Order Management platform will now serve as the Order Management System (OMS) and be the sole system responsible for the state of the order.

Once an order is submitted, changes to an order from Magento will not be applied to that order. Likewise, Magento may not receive all order updates from the Retail Order Management order service.

## Order Submission

### Fault Tolerance

The Magento Retail Order Management Extension will save and resubmit orders that fail due to timeouts or the unavailability of the Retail Order Management order service.

- Orders will be created in a state of `new` with status of `unsubmitted`.
- When an order is successfully submitted to the order service, the order status will be updated to `pending`.
- A cron job will regularly look for orders with a status of `unsubmitted` and resubmit those orders.

### Virtual Orders

In Magento, virtual orders include only downloadable products, virtual products or virtual gift cards. When creating a virtual order, Magento only collects the customer's billing information and does not collect shipping information.

The order service requires both billing and shipping address information when creating an order. To support virtual orders, the extension uses the billing address as the shipping address.

### Extending Order Create

It is possible to modify or pass additional order information to the Retail Order Management order service when submitting an order by observing appropriate events.

As the Magento Retail Order Management Extension creates an order, it will dispatch events with appropriate data at designated points. To add or change data in the order create request, simply observe one or more of these events and supply the appropriate data to the appropriate [payload from the Retail Order Management SDK](http://ebayenterprise.github.io/RetailOrderManagement-SDK-Docs/namespaces/eBayEnterprise.RetailOrderManagement.Payload.Order.html).

#### Dispatched Order Create Events

| Level      | Event Name                                  | Data |
|:-----------|:--------------------------------------------|:-----|
| Item       | `ebayenterprise_order_create_item`          | `Mage_Sales_Model_Order_Item item`,<br /> `IOrderItem item_payload`,<br /> `Mage_Sales_Model_Order order`,<br /> `Mage_Customer_Model_Address_Abstract address`,<br /> `int line_number`,<br /> `string shipping_charge_type` |
| Ship Group | `ebayenterprise_order_create_ship_group`    | `Mage_Customer_Model_Address_Abstract address`,<br /> `Mage_Sales_Model_Order order`,<br /> `IShipGroup ship_group_payload`,<br /> `IOrderDestinationIterable order_destinations_payload`
| Payment<sup>1</sup>   | `ebayenterprise_order_create_payment`       | `Mage_Sales_Model_Order order`,<br /> `IPaymentContainer payment_container`,<br /> `SplObjectStorage processed_payments` |
| Context    | `ebayenterprise_order_create_context`       | `Mage_Sales_Model_Order order`,<br /> `IOrderContext order_context`
| Order      | `ebayenterprise_order_create_before_attach` | `Mage_Sales_Model_Order order`,<br /> `IOrderCreateRequest payload` |
| Order      | `ebayenterprise_order_create_before_send`   | `Mage_Sales_Model_Order order`,<br /> `IOrderCreateRequest payload` |

1. Any handler of the `ebayenterprise_order_create_payment` event should attach payments to the `processed_payments` object to indicate that a payload was created. Handlers should avoid creating a new payload for any payment in the set of processed payments to avoid adding duplicate payment information to the request.

#### Example

To include a payment method that is supported by the Retail Order Management platform, but not yet included in the Magento Retail Order Management Extension such as Cash on Delivery:

##### 1. Observe Order Create Event

In the cash on delivery payment method module, simply observe the `ebayenterprise_order_create_payment` event, 

```XML
<ebayenterprise_order_create_payment>
    <observers>
        <vendor_cod_order_create_payment_observer>
            <type>model</type>
            <class>vendor_cod/observer</class>
            <method>handleOrderCreatePaymentEvent</method>
        </vendor_cod_order_create_payment_observer>
    </observers>
</ebayenterprise_order_create_payment>
```

```PHP
    /**
     * add cash on delivery payment payloads to the order create
     * request.
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleOrderCreatePaymentEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $processedPayments = $event->getProcessedPayments();
        $paymentContainer = $event->getPaymentContainer();
        Mage::getModel('vendor_cod/order_create_payment')
            ->addPaymentsToPayload($order, $paymentContainer, $processedPayments);
        return $this;
    }
```

##### 2. Add or Update Data to Payloads

Then use the included [`IPaymentContainer payment_container`](http://ebayenterprise.github.io/RetailOrderManagement-SDK-Docs/classes/eBayEnterprise.RetailOrderManagement.Payload.Order.IPaymentContainer.html) payload to populate an empty [`IPrepaidCashOnDeliveryPayment`](http://ebayenterprise.github.io/RetailOrderManagement-SDK-Docs/classes/eBayEnterprise.RetailOrderManagement.Payload.Order.IPrepaidCashOnDeliveryPayment.html) payload with the payment method data from the included `Mage_Sales_Model_Order order`.

```PHP
    /**
     * Attach cash on delivery payloads
     * @param Mage_Sales_Model_Order $order
     * @param IPaymentContainer      $paymentContainer
     * @param SplObjectStorage       $processedPayments
     */
    public function addPaymentsToPayload(
        Mage_Sales_Model_Order $order,
        IPaymentContainer $paymentContainer,
        SplObjectStorage $processedPayments
    ) {
        foreach ($order->getAllPayments() as $payment) {
            if ($this->_shouldIgnorePayment($payment, $processedPayments)) {
                continue;
            }
            $iterable = $paymentContainer->getPayments();
            $payload = $iterable->getEmptyPrepaidCashOnDeliveryPayment();
            $payload
                ->setAmount($payment->getAmount())
            // add the new payload
            $iterable->OffsetSet($payload, $payload);
            // put the payment in the processed payments set
            $processedPayments->attach($payment);
        }
    }
```

## Order History

When a customer checks their order status via the "Orders and Returns" or "My Orders" section of their "My Account" profile, the order service is called to provide up-to-date order history and details, including order status and shipment tracking numbers. All of the customer's orders, even those that did not originate with Magento, are displayed. This information is not saved back into the Magento sales database.

- Order data will be retrieved in real-time to be displayed to the customer.
- Invoices will not be shown on the order detail page.
- Since order data originates from the Retail Order Management order service, reorder links will not be shown.
- The Retail Order Management order service does not include the Ship To address in the Order Summary response. The Ship To address will not be displayed on the "My Orders" section of the Customer's "My Account" profile.  However, the Ship To address will display in the Order Details page.
- Order Status will be displayed as received from the Retail Order Management order service. This text may be optimized for customer consumption by using the Magento translation functionality.


## Order Events

The Retail Order Management order service may make order events available for the extension to consume via a message queue. On consumption, the extension will fire corresponding Magento events with the message of that event as a payload. The extension may trigger Magento actions like creating a shipment or credit memo when applicable.

It should be noted that the extension will not guarantee capture of all updates or changes to an order. Depending on the desired outcome, the Magento system integrator may need to create custom code to listen for these events and react as desired.

### Magento Events

The events dispatched in Magento for specific Retail Order Management order events will be named according to the following specification:

1. Event names will begin with `ebayenterprise_amqp_message_order_`
2. The name of the ROM order event will be converted from CamelCase to underscore_case.

Magento events dispatched for order events will include a [payload](https://github.com/eBayEnterprise/RetailOrderManagement-SDK), which is a PHP object with data exposed via getters and setters.

If a specific implementation requires order events to trigger additional functionality, a Magento System integrator may implement custom event observers to trigger any desired action. The following is an example event observer:

```php
/**
 * Sample observer method
 */
class Observer {
    public function processAmqpMessage{orderStatusEventName}(Varien_Event_Observer $observer)
    {
        Mage::getModel('eb2corder/{order_status_event_name}', array(
            'payload' => $observer->getEvent()->getPayload(),
            'order_event_helper' => $this->_orderEventHelper
        ))->process();
        return $this;
    }
}
```

### Supported Order Events

| Order Event | Magento Event | Description | Action |
|:------------|:--------------|:------------|:-------|
| Accepted    | `ebayenterprise_amqp_message_order_accepted` | The order was accepted by the Retail Order Management public API, order service or OMS as indicated by the event message. | None |
| Backorder   | `ebayenterprise_amqp_message_order_backorder` | Some quantity of one or more line items were backordered. | None |
| Cancelled      | `ebayenterprise_amqp_message_order_cancelled` | Some quantity of one or more line items were cancelled. | None |
| Confirmed | `ebayenterprise_amqp_message_order_confirmed` | The order was confirmed by ROM, and released for fulfillment. May include adjustments to the order (e.g. price, tax, shipping). | None |
| Credit Issued | `ebayenterprise_amqp_message_order_credit_issued`  | A credit was issued against one or more line items after shipment. | Creates a Magento credit memo. For partial line item credits, sums the credit as the adjustment refund of the credit memo. |
| Gift Certificate | `ebayenterprise_amqp_message_order_gift_card_activation` | A virtual gift certificate was issued. | None |
| Price Adjustment | `ebayenterprise_amqp_message_order_price_adjustment` | The price of one or more line items was adjusted. | None |
| Rejected    | `ebayenterprise_amqp_message_order_rejected` | The order was rejected by ROM. | Cancels the Magento order. |
| Return in Transit | `ebayenterprise_amqp_message_order_return_in_transit` | A return for the order is in transit. | None |
| Shipped     | `ebayenterprise_amqp_message_order_shipped` | Some quantity of one or more line items were shipped. May include adjustments to the order (e.g. price, tax, shipping). | Creates a Magento shipment for the indicated line items and quantity. Includes the tracking information. <br /><br />**Note**: Adjustments are not applied to the Magento order. |

## Transactional Emails

The Retail Order Management notification service is pre-integrated with eBay Enterprise Email to send transactional emails. If the extension is configured to use eBay Enterprise Email as the email handler, then the following emails will be suppressed from Magento:

- New Order Confirmation
- New Order
- Order Comment
- New Shipment
- Shipment Comment
- New Invoice
- Invoice Comment
- New Credit Memo
- Credit Memo Comment

If a specific implementation requires a third-party email provider, then the Magento system integrator will be required to ensure the order events trigger the proper third party emails.

## Dependencies

### Magento Modules

- Mage_Adminhtml
- Mage_Catalog
- Mage_Checkout
- Mage_Core
- Mage_Sales
- EbayEnterprise_Eb2cCore
- EbayEnterprise_MageLog

### Other Dependencies

- [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.