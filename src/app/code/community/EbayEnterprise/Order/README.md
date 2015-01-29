![ebay logo](../../../../../../docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# Distributed Order Management

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents
- [Introduction](#introduction)
- [Order Submission](#order-submission)
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

## Dependencies

### Magento Modules

- Mage_Sales
- EbayEnterprise_Eb2cCore
- EbayEnterprise_MageLog

### Other Dependencies

- RetailOrderManagement-SDK

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.
