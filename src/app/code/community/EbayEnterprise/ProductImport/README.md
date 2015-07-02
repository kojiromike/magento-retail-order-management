![ebay logo](/docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# Product Import Guide

## Contents

- [Introduction](#introduction)
- [Enabling Product Import ](#enabling-product-import)
- [Magento Product Attributes](#magento-product-attributes)
- [Product Hub Feed Processing](#product-hub-feed-processing)
  - [Item Master](#item-master)
  - [Content Master](#content-master)
  - [Price Events](#price-events)
  - [New Product Information](#new-product-information)
- [Use Cases](#use-cases)
  - [Associate Products to Categories](#associate-products-to-categories)
  - [Import Configurable Products](#import-configurable-products)
  - [Import Gift Cards](#import-gift-cards)
  - [Import Products into Different Magento Websites](#import-products-into-different-magento-websites)
  - [Import Localized Content](#import-localized-content)
  - [Images](#images)
- [Custom Product Import Mapping](#custom-product-import-mapping)
- [Dependencies](#dependencies)

## Introduction

The Magento Retail Order Management Extension provides two different workflows for managing product information. The extension can support direct management of product information from within Magento or import product information into Magento from a third party Product Information Management system.

The catalog workflows are intended to import product information only. Regardless of which catalog workflow is implemented, the following should be configured in Magento prior to importing or exporting products:

- Categories
- Product Attributes
- Product Attribute Sets

This Product Import Guide describes the process and details for importing product information into Magento, via the Retail Order Management Product Hub, from a third party Product Information Management system.  If you would prefer to directly manage product information within Magento, then please consult the [Product Export Guide](/src/app/code/community/EbayEnterprise/ProductExport/README.md).

## Enabling Product Import

Follow the [Installation and Configuration Guide](/docs/INSTALL.md#product-information-management-via-a-3rd-party-system) to enable the Retail Order Management Extension to support importing product information from a third party system. Then return to this guide to learn more about the product import process and configuration.

## Magento Product Attributes

On installation, the Magento Retail Order Management Extension will create the following attributes required for the extension to operate. These attributes should be included in every Attribute Set.

| Attribute Name | Attribute Code  | Description |
|:---------------|:----------------|:------------|
| Size           | `size`          | Product size, if the attribute `size` had already existed in Magento Prior to installing the ROM extension it will be removed and re-installed as a select input type |
| Style ID       | `style_id`      | Associates simple products to configurable products |
| Is Clean       | `is_clean`      | Flag indicating if the product has had all of its product links resolved |
| Unresolved Product Links | `unresolved_product_links` | Any related, cross-sell or up-sell product links for the product that have not yet been resolved, typically due to the target products not existing in Magento yet |
| HTS Codes      | `hts_codes`     | Serialized mapping of tax codes used for calculating international taxes and duties |
| Tax Code       | `tax_code`      | Tax code used by the Retail Order Management tax service |
| Catalog Class  | `catalog_class` | Intended to specify how an item displays in a catalog. This value drives no out-of-the-box business logic in Magento. |
| Item Status    | `item_status`   | Preserves the original Item Status value. This value drives no out-of-the-box business logic in Magento. |
| Street Date    | `street_date`   | Earliest date the Retail Order Management OMS will allocate the product. |

## Product Hub Feed Processing

The Retail Order Management Product Hub expects product information to be provided in three separate feeds, each with a canonical specification. The Product Hub will relay this product data to various internal systems as well as the Magento webstore.

When using the Product Import module, the Magento Retail Order Management Extension is preconfigured to import relevant data from these feeds. Product information that is essential to the operation of the extension will be hard mapped and cannot change. Less essential product information is provided in [sample maps](/src/app/etc/productimport.xml.sample) that can be customized for the specific needs of a merchant's implementation.

### Item Master

The Item Master provides information for the sellable products, e.g. simple, bundle, virtual, downloadable and gift card products. "Parent" products used primarily for grouping child products in the webstore should not be included in the webstore, e.g. configurable and grouped products.

The product information provided in the Item Master drives much of the business logic for each product. Some of the information provided in the Item Master is required by internal systems of the Retail Order Management Platform and is irrelevant to Magento and is thus discarded during the import process. The relevant information is processed into Magento as follows:

`/ItemMaster/Item[]`

| Item Master XPath | Magento Product Attribute | Notes |
|:------------------|:--------------------------|:------|
| `@operation_type` | N/A | When importing into Magento, both operation types will either add a new product or update an existing product. |
| `@gsi_client_id` | N/A | Client Id provided by eBay Enterprise and configured at the Website scope. Products will only be assigned to Magento Websites with a matching Client Id. See [Import Products into Different Magento Websites](#import-products-into-different-magento-websites) for more details on assigning products to Websites. |
| `@catalog_id` | N/A | Catalog Id provided by eBay Enterprise and configured at the Global scope. Products will only be imported with a matching Catalog Id. |
| `ItemId/ClientItemId` | SKU | |
| `BaseAttributes/CatalogClass` | [Catalog Class](#magento-product-attributes) | |
| `BaseAttributes/ItemStatus` | Status and [Item Status](#magento-product-attributes) | A value of `Active` will result in a product that is "Enabled" in Magento. Both `Discontinued` and `Inactive` will result in a product that is "Disabled" in Magento. The original value will also be preserved and saved as Item Status. |
| `BaseAttributes/TaxCode` | [Tax Code](#magento-product-attributes) | Used in the tax duty request. Note that this is different than the "Tax Class" in Magento. |
| `ExtendedAttributes/AllowGiftMessage` | Allow Message | For gift cards. |
| `ExtendedAttributes/ColorAttributes/Color` | Color |
| `ExtendedAttributes/ColorAttributes/Color/Code` | Color Admin Label | A unique value used to identify the color. This value will be used as the admin label for the color option. When colors are imported, if a color option with an admin label matching the code already exists, that color option will be reused for the product. When a new `Color/Code` is encountered, a new option will be created for the color. |
| `ExtendedAttributes/ColorAttributes/Color/Description` | Color Store View Label | The localized name of the color. This value will be used as the Store View specific label for color option and will be applied to any Store Views that are configured with a Store Language Code matching the `xml:lang` attribute of this node. |
| `ExtendedAttributes/CountryOfOrigin` | Country of Manufacture | This value should be the two character ISO 3166 country code. |
| `ExtendedAttributes/GiftCardTenderCode` | Gift Card Type | Values mapped as defined by `default/ebayenterprise_catalog/feed/gift_card_tender_code` in [`app/etc/productimport.xml`](/src/app/etc/productimport.xml.sample). |
| `ExtendedAttributes/MaxGCAmount` | Open Amount Max Value | |
| `ExtendedAttributes/ItemDimension/Shipping/Mass/Weight` | Weight | |
| `ExtendedAttributes/SalesClass` | Backorders |  Values mapped as defined by `default/ebayenterprise_catalog/feed/stock_map` in [`app/etc/productimport.xml`](/src/app/etc/productimport.xml.sample). |
| `ExtendedAttributes/StreetDate` | [Street Date](#magento-product-attributes) | |
| `ExtendedAttributes/Style/StyleId` | [Style Id](#magento-product-attributes) | The Style Id will associate a child product to a parent configurable product whose SKU matches that Style Id. |
| `CustomAttributes` | N/A | Additional key/value pairs may be included in the Item Master feed. A few Custom Attributes have been mapped to required Magento Product Attributes. Additional [Custom Product Import Mappings](#custom-product-import-mapping) may be added to local configuration. |
| `CustomAttributes/Attribute[@name="ProductType"]/Value` | Product Type | Possible values include `bundle`, `configurable`, `downloadable`, `giftcard`, `grouped`, `simple` and `virtual`. Required for product creation, else product will be created as a simple product. |
| `CustomAttributes/Attribute[@name="AttributeSet"]/Value` | Attribute Set | Should match the name of the Attribute Set in Magento exactly. Required for product creation, else product will be created with the default Attribute Set |
| `CustomAttributes/Attribute[@name="Visibility"]/Value` | Visibility | Optional attribute to set the product visibility. Accepted values can include either the following integers or exact strings: <table><thead><tr><th>Integer</th><th>String</th></tr></thead><tbody><tr><td>`1`</td><td>`Not Visible Individually`</td></tr><tr><td>`2`</td><td>`Catalog`</td></tr><tr><td>`3`</td><td>`Search`</td></tr><tr><td>`4`</td><td>`Catalog, Search`</td></tr></tbody></table> |

| Important |
|:----------|
| Magento does not support changing the Attribute Set of a product once it's been set. We **strongly** discourage changing a Product's Attribute Set via Product Hub Feeds. Unpredictable results may occur. |

### Content Master

The Content Master provides marketing and display information for visible products. The use of the Content Master with Magento is optional. It is possible to update product content manually or via other import process. However, those updates will not be synced with any of the Retail Order Management systems, so please ensure that any product updates that do not use the Content Master is for exclusively webstore content.

`/ContentMaster/Content[]`

| Content Master XPath | Magento Product Attribute | Notes |
|:---------------------|:--------------------------|:------|
| `@gsi_client_id` | N/A | Client Id provided by eBay Enterprise and configured at the Website scope. Content changes will only be applied to Magento Websites with a matching Client Id. See [Import Products into Different Magento Websites](#import-products-into-different-magento-websites) for more details on assigning product information to Websites. |
| `@catalog_id` | N/A | Catalog Id provided by eBay Enterprise and configured at the Global scope. Content changes will only be applied with a matching Catalog Id. |
| `@gsi_store_id` | N/A | Store Id provided by eBay Enterprise and configured at the Website scope. Content changes will only be applied to Magento Websites with a matching Store Id. See [Import Products into Different Magento Websites](#import-products-into-different-magento-websites) for more details on assigning product information to Websites. |
| `UniqueId` | SKU | |
| `StyleId` | [Style Id](#magento-product-attributes) | The Style Id will associate a child product to a parent configurable product whose SKU matches that Style Id. |
| `ProductLinks/ProductLink` | N/A | Encapsulates related, up-sell and cross-sell product links. |
| `ProductLinks/ProductLink@link_type` | N/A | <table><thead><tr><th>Link Type</th><th>Magento Link</th></tr></thead><tbody><tr><td>`ES_Accessory`</td><td>Related Products</td></tr><tr><td>`ES_CrossSelling`</td><td>Cross-sells</td></tr><tr><td>`ES_UpSelling`</td><td>Up-sells</td></tr></tbody></table> |
| `ProductLinks/ProductLink@operation_type` | N/A | `Add` to create a new link or `Delete` to remove an existing link. |
| `ProductLinks/ProductLink/LinkToUniqueId` | Linked Product SKU | |
| `CategoryLinks` | Product Categories | Encapsulates data used to link products to categories in Magento. Category links will always replace any and all existing category links for the product. Links will only be created to categories that already exist within the Magento instance. See [Associate Products to Catgories](#associate-products-to-categories) for more information and examples. |
| `CategoryLinks/CategoryLink@import_mode` | N/A | Specifies how the link should be handled. Links with an `import_mode` of "Delete" will not be imported. All other category links will be imported and replace any existing category links. |
| `CategoryLinks/CategoryLink/Name` | N/A | Specifies a category hierarchy indicating a category the product should be linked to. Each category in the hierarchy should be delimited by a dash (`-`). The product will only be added to the last category specified by the hierarchy. The first category in each path must be a Root Category. |
| `BaseAttributes/Title` | Name | |
| `ExtendedAttributes/ColorAttributes/Color` | Color |
| `ExtendedAttributes/ColorAttributes/Color/Code` | Color Admin Label | A unique value used to identify the color. This value will be used as the admin label for the color option. When colors are imported, if a color option with an admin label matching the code already exists, that color option will be reused for the product. When a new `Color/Code` is encountered, a new option will be created for the color. |
| `ExtendedAttributes/ColorAttributes/Color/Description` | Color Store View Label | The localized name of the color. This value will be used as the Store View specific label for color option and will be applied to any Store Views that are configured with a Store Language Code matching the `xml:lang` attribute of this node. |
| `ExtendedAttributes/LongDescription` | Description | |
| `ExtendedAttributes/ShortDescription` | Short Description | |
| `CustomAttributes` | N/A | Additional key/value pairs may be included in the Item Master feed. A few Custom Attributes have been mapped to required Magento Product Attributes. Additional [Custom Product Import Mappings](#custom-product-import-mapping) may be added to local configuration. |
| `CustomAttributes/Attribute[@name="ProductType"]/Value` | Product Type | Possible values include `bundle`, `configurable`, `downloadable`, `giftcard`, `grouped`, `simple` and `virtual`. Required for product creation, else product will be created as a simple product. |
| `CustomAttributes/Attribute[@name="ConfigurableAttributes"]/Value` | Configurable Attributes | A comma delimited list of product attributes that Magento should consider configurable when creating a Configurable Product. Required for creation of configurable products. |
| `CustomAttributes/Attribute[@name="AttributeSet"]/Value` | Attribute Set | Should match the name of the Attribute Set in Magento exactly. Required for product creation, else product will be created with the default Attribute Set |
| `CustomAttributes/Attribute[@name="Visibility"]/Value` | Visibility | Optional attribute to set the product visibility. Accepted values can include either the following integers or exact strings: <table><thead><tr><th>Integer</th><th>String</th></tr></thead><tbody><tr><td>`1`</td><td>`Not Visible Individually`</td></tr><tr><td>`2`</td><td>`Catalog`</td></tr><tr><td>`3`</td><td>`Search`</td></tr><tr><td>`4`</td><td>`Catalog, Search`</td></tr></tbody></table> |

| Important |
|:----------|
| Magento does not support changing an Attribute Set of a product once it's been set. We **strongly** discourage changing a Product's Attribute Sets via Product Import Feeds. Unpredictable results will likely occur. |

### Price Events

The Price Event feed provides product price information to Magento and the Retail Order Management Platform. Since Magento supports only a single Price and single Special price, the Magento Retail Order Management Extension will only be able to import one price event at a time, overwriting any previous price events with the last price event received.

`/Prices/PricePerItem[]`

| Prices XPath | Magento Product Attribute | Notes |
|:-------------|:--------------------------|:------|
| `@gsi_store_id` | N/A | Store Id provided by eBay Enterprise and configured at the Website scope. Pricing changes will only be applied to Magento Websites with a matching Store Id. See [Import Products into Different Magento Websites](#import-products-into-different-magento-websites) for more details on assigning product information to Websites. |
| `@gsi_client_id` | N/A | Client Id provided by eBay Enterprise and configured at the Website scope. Pricing changes will only be applied to Magento Websites with a matching Client Id. See [Import Products into Different Magento Websites](#import-products-into-different-magento-websites) for more details on assigning product information to Websites. |
| `@catalog_id` | N/A | Catalog Id provided by eBay Enterprise and configured at the Global scope. Pricing changes will only be applied with a matching Catalog Id. | No |
| `ClientItemId` | SKU |
| `Event` | N/A | A new price event will replace existing product price information for the product's "Price," "Special Price," "Special Price From Date," and "Special Price To Date." |
| `Event/Price` | Price or Special Price | The "sellable price." |
| `Event/MSRP` | Manufacturer's Suggested Retail Price | |
| `Event/AlternatePrice1` | Price |
| `Event/StartDate` | Special Price From Date | While this value is a full date time in the feed, only the date will be used within Magento. |
| `Event/EndDate` | Special Price To Date | While this value is a full date time in the feed, only the date will be used within Magento. |

#### Price vs. Special Price

In the Price Event feed, `Event/Price` is always supposed to represent the "selling price," while `Event/MSRP` and `Event/AlternatePrice1` are never supposed to represent the "selling price" and are instead used or display purposes only.

In Magento, Price may be the "selling price," unless there is an active Special Price that will override Price as the "selling price." Manufacturer's Suggested Retail Price is never used as the "selling price" and is instead used for display purposes only.

Thus the map used to process `Event/Price` and `Event/AlternatePrice1` from the Price Event feed into Magento is not direct. Instead the value of `Event/Price` maps to Price in Magento, if there is no `Event/AlternatePrice1` in the Price Event feed for the item in question. If there is an `Event/AlternatePrice1`, then the value of `Event/AlternatePrice1` will map to Price in Magento, while the value of `Event/Price` will map to Special Price.

#### Price vs. Special Price Use Cases

The Magento Retail Order Management Extension supports two pricing use case scenarios, Regular and Special.

A Regular Price scenario is defined as a price event where an `Event/Price` is included, while an `Event/AlternatePrice1` is not. Typically, this is used as the everyday price of an item. In addition, the start date is immediate, regardless of the actual start date sent.

Example:

```xml
<PricePerItem gsi_client_id="MAGTNA" catalog_id="45" gsi_store_id="MAGT1">
    <ClientItemId>123456789</ClientItemId>
    <Event>
        <Price>62.99</Price>
        <StartDate>2014-06-13T11:59:59-06:00</StartDate>
        <EndDate>2500-12-31T23:59:59-05:00</EndDate>
    </Event>
</PricePerItem>
```

A Special Price scenario is defined as a price event where both `Event/Price` and `Event/AlternatePrice1` are included. Typically, this is used for short duration promotional events. The `Event/StartDate` and `Event/EndDate` will apply to the Special Price in Magneto only.

Example:

```xml
<PricePerItem gsi_client_id="MAGTNA" catalog_id="45" gsi_store_id="MAGT1">
    <ClientItemId>123456789</ClientItemId>
    <Event>
        <Price>54.99</Price>
        <AlternatePrice1>62.99</AlternatePrice1>
        <StartDate>2014-06-17T11:59:59-06:00</StartDate>
        <EndDate>2014-06-20T11:59:59-06:00</EndDate>
    </Event>
</PricePerItem>
```

During the time a Regular Price record and a Special Price record intersect, the Special Price record would take precedence.

Using the 2 price records above as an example:

| Date                  | Price  | Special Price | "Selling Price" | "Was Price<sup>†</sup>" |
|:----------------------|:-------|:--------------|:----------------|:------------------------|
| 6/13/14 to 6/16/2014  | $62.99 | None          | $62.99          | None |
| 6/17/14 to 6/20/2014  | $62.99 | $54.99        | $54.99          | $62.99 |
| 6/21/14 to 12/31/2500 | $62.99 | $54.99        | $62.99          | None |

_† Subject to specific pricing template in use for the active theme_

### New Product Information

Since all feeds may not process at the same time, when the Magento Retail Order Management Extension processes a feed to create a new product there will be missing required product attributes in Magento. The extension's feed processor handles this missing information by populating these required product attributes with default or "dummy" data. This data will be later populated with real data when the appropriate feeds are processed.

| Required Product Attribute | "Dummy" Value |
|:---------------------------|:--------------|
| Name                       | Incomplete Product: {SKU} |
| Description                | This product is incomplete. If you are seeing this product, please do not attempt to purchase and contact customer service. |
| Short Description          | Incomplete product. Please do not attempt to purchase. |
| Manage Stock               | `True` |
| Stock Quantity             | `0` |
| Type                       | Simple |
| Weight                     | `0` |

## Use Cases

### Associate Products to Categories

Example:

```xml
<CategoryLinks>
    <CategoryLink import_mode="Update">
        <Name>Store Root-Women</Name>
    </CategoryLink>
    <CategoryLink import_mode="Update">
        <Name>Store Root-Women-Shoes-Boots</Name>
    </CategoryLink>
</CategoryLinks>
```

Will result in the product linked directly to the "Women" and "Boots" categories but not the "Store Root" or "Shoes" category.

### Import Configurable Products

When importing configurable products, the product should only be included in the Content Master feed. The product must include the "ProductType" custom attribute and the "ConfigurableAttributes" custom attribute. The "ProductType" should be `configurable` and "ConfigurableAttributes" should be a comma delimited list of product attribute codes the product is configured on.

Simple products are linked to configurable products by matching the Style ID of the simple product to the SKU of the configurable product. Any time new products are imported, the products will be checked for fulfilling either end of a configurable/simple product relationship.

### Import Gift Cards

Gift Card products may be imported into Magento via the Item Master. Ensure the following Gift Card-specific information is included for the item:

1. Set the Product Type in Magento to Gift Card via a custom attribute with name of "ProductType" and value of "giftcard".
1. Set the Gift Card Type in Magento via `ExtendedAttributes/GiftCardTenderCode` and ensure the value is included in the mapped defined by `default/ebayenterprise_catalog/feed/gift_card_tender_code` in [`app/etc/productimport.xml`](/src/app/etc/productimport.xml.sample).
1. Optionally, set the Open Amount Max Value in Magento via `ExtendedAttributes/MaxGCAmount`.
1. Optionally, set Allow Message in Magento via `ExtendedAttributes/AllowGiftMessage`

The following Gift Card attributes are not provided as named attributes in the Item Master and will default to using the values specified by the Magento Gift Card configuration:

| Gift Card Product Attribute | Configuration Tab | Configuration Option |
|:----------------------------|:------------------|:---------------------|
| Lifetime | Gift Card General Settings | Lifetime (days) |
| Email Template | Gift Card Email Settings | Gift Card Notification Email Template |
| Is Redeemable | Gift Card General Settings | Redeemable |

### Import Products into Different Magento Websites

The combination of incoming `catalog_id`, `gsi_client_id` and optionally `gsi_store_id` from the Product Hub feeds are mapped to Magento Websites via the configuration of the Magento Retail Order Management Extension.

The product import process first gathers all the websites for the Magento instance, and then for each Magento Website, extracts relevant information from each of the feed files according to these rules:

- The ‘catalog_id’, if present, must match the Magento Website’s Catalog Id. Catalog Id is set at the Global scope, which effectively filters out any item that has a Catalog Id that does not match the Magento Website.
- The ‘client_id’, if present, must match the Magento Website’s Client Id.
* The ’store_id’, if present, must match the Magento Website’s Store Id.

It is important to note that the absence of an an attribute in the incoming feed effectively acts as a wildcard.

Examples:

Given a Magento installation with two websites, both configured with the same Client Id, but different Store Ids. An incoming feed that specifies only the `client_id` will be assigned to **both** websites.

An incoming feed node specifying **none** of the these attributes will be assigned to **all** websites.

### Import Localized Content

Product Hub feeds allow certain attributes to contain localized content. Magento manages localized content at the Store View scope. The Magento Retail Order Management Extension's product import process provides a framework to extract data from the Product Hub feeds and ensure the proper localized values will import into the correct Magento Store Views.

#### Product Hub Feed Localization

Product Hub feeds denote language according to one of two possible structures:

```xml
<CustomAttributes>
    <Attribute name="some_attribute" xml:lang="bcp-47-language-code">
        <Value>The localized value</Value>
    </Attribute>
</CustomAttribute>
```

or

```xml
<BaseOrExtendedAttributes>
    <SomeAttribute xml:lang="bcp-47-language-code">The localized value</SomeAttribute>
</BaseOrExtendedAttributes>
```

If an attribute node exists without an `xml:lang` attribute, that value will be set as the default value for the product attribute. (In other words, the value will be set for the product attribute at the default scope level.)

#### Localized Attribute Values

Once the product import process acquires an attribute node with a language code, it applies the value to all stores configured with the same language code. Given a Magento instance with the following Stores:

| Scope               | Language      |
|:--------------------|:--------------|
| Default             | "en-us"       |
| Website1            | "use default" |
| Website1:Storeview1 | "use default" |
| Website1:Storeview2 | "fr-ca"       |
| Website2            | "de-de"       |
| Website2:Storeview3 | "it-it"       |
| Website2:Storeview4 | "en-us"       |
| Website2:Storeview5 | "use website" |
| Website2:Storeview6 | "zh-cn"       |

And given this configuration:

```xml
<feed_attribute_mappings>
    <name>
        <class>ebayenterprise_catalog/map</class>
        <type>helper</type>
        <method>extractStringValue</method>
        <xpath>BaseAttributes/Title</xpath>
    </name>
    ...
</feed_attribute_mappings>
```

With the following feed fragment to import (in the context of the "pickle" product, for sake of simplicity):

```xml
<BaseAttributes>
    …
    <Title xml:lang="en-us">Dill Pickle</Title>
    <Title xml:lang="it-it">sottaceto</Title>
    <Title xml:lang="he-il">דיל פיקל</Title>
    <Title xml:lang="de-de">Dillgurke</Title>
    <Title xml:lang="zh-cn">泡菜</Title>
    …
</BaseAttributes>
```

The resulting product information in Magento:

| Scope               | Language      | Product name  | Notes         |
|:--------------------|:--------------|:--------------|:--------------|
| Default             | "en-us"       | Dill Pickle   | Default value |
| Website1            | "use default" |               |               |
| Website1:Storeview1 | "use default" |               |               |
| Website1:Storeview2 | "fr-ca"       |               | No data for this language is provided so the default value will be used |
| Website2            | "de-de"       |               | Product data is never set at the website leve |
| Website2:Storeview3 | "it-it"       | sottaceto     |               |
| Website2:Storeview4 | "en-us"       |               | Falls back to the default value as the languages match and no translated data is set in an intermediate scope |
| Website2:Storeview5 | "use website" | Dillgurke     | Language config is set at website level but actual product data is saved at the store view |
| Website2:Storeview6 | "zh-cn"       | 泡菜           |               |

Scopes with values have data set in that scope. Empty "Product Name" fields fall back through parent scopes.

##### Scope Notes

1. If the language of a store view is the same as the default language, and the value of an attribute changes at that upper scope, then any attribute value already set at the inner scope should be removed, and it should be set to fall back as well. This is what happened to Storeview1 and Storeview4.
1. The processor can only apply values at the lowest scope of the attribute itself. Thus, if "Name" were a global attribute, only the "en-us" value would be applicable because the language at the default scope is "en-us".

### Images

The Magento Retail Order Management Extension does not currently support importing images.

Please see the [Product Image Export](/src/app/code/community/EbayEnterprise/ProductImageExport/README.md) documentation for details on exporting image metadata to the Retail Order Management Product Hub.

## Custom Product Import Mapping

The product import process uses attributes maps defined in configuration XML in a manner similar to how Magento defines event observers:

```xml
<ebayenterprise_catalog_feed_attribute_mappings>
    <mage_attribute_code> <!-- The attribute code for this attribute in Magento -->
        <class>ebayenterprise_catalog/map</class> <!-- Magento object factory string -->
        <type>(disabled|model|helper|singleton)</type> <!-- Type of object factory to use -->
        <method>takeAction</method> <!-- Any public method -->
        <xpath>Relative/Path/To/Node</xpath> <!-- Relative to the current item or content node -->
    </mage_attribute_code>
    ...
</ebayenterprise_catalog_feed_attribute_mappings>
```

The method mapped by the configuration will be invoked when the XML node list matched by the XPath for an instance of the imported product. The methods should return the value to be set to the product attribute matching the attribute code the mapping is for.

The XPath expressions are evaluated relative the the XML node representing a single product in the feed. For example, the `/ItemMaster/Item` node or `/ContentMaster/Content` node.

Predefined maps that were [described above](#product-hub-feed-processing) can be found in [`app/code/community/EbayEnterprise/ProductImport/etc/config.xml`](/src/app/code/community/EbayEnterprise/ProductImport/etc/config.xml). There are some additional example maps in [`app/etc/local/productimport.xml.sample`](/src/app/etc/productimport.xml.sample) at `config/default/ebayenterprise_catalog/feed_attribute_mappings`.

| Important |
|:----------|
| The product import process will not generate new Magento product attributes. Any mapped product attribute must already exist in Magento as part of the attribute set for the imported product. |

### Predefined Map Methods

The following methods are provided by `EbayEnterprise_Catalog_Helper_Map` to cover a majority of import scenarios:

| Method               | Returns                                     |
|:---------------------|:--------------------------------------------|
| `extractStringValue` | String value of the first matched XML node. |
| `extractBoolValue`   | Bool value of the first matched XML node.   |
| `extractIntValue`    | Int value of the first matched XML node.    |
| `extractFloatValue`  | Float value of the first matched XML node.  |
| `passThrough`        | Value the method is called with.            |
| `extractSkuValue`    | String value of the normalized SKU of the first matched XML node. |
| `extractCustomAttributes` | Loop through all custom attributes and set the product with the custom attribute name and value. |

### Wild Card Custom Attribute Map

A "wild card" map is included in [`app/etc/productimport.xml.sample`](/src/app/etc/productimport.xml.sample) as `custom_attributes`. This map will attempt to import all custom attributes in either the Item Master or Content Master feed to a Magento Product Attribute with an Attribute Code that matches the Custom Attribute `name` exactly. This map will attempt to pass the value as a string.

| Important |
|:----------|
| Please note that Magento saves the index value of the Drop Down or MultiSelect option of the Product Attribute in the EAV table. Thus any imported value must either match that index value or use a custom map that explicitly maps to the string values of the Product Attribute Options. |

### Examples

An example that maps `IsDropShipped` from `BaseAttributes` to the `is_drop_shipped` "Yes/No" attribute in Magento:

```xml
<is_drop_shipped>
    <class>ebayenterprise_catalog/map</class>
    <type>helper</type>
    <method>extractBoolValue</method>
    <xpath>BaseAttributes/IsDropShipped</xpath>
</is_drop_shipped>
```

XPath has a lot of power for finding nodes and even transforming them itself. Much of the logic can be driven in the XPath expression. For example, a standard custom attribute map might look like:

```xml
<my_custom_attribute>
    <class>ebayenterprise_catalog/map</class>
    <type>helper</type>
    <method>extractStingValue</method>
    <xpath>CustomAttributes/Attribute/[@name="my_custom_attribute"]/Value</xpath>
</my_custom_attribute>
```

## Dependencies

### Magento Modules


- EbayEnterprise_Catalog

### Other Dependencies

- TBD

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.
