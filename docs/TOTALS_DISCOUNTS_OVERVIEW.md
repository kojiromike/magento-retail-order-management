# Magento Quote Totals and Discount Calculation overview:
### The following are classes and methods responsible for calculating quote, and quote item totals and row totals.
- *sales/quote_item_abstract* class has various methods to calculate quote item row totals, base totals, and etc...

### The following are classes and methods responsible for calculating quote and quote item discount amounts and totals.
- *Sales/quote_address_total_discount::collect* process totals and amount on the quote item level.
	- Quote item discount calculation process using SalesRules:
		- *salesrule/validator::process* method
			- calculate data for the following quote item || order item fields:
			- discount_amount
			- base_discount_amount
- The event *salesrule_validator_process* get dispatch taking the rule, quote_item, quantity, and ...etc in order to calculate discount and then more calculation are applied base on configuration.
- The event *sales_quote_address_discount_item* get dispatch

### The following are classes and methods responsible for calculating quote shipping discount amounts:
- The *shipping_discount_amount* is set in the *sales/quote_address* and the calculation occurred in the *salesrule/validator::processShippingAmount* method.
	- Calculation data for quote_address fields using the applied salesrule:
		- shipping_discount_amount
		- base_shipping_discount_amount
