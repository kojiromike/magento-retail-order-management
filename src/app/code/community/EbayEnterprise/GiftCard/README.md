![ebay logo](/docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# Gift Card Payments

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents

- [Introduction](#introduction)
- [Local XML Configuration](#local-xml-configuration)
- [Dependencies](#dependencies)

## Introduction

The Magento Retail Order Management Extension enables gift card payment processing via the Retail Order Management payment service.

Gift cards may only be added to a customer's cart from the cart page. Gift card balances may be checked from the cart and the customer's "My Account."

Multiple gift cards may be applied to a single order. Amounts will be applied to gift cards in the order they are added to the cart.

Gift cards will only ever be redeemed for the amount displayed in the totals while creating an order. If a card cannot be redeemed for that exact amount, the customer will be asked to review the order and confirm the amounts to be applied to each gift card or other payment methods.

While creating orders in the admin, gift card amounts listed in the form are based upon order totals when the gift card is applied and may not necessarily reflect the amount to be applied to the order. The totals line for gift cards should display the appropriate amount to be applied to gift cards.

| Important |
|:----------|
| The Gift Card module is incompatible with any payment method that calculates totals and is unaware of this modules' implementation. |

## Dependencies

### Magento Modules

- Mage_Core
- Mage_Checkout
- Mage_Sales
- EbayEnterprise_Eb2cCore
- EbayEnterprise_MageLog

### Other Dependencies

- [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.
