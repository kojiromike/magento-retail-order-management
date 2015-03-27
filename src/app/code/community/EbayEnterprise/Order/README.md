![ebay logo](../../../../../../docs/static/logo-vert.png)

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

Order custom attributes can apply to the order at three different levels. The extension dispatches events appropriate to modifying and injecting custom information at these levels, described below.

### Supported Order Create Events

|  Level  |                  Event Name                 |
|:--------|:--------------------------------------------|
| Item    | `ebayenterprise_order_create_item`          |
| Context | `ebayenterprise_order_create_context`       |
| Order   | `ebayenterprise_order_create_before_attach` |
| Order   | `ebayenterprise_order_create_before_send`   |

#### Local XML Configuration

The order custom attribute map requires additional configuration via local XML.

The extension includes a sample order custom attribute configuration file—[`/path/to/magento/root/dir/app/etc/ordercustomattributes.xml.sample`](../../../../etc/ordercustomattributes.xml.sample)—that includes example configuration options. You can use this file as a starting point for your implementation by renaming this file to `ordercustomattributes.xml`. Carefully update the map to ensure it matches your specific implementation.

```xml
<custom_attribute_mappings>
	<order_level>
		<increment_id> <!-- A known value that can be retrieved via 'get' magic method or an actual public method call on any concrete class instance that extend Varien_Object class-->
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</increment_id>
	</order_level>
	<order_item_level>
		<sku> <!-- A known value that can be retrieved via 'get' magic method or an actual public method call on any concrete class instance that extend Varien_Object class-->
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</sku>
	</order_item_level>
	<order_context_level>
		<name>
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</name>
	</order_context_level>
</custom_attribute_mappings>
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