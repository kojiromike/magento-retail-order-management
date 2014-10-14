# Gift Cards

Provides the use of gift cards via the eBay Enterprise Retail Order Management service.

Gift cards may only be added to a customers cart from the cart page. Gift card balances may be checked from the cart as well as within the customer account pages.

Multiple gift cards may be applied to a single order. Amounts will be applied to gift cards in the order they are added to the cart.

Gift cards will only ever be redeemed for the amount displayed in the totals while creating an order. If a card cannot be redeemed for that exact amount, the customer will be asked to review the order and confirm the amounts to be applied to each gift card or other payment methods.

## Configuration

Gift cards are mapped to a tender type by the card number. Ranges of card numbers belonging to a specific tender type are configured by a config.xml file included in Magento's `app/etc/` directory. A sample configuration is provided in [`/src/app/etc/rom.xml.sample`](/src/app/etc/rom.xml.sample).

## Notes

* While creating orders in the admin, gift card amounts listed in the form are based upon order totals when the gift card is applied and may not necessarilly reflect the amount to be applied to the order. The totals line for gift cards should display the appropriate amount to be applied to gift cards.

[Dependencies](docs/DEPENDENCIES.md)
