# In-Store Pick-Up, Ship-To-Store, Ship-From-Store

Module for in-store integrations - in-store pick-up, ship-to-store and ship-from-store.

This module is deactivated by default and must be activated via an `app/etc/modules` XML file.

## Contents

- [Product Feed Attributes](#product-feed-attributes)
- [Magento Product Attributes](#magento-product-attribute)
- [Dependencies](#dependencies)

## Product Feed Attributes

The following extended attributes when present in the ItemMaster feed.

```xml
<Item>
	…
	<ExtendedAttributes>
		...
		<IspEligible>true|false</IspEligible>
		<InventoryCheckEligible>true|false</InventoryCheckEligible>
		<IspReserveEligible>true|false</IspReserveEligible>
		<StsEligible>true|false</StsEligible>
		<SfsEligible>true|false</SfsEligible>
		...
	</ExtendedAttributes>
	…
</Item>
```

## Magento Product Attributes

The following product attributes are created and added to the "default" attribute set when installing and enabling this modules:

| Attribute Name | Attribute Code |
|----------------|----------------|
| In-store Pickup Eligible | isp_eligible |
| In-store Pickup Reservation Eligible | isp_reserve_eligible |
| Inventory Lookup Eligible | inventory_check_eligible |
| Ship-from-store Eligible | sfs_eligible |
| Ship-to-store Eligible | sts_eligible |

## Dependencies

### Magento Modules

- Mage_Catalog
- EbayEnterprise_Catalog

- - -
Copyright © 2015 eBay Enterprise, Inc.
