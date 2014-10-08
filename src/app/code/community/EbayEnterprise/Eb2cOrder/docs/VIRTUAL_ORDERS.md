# Virtual Orders

### Definition

Magento defines a virtual order as an order with only virtual items such as virtual products or and virtual gift card products.
For more information about virtual products [click here](http://www.magentocommerce.com/wiki/modules_reference/english/mage_adminhtml/catalog_product/producttype).

Magento does not collect customer shipping information when creating a virtual order. Only billing information is collected.

Virtual products are digital gift cards, subscriptions, and etc...

### ROM Order Create Service

The order create service requires both billing and shipping address data. To support Magento Virtual orders in the ROM Order Service the billing address is sent as shipping address whenever shipping data are not available when creating the order.
