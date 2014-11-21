![ebay logo](../../../../../../docs/static/logo-vert.png)

**Magento Retail Order Management Extension**

# Address Validation

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents

- [Introduction](#introduction)
- [Dependencies](#dependencies)

## Introduction

The Address Validation service ensures that complete and accurate customer address information is collected at the time of order. Should an address fail validation, the customer is presented with a configurable number of alternative addresses.

In checkout, shipping addresses (including those used for both billing and shipping) are validated. In the customer's Address Book, all addresses are validated, as any address may be selected as a shipping address during checkout.

## Dependencies

### Magento Modules

- Mage_Checkout
- Mage_Core
- Mage_Customer
- Mage_Directory
- EbayEnterprise_Eb2cCore
- EbayEnterprise_MageLog

### Other Dependencies

- EbayEnterprise_Dom

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.