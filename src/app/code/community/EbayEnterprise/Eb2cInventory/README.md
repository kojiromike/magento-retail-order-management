![ebay logo](../../../../../../docs/static/logo-vert.png)

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

When using Magento's persistent cart, the amount of time to cache the validated inventory quantity of a cart session is configurable at **System > Configuration > eBay Enterprise > Retail Order Management > Web Services > Inventory Expiration**.

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