# eBay Enterprise Credit Card Payments

Direct integration for Credit Card payments through eBay Enterprise Retail Order Management payment services. The integration uses client side encryption to prevent credit card numbers and card security codes from being sent to Magento as raw text.

eBay Enterprise Credit Card payments are configured in the Magento admin along with other Magento payment methods.

Credit card types in Magento must be mapped to supported tender types in the eBay Enterprise Retail Order Management payment service. This configuration is done in via Magento config.xml. See the sample configuration in [rom.xml.sample](/src/app/etc/rom.xml.sample). Only card types that have a mapping to a payment service tender type will be available in Magento.
