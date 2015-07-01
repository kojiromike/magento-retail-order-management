![ebay logo](/docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# Omnichannel Inventory Management

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents

- [Introduction](#introduction)
- [ATP Inventory Feed](#atp-inventory-feed)
- [Inventory Service](#inventory-service)
- [Dependencies](#dependencies)

## Introduction

The Magento Retail Order Management Extension will import an Available to Promise (ATP) feed from the Fulfillment Hub to be used by Magento to indicate whether a product should be in-stock or out-of-stock for use on category, search or product pages. 

A real-time inventory service call will be performed when adding the product to cart. Inventory data from the ATP feed is used instead of the inventory service on category, search and product pages to maintain site performance and the ability to cache the catalog pages.

## ATP Inventory Feed

The ATP Inventory Feed provides:

- A complete feed of available to promise inventory on a daily basis to true up inventory levels.
- An incremental feed once every fifteen minutes (timing configurable) to account for warehouse receipts and other adjustments that may occur throughout the day.

## Inventory Service

The Inventory Service provides:

- Real-time inventory availability validation as products are carted. 
- Real-time inventory details, including estimated delivery date and shipping origin.
- Real time inventory allocation upon order submission.
- Real time rollback of inventory in the event that a order submission fails (e.g. payment failed).

### Estimated Delivery Date

During the checkout process, once the customer has entered certain required information, shipping address and shipping method, the Magento Retail Order Management Extension will request inventory details that will include the Estimated Delivery Date for each item in the order (that is in-stock). This information is submitted with order and may be used in transactional e-mails to the customer. The Estimated Delivery Date will also be displayed to the customer in the Order Review step of checkout.

### Backorders

In the Item Master, a `SalesClass` of `advanceOrderOpen` will set a product’s Backorders attribute to “Allow Qty Below 0 (and Notify Customers)” in Magento. All other `SalesClass` values will set a product's Backorders attribute to “No Backorders” in Magento.

In **System > Configuration > eBay Enterprise > Retail Order Management > Inventory** there is a configurable option to, "Send Inventory Requests for Backorderable Products". When enabled:

- If the product has sufficient quantity to fulfill the order, the extension will also request inventory details and allocation as normal, generating an Estimated Delivery Date and Inventory Allocation for that in-stock product.
- If the product does not have sufficient quantity to fulfill the order, the extension will not request inventory details or allocation, since there will be no inventory to allocate or provide details for.
- If the product does not have sufficient quantity to fulfill the order, then native Magento functionality to notify the customer that the item is backorderd may be enabled.
- If the product is a preorder with a Street Date supplied and "Use Street Date as Estimated Delivery Date for Backordered Products" is enabled then the Street Date will be used as the Estimated Delivery Date.

When "Send Inventory Requests for Backorderable Products" is disabled, the Magento Retail Order Management Extension will not make any requests to the ROM inventory service.

### Manage Stock

The Magento Retail Order Management Extension will check the inventory service for all products that are set to Manage Stock in Magento.

### Inventory Expiration

The amount of time to cache the validated inventory quantity of a cart session is configurable at **System > Configuration > eBay Enterprise > Retail Order Management > Inventory > Inventory Expiration**.

If the Magento mini-cart is in use, Magento will check internal inventory of all products in that cart with every page refresh. The Magento Retail Order Management Extension observes the Magento inventory events that are dispatched at this time for each product in cart. Corresponding quantity requests to the Retail Order Management inventory service could potentially number dozens per minute per customer depending on how many products a customer has in their cart, and how quickly they navigate through the catalog.

To prevent heavy traffic to the Retail Order Management inventory service as well as a performance impact to Magento, the extension caches the inventory quantity while no changes are made to the cart. A cart update will always trigger new inventory quantity requests. Thus, this configuration settings provides an expiration for this cache so that when a customer returns to their persistent cart, potentially hours later, a fresh inventory quantity request is triggered even though the cart was not updated.

## Dependencies

### Magento Modules

- EbayEnterprise_Eb2cCore
- TBD

### Other Dependencies

- TBD

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.
