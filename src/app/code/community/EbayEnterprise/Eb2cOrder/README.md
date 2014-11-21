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

### Order Custom Attributes

It is possible to pass additional order information as custom attributes to the Retail Order Management order service when submitting an order.

Order custom attributes can apply to the order at three different levels:

- Order Level
- Order Item Level
- Order Context Level

| Important |
|:----------|
| The order custom attribute map assumes that the data intended to be mapped to an order custom attribute can be retrieved using the get magic method on a concrete class instance that extended a `Varien_Object` class. |

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

When a customer checks their order status via the "Orders and Returns" or "My Orders" section of their "My Account" profile, the order service is called to provide up-to-date order history and details, including order status and shipment tracking numbers. This information is not saved back into the Magento sales database.

- Order data will be retrieved in real-time to be displayed to the customer.
- Orders must exist in both Magento and the Retail Order Management System.
- Invoices will not be shown on the order detail page.
- The "Recent Orders" and "My Orders" sections of the customer account pages display the "Ship To" name for the order in Magento, not the Retail Order Management System. The templates being used to display this data may be modified to prevent this data from displaying.

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
| Accepted    | | | None |
| Backorder   | | Some quantity of one or more line items were backordered. | None |
| Cancelled      | | Some quantity of one or more line items were cancelled. | None |
| Credit Issued | | A credit was issued against one or more line items after shipment. | Creates a Magento credit memo. For partial line item credits, sums the credit as the adjustment refund of the credit memo. |
| Confirmation | | The order was confirmed by ROM, and released for fulfillment. May include adjustments to the order (e.g. price, tax, shipping). | None |
| Gift Certificate | A virtual gift certificate was issued. | None |
| Price Adjustment | | The price of one or more line items was adjusted. | None |
| Rejected    | `ebayenterprise_amqp_message_order_rejected` | The order was rejected by ROM. | Cancels the Magento order. |
| Return      | | | |
| Return in Transit ||  | None |
| Shipped     | | Some quantity of one or more line items were shipped. May include adjustments to the order (e.g. price, tax, shipping). | Creates a Magento shipment for the indicated line items and quantity. Includes the tracking information. <br /><br />**Note**: Adjustments are not applied to the Magento order. |

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

- TBD

### Other Dependencies

- TBD

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.