# Virtual Quotes

The Magento virtual quote is a quote with only virtual products or virtual gift card products.

[See the definition of a Virtual Order](../../Eb2cOrder/docs/VIRTUAL_ORDERS.md).

### ROM TDF Service:

The TDF service requires a Target destination billing reference id and a target destination shipping reference id and that id need to match the mailing address for each type of mailing address such as billing and shipping respectively. To support ROM TDF service on virtual quotes the same target destination billing reference id and is used as the target destination shipping reference id and just has one mailing address matching that one reference id for both billing and shipping.
