# Product Export

When Magento is to be the system of record for product information (except for inventory management), the extension enables Magento to be configured to export product data as XML files.

## Contents

- [Enabling Product Export](#enabling-product-export)
- [Product Attribute Mappings](#product-attribute-mappings)
- [Product Attribute Requirements](#product-attribute-requirements)
- [Product Export Mapping](#product-export-mapping)
- [Attributes Provided by the eBay Enterprise Retail Order Management Extension](#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension)
- [Language](#language)
- [Exporting Configurable Products](#exporting-configurable-products)
- [Images](#images)

## Enabling Product Export

1. Ensure the eBay Enterprise Retail Order Management Extension is configured. See the [Installation and Configuration Guide](INSTALL.md) for more details.
1. Copy `app/etc/productexport.xml.sample` to `app/etc/productexport.xml` to enable the local XML configuration.
1. Ensure cron jobs are set to run on the Magento instance.

### Local XML Configuration

The local XML configuration for product export provides settings for:

- **Cron scheduling**: This controls how often the feeds are generated. The value provided is for feeds to be generated every 15 minutes.
- **Product attribute mappings**: All [Product Attribute Mappings](#product-attribute-mappings) included in the export feeds are located in the local XML configuration file.

## Product Attribute Mappings

The following table describes how Magento product attribute are exported to elements in the XML product feeds. The eBay Enterprise Retail Order Management Extension provides these mappings. Any additional product attributes or elements in the XML that are not included below can be mapped and exported to meet the specific needs of a Magento store. See [Product Export Mapping](#product-export-mapping) for more details on mapping additional elements.

Some values in the _Magento Attribute Code_ column are not truly product attribute codes, but are necessary to include data from some other part of Magento, such as configuration values. Those values are indicated by a starting underscore (`_`).

*All XPath expressions are relative to the repeating XML node representing a single product in the feed, e.g. `Item` in ItemMaster or `Content` in ContentMaster.*

<table>
	<thead>
		<tr>
			<th>Magento Attribute Code</th>
			<th>XPath</th>
			<th>Description</th>
			<th>Lang Support</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th colspan="4">ItemMaster</th>
		</tr>
		<tr>
			<td>_gsi_client_id</td>
			<td>@gsi_client_id</td>
			<td>The Client Id configured for the website product data is being exported for.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_operation_type</td>
			<td>@operation_type</td>
			<td>One of <code>Add</code> or <code>Update</code>. The first time a product is exported, it will have an operation type of <code>Add</code>. Later exports of the product will have an operation type of <code>Update</code>.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_catalog_id</td>
			<td>@catalog_id</td>
			<td>The Catalog Id configured for the Magento instance.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>sku</td>
			<td>ItemId/ClientItemId</td>
			<td>Product SKU. Must be less than 15 characters to export.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>visibility</td>
			<td>BaseAttributes/CatalogClass</td>
			<td>A product visibility of "Not Visible Individually" will be included in the feed with a catalog class of <code>nosale</code>. Any other visibility setting will be included as <code>regular</code>.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>drop_shipped</td>
			<td>BaseAttributes/IsDropShipped</td>
			<td>Specifies if the item can be fulfilled using a drop shipper. Uses the value of the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Drop Shipped"</a> product attribute.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>name</td>
			<td>BaseAttributes/ItemDescription</td>
			<td>The "Name" attribute of the product.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>item_type</td>
			<td>BaseAttributes/ItemType</td>
			<td>Specifies the type of item in eBay Enterprise Retail Order Management. This value is not restricted by the feed schema but should be one of the values described under <a href="#ebay-enterprise-retail-order-management-item-types">eBay Enterprise Retail Order Management Item Types</a>.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>status</td>
			<td>BaseAttributes/ItemStatus</td>
			<td>Products that are "Disabled" will have a value of <code>Inactive</code> in the feed. Products that are "Enabled" will have a value of <code>Active</code> in the feed.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>tax_code</td>
			<td>BaseAttributes/TaxCode</td>
			<td>The eBay Enterprise assigned tax group. Uses the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Tax Code"</a> product attribute. Note that this is different from the "Tax Class" in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_hierarchy</td>
			<td>BaseAttributes/Hierarchy/</td>
			<td>The merchandise structure for the item. This mapping is responsible for exporting all of the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">hierarchy department and class attributes</a>.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>drop_ship_supplier_name</td>
			<td>DropShipSupplierInformation/SupplierName</td>
			<td>Name of the drop shipper fulfilling the item. Only required when the product can be fulfilled by a drop shipper.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>drop_ship_supplier_number</td>
			<td>DropShipSupplierInformation/SupplierNumber</td>
			<td>eBay Enterprise assigned code for the drop ship supplier. Only required when the product can be fulfilled by a drop shipper.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>drop_ship_supplier_part_number</td>
			<td>DropShipSupplierInformation/SupplierPartNumber</td>
			<td>The id or SKU used by the drop ship supplier to identify the item. Only required when the product can be fulfilled by a drop shipper.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>gift_message_available</td>
			<td>ExtendedAttributes/AllowGiftMessage</td>
			<td>Specifies if the customer can add gift messages to the item. "Yes" will allow messages to be applied. "No" will disallow gift messages for the item.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_color_code</td>
			<td>ExtendedAttributes/ColorAttributes/Color/Code</td>
			<td>When a product has a color option, this will use the "admin" label for the color option. This value should uniquely identify the color option.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_color_description</td>
			<td>ExtendedAttributes/ColorAttributes/Color/Description</td>
			<td>When a product has a color option, <code>Description</code> XML nodes will be added for each store view specific color option label. The <code>Description</code> node will include an <code>xml:lang</code> attribute containing the "Store Language Code" of the store view the label applies to. These values should be the display name of the color option.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>country_of_manufacture</td>
			<td>ExtendedAttributes/CountryOfOrigin</td>
			<td>Country in which the goods were completely derived or manufactured. Should be the ISO 3166 two letter/alpha format.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_giftcard</td>
			<td>ExtendedAttributes/</td>
			<td>For gift cards, will include the <code>GiftCardFacing</code> element, set to the product's "Name" attribute and the <code>GiftCardTenderCode</code> to the appropriate eBay Enterprise Retail Order Management tender code for the type of gift card.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>manufacturer</td>
			<td>ExtendedAttributes/Manufacturer/Name</td>
			<td>Company name of the manufacturer of the item.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>manage_stock</td>
			<td>ExtendedAttributes/SalesClass</td>
			<td>Determines product display behavior based on inventory availability. Items set to have "Managed Stock" will be given a sales class of <code>stock</code>, tying display to inventory availability. Items set to not have "Managed Stock" will be given a sales class of <code>advanceOrderOpen</code>, making display independent of inventory availability.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_style</td>
			<td>ExtendedAttributes/Style/</td>
			<td>
				<p>Relates a specific variant of a product, a simple product in Magento, to a general style of product, a configurable product in Magento. Any simple product that is used by a configurable product will have <code>StyleID</code> and <code>StyleDescription</code> XML nodes with values of the "SKU" and "Name" respectively of the parent configurable product. Any other product will simply use its own "SKU" and "Name" for the <code>StyleID</code> and <code>StyleDescription</code>.</p>

				<p><em>Note that eBay Enterprise Retail Order Management feed only supports simple products belonging to a single parent configurable product. If a simple product is used by more than one configurable product, only one of the relationships will be included in the feed. Which relationship will be included is not strictly defined.</em></p>
			</td>
			<td>No</td>
		</tr>
		<tr>
			<td>cost</td>
			<td>ExtendedAttributes/</td>
			<td>Creates the <code>UnitCost</code> element representing the cost per unit of measure for the merchandise. This value will be in the default currency of the Magento instance and will include a <code>currency_code</code> attribute indicating the three character ISO-4217 currency code the price is in.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>item_url</td>
			<td>EB2C/ItemURLs/</td>
			<td>Will contain an <code>ItemURL</code> node with a value of the product page URL for the product in the default store view. The <code>ItemURL</code> XML node will include a <code>type</code> attribute set to <code>webstore</code> indicating the URL points to the Magento webstore.</td>
			<td>No</td>
		</tr>
		<tr>
			<th colspan="4">ContentMaster</th>
		</tr>
		<tr>
			<td>_gsi_client_id</td>
			<td>@gsi_client_id</td>
			<td>The Client Id configured for the website product data is being exported for.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_catalog_id</td>
			<td>@catalog_id</td>
			<td>The Catalog Id configured for the Magento instance.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>sku</td>
			<td>UniqueID</td>
			<td>Product SKU. Must be less than 15 characters to export.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_style</td>
			<td>ExtendedAttributes/Style/</td>
			<td>
				<p>Relates a specific variant of a product, a simple product in Magento, to a general style of product, a configurable product in Magento. Any simple product that is used by a configurable product will have <code>StyleID</code> XML node with a value of the "SKU" of the parent configurable product. Any configurable product will simply use its own SKU as the <code>StyleID</code> value.</p>

				<p><em>Note that eBay Enterprise Retail Order Management feed only supports simple products belonging to a single parent configurable product. If a simple product is used by more than one configurable product, only one of the relationships will be included in the feed. Which relationship will be included is not strictly defined.</em></p>
			</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_product_links</td>
			<td>ProductLinks</td>
			<td>
				Specifies links between products for Related Products, Up-sells, and Cross-sells. Each product relation will create a new <code>ProductLink</code> XML element. These elements will include a <code>LinkToUniqueID</code> XML element containing the SKU of the product the link is to and a <code>link_type</code> attribute indicating the type of link. Magento product relationships are mapped to eBay Enterprise Retail Order Management link types as follows:

				<table>
					<thead>
						<tr>
							<th>Magento Link Type</th><th>Feed Link Type</th>
						</tr>
					</thead>
					<tbody>
						<tr><td>Related</td><td>ES_Accessory</td></tr>
						<tr><td>Cross-Sells</td><td>ES_CrossSelling</td></tr>
						<tr><td>Up-Sells</td><td>ES_UpSelling</td></tr>
					</tbody>
				</table>

			</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_category_links</td>
			<td>CategoryLinks</td>
			<td>Specifies categories the product is displayed in. Category hierarchies are represented as dash (<code>-</code>) separated lists of category names. Category links from Magento will always be given an <code>import_mode</code> of "Replace."</td>
			<td>No</td>
		</tr>
		<tr>
			<td>name</td>
			<td>BaseAttributes/Title</td>
			<td>The "Name" of the product. One <code>Title</code> XML element will be included per translation of the product attribute. See the <a href="#language">Language</a> section below for more information on how the extension handles translations.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>_color_code</td>
			<td>ExtendedAttributes/ColorAttributes/Color/Code</td>
			<td>When a product has a color option, this will use the "admin" label for the color option. This value should uniquely identify the color option.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_color_description</td>
			<td>ExtendedAttributes/ColorAttributes/Color/Description</td>
			<td>When a product has a color option, <code>Description</code> XML nodes will be added for each store view specific color option label. The <code>Description</code> node will include an <code>xml:lang</code> attribute containing the "Store Language Code" of the store view the label applies to. These values should be the display name of the color option.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>country_of_manufacture</td>
			<td>ExtendedAttributes/DisplayCountryOfOrigin</td>
			<td>Country in which the goods were completely manufactured. This value is used for display purposes only. The product export currently uses the two character country code.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>gift_wrapping_available</td>
			<td>ExtendedAttributes/GiftWrap</td>
			<td>Specifies if gift wrapping is available for the product. When allowed, will contain a value of <code>Y</code>. When not allowed, a value of <code>N</code> will be used. When the product is set to "Use Config Settings" the value of the System -> Configuration -> Sales -> Gift Options -> Allow Gift Wrapping for Order Items configuration value.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>description</td>
			<td>ExtendedAttributes/LongDescription</td>
			<td>The long description of the item.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>meta_keyword</td>
			<td>ExtendedAttributes/SearchKeywords</td>
			<td>Search terms associated with the item.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>short_description</td>
			<td>ExtendedAttributes/ShortDescription</td>
			<td>Short description of the item.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>_giftcard</td>
			<td>ExtendedAttributes/GiftCard</td>
			<td>Disabled.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>news_from_date</td>
			<td>CustomAttributes/Attribute[@name="news_from_date"]</td>
			<td>A custom attribute containing when the item should be considered new from.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>news_to_date</td>
			<td>CustomAttributes/Attribute[@name="news_to_date"]</td>
			<td>A custom attribute containing when the item should be considered new until.</td>
			<td>No</td>
		</tr>
		<tr>
			<th colspan="4">Prices</th>
		</tr>
		<tr>
			<td>_gsi_client_id</td>
			<td>@gsi_client_id</td>
			<td>The Client Id configured for the website product data is being exported for.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_catalog_id</td>
			<td>@catalog_id</td>
			<td>The Catalog Id configured for the Magento instance.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_gsi_store_id</td>
			<td>@gsi_store_id</td>
			<td>The Store Id configured for the website product data is being exported for.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>sku</td>
			<td>ClientItemId</td>
			<td>The item SKU. Must be less than 15 characters to export.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_price_event_number</td>
			<td>Event/EventNumber</td>
			<td>When the product has a special price, this XML element will be included with an event number based on the special from and to dates - <code>{special_from_date}-{special_to_date}</code>.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>price</td>
			<td>Event/Price</td>
			<td>The "IS" price for the time/date range. This is the price that will be used for price calculations. When the product includes a "Special Price," that value will be used as the value of the <code>Event/Price</code> element. When there is no "Special Price" for the product, the "Price" attribute value will be used.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>msrp</td>
			<td>Event/MSRP</td>
			<td>Uses the product "MSRP" attribute. This value will **never** be used as the selling price.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>alternate_price1</td>
			<td>Event/AlternatePrice1</td>
			<td>The "WAS" price for "WAS/IS" price models. This value will **never** be used as the selling price. When the product has a "Special Price" set, this will be set to the product's "Price" attribute. When the product does not have a "Special Price" set, this XML element will not be included in the feed.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>special_from_date</td>
			<td>Event/StartDate</td>
			<td>The date and time the price event will become active. When not present, the price event will become active once the feed is processed.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>special_to_date</td>
			<td>Event/EndDate</td>
			<td>The date and time the price event will be active until. This value *must* be after the Start Date. When not present, the a default value of "2500-12-31" will be assumed by the receiving system.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>_price_vat_inclusive</td>
			<td>Event/PriceVatInclusive</td>
			<td>Set to "true" if the product price is VAT inclusive, "false" otherwise. Currently, this value will always be mapped to "false."</td>
			<td>No</td>
		</tr>
	</tbody>
</table>

## Product Attribute Requirements

The following product requirements must be met for a product to be exported from Magento to eBay Enterprise Retail Order Management.

| Product Attribute         | Requirement |
| ------------------------- | ----------- |
| SKU                       | Must be set and less than 15 characters. |
| Hierarchy Class Number    | Non-empty value. Must be provided by eBay Enterprise. |
| Hierarchy Dept Number     | Non-empty value. Must be provided by eBay Enterprise. |
| Hierarchy Subclass Number | Non-empty value. Must be provided by eBay Enterprise. |
| Hierarchy Subdept Number  | Non-empty value. Must be provided by eBay Enterprise. |
| Tax Code                  | Non-empty value. Must be provided by eBay Enterprise. |

## Product Export Mapping

Mappings are located in the `app/etc` directory of the Magento root in an XML configuration file named `productexport.xml.sample`. This file must be copied or renamed to `productexport.xml` in order for exports to function.

**WARNING:** Be careful when modifying this file. Invalid Changes can cause schema validation errors which may prevent feed files from being generated.

The basic structure of the mapping configuration is as follows:

```xml
<config>
	<default>
		<eb2cproduct>
			<feed_pim_mapping>
				<feed_mapping_name> <!-- A unique name for the mapping.
										 In the sample, we use item_map for ItemMaster and
										 content_map for ContentMaster, etc
									-->
					<mappings>
						... <!-- individual attribute code configuration goes here -->
					</mappings>
				<feed_mapping_name>
			</feed_pim_mapping>
		</eb2cproduct>
	</default>
</config>
```

### Attribute Code Configuration

Attribute codes in Magento are mapped using an ordered list of XML structures that relate a Magento product attribute code to a method. The method should transform the data into an appropriate XML representation. The result is added as a child of the element specified in the config. The method declarations are similar to Magento's observer configuration.

```xml
<mage_attribute_code> <!-- The attribute code for an attribute in Magento -->
	<class>eb2cproduct/pim</class> <!-- Magento object factory string -->
	<type>(disabled|model|helper|singleton)</type> <!-- Type of object factory to use -->
	<method>takeAction</method> <!-- Any public method -->
	<xml_dest>Explicit/Relative/Path/To/Node</xml_dest> <!-- Subset-of-XPath expression describing how to create the destination node. -->
	<translate>(0|1)</translate> <!-- Whether the resultant XML can contain more than one node differentiated by a `xml:lang` attribute or only a single node with no `xml:lang` -->
</mage_attribute_code>
```

If `type` is `disabled`, the attribute will not be built into the outbound file.

The `xml_dest` specifies the element that will be the parent of the transformed data. Its value is a string that takes its syntax from XPath. The path must unambiguously describe a single destination node and consist of elements separated by slashes.

A fatal exception will be thrown in the following cases:

1. Ambiguous `xml_dest` paths
1. Paths with leading slashes
1. Paths starting with `..`

The element referenced by the final part of the path will always be created as a new element even if an element with the same tag name exists. A trailing '/' at the end of the path overrides this feature and instead attaches the method results as the children of the existing element.

Note: no nodes are overwritten.

The first element of the `xml_dest` path is expected to be one of BaseAttributes, ExtendedAttributes or CustomAttributes, but it is not explicitly forbidden for it to be something else. Please refer to the XML schema for more information.

There is limited support for element attributes using a subset of XPath predicate syntax. This is largely useful when adding mappings to CustomAttributes in the feeds.

For example, the path `CustomAttributes/Attribute[@name="attrvalue"][@operation_type="Add"]` will result in the following XML output:

```xml
<CustomAttributes>
	<Attribute name="attrvalue" operation_type="Add">
		<!-- result of method -->
	</Attribute>
</CustomAttributes>
```

### Special Attribute Configurations

There are cases where data is not stored as a product attribute or calculated from other sources. Prepending the "attribute code" element's tag name with an underscore will tell the export module to not attempt to retrieve the value from the product, but still execute the method. The result, if any, will be added to the document.

```xml
<_name_of_attribute_or_concept> <!-- prepend the XML tag name with an "_" -->
	<class>...</class>
	<type>(disabled|model|helper|singleton)</type>
	<method>...</method>
	<xml_dest>...</xml_dest>
	<translate>(0|1)</translate>
</_name_of_attribute_or_concept>
```

This is useful when arbitrary data should be in the document but is not a product attribute.

Attributes that are not specified in the XML schema can be added as custom attributes. This example demonstrates usage of the builtin method for handling custom attributes.

```xml
<name_of_attribute>
	<class>eb2cproduct/pim</class>
	<type>helper</type>
	<method>getValueAsDefault</method>
	<xml_dest>CustomAttributes/Attribute[@name="name_of_attribute"]</xml_dest>
	<translate>1</translate>
</name_of_attribute>
```

As long as the resultant XML validates according to the schema (see the `xsd` subdirectory of the Eb2cCore module), you can customize this mapping however you like, including writing your own mapping methods.

### Built-in methods

The eb2cproduct/pim helper has predefined passthrough methods for the following data types:

- passYesNoToBool - convert "Yes/No" product attributes to text node containing the boolean representation
- passString - produces a text node containing the string value of the product attribute
- passInteger - produces a text node containing the integer value of the product attribute
- passDecimal - produces a text node containing the float value of the product attribute

### Examples

A simple example mapping a Yes/No attribute with the code of drop_shippable to the IsDropShipped BaseAttribute would look like:

```xml
<drop_shippable>
	<class>eb2cproduct/pim</class>
	<type>helper</type>
	<method>passYesNoToBool</method>
	<xpath>BaseAttributes/IsDropShipped</xpath>
</drop_shippable>
```

## Attributes Provided by the eBay Enterprise Retail Order Management Extension

The following attributes have are created by the eBay Enterprise Retail Order Management Extension and added to the "Default" attribute set in Magento and should be included in every product attribute set in the Magento instance.

| Attribute Name | Attribute Code | Description |
|----------------|----------------|-------------|
| Size | size | Product size. |
| Style Id | style_id | Relates simple products to configurable products. A simple product's Style Id relates to the configurable product's SKU. |
| Is Clean | is_clean | Flag indicating if the product has had all of its product links resolved. |
| Unresolved Product Links | unresolved_product_links | Any related, cross-sell or up-sell product links for the product that have not yet been created, typically due to the target products not existing in Magento yet. |
| HTS Codes | hts_codes | Mapping of tax codes used for calculating international taxes and duties. |
| Tax Code | tax_code | eBay Enterprise assigned tax group. |
| Item Type | item_type | Item specification used by eBay Enterprise Retail Order Management. This value should be one of the values described under [eBay Enterprise Retail Order Management Item Types](#ebay-enterprise-retail-order-management-item-types). |
| Drop Shipped | drop_shipped | Specifies if the item can be fulfilled using a drop shipper.  |
| Drop Ship Supplier Name | drop_ship_supplier_name | Name of the drop ship supplier fulfilling the product. |
| Drop Ship Supplier Number | drop_ship_supplier_number | eBay Enterprise assigned code for the drop ship supplier. |
| Drop Ship Supplier Part Number | drop_ship_supplier_part_number | Id or SKU used by the drop ship supplier to identify the item. |
| Hierarchy Department Number | hierarchy_dept_number | Hierarchy Level 1 number. |
| Hierarchy Department Description | hierarchy_dept_description | Hierarchy Level 1 description. |
| Hierarchy Subdepartment Number | hierarchy_subdept_number | Hierarchy Level 2 number. |
| Hierarchy Subdepartment Description | hierarchy_subdept_description | Hierarchy Level 2 description. |
| Hierarchy Class Number | hierarchy_class_number | Hierarchy Level 3 number. |
| Hierarchy Class Description | hierarchy_class_description | Hierarchy Level 3 description. |
| Hierarchy Subclass Number | hierarchy_subclass_number | Hierarchy Level 4 number. |
| Hierarchy Subclass Description | hierarchy_subclass_description | Hierarchy Level 4 description. |

### eBay Enterprise Retail Order Management Item Types

The following item types are supported by eBay Enterprise Retail Order Management. The Magento "Item Type" product attribute added by the eBay Enterprise Retail Order Management Extension must be one of the following when exporting products.

<table>
	<thead>
		<tr>
			<th>Type</th><th>Description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Custom</td><td>Customization SKUs.  A virtual item that represents a charge to the consumer to customize an item as well as a description of the customization options selected by the customer.</td>
		</tr>
		<tr>
			<td>Donation</td>
			<td>Donation SKU. A virtual item that represents a consumer's donation to a charitable cause.</td>
		</tr>
		<tr>
			<td>GC</td>
			<td>Physical Gift Cards.</td>
		</tr>
		<tr>
			<td>GiftMessage</td>
			<td>This item is a Gift Message.</td>
		</tr>
		<tr>
			<td>GiftWrap</td>
			<td>Physical Gift Wrap.</td>
		</tr>
		<tr>
			<td>Merch</td>
			<td>A typical physically-fulfilled item.</td>
		</tr>
		<tr>
			<td>OGC</td>
			<td>Online Gift Certificates that are hosted/processed by eBay Enterprise. They are our in-house product for gift certificates that do not have a physical card associated with them (only a payment account number and PIN).</td>
		</tr>
		<tr>
			<td>PostSaleFulfillment</td>
			<td>Post-sale fulfillment SKU. A virtual item that needs to be classified separately from other virtual sku types such as Donation or Warranty SKUs.</td>
		</tr>
		<tr>
			<td>RefundOGC</td>
			<td>Refund online Gift Certificate. 'Refund' used for accounting purposes to differentiate Refunds from the original tender.</td>
		</tr>
		<tr>
			<td>RefundOGCForCOD</td>
			<td>Refund online gift certificate issued against a COD order.</td>
		</tr>
		<tr>
			<td>RefundOGCForCVS</td>
			<td>Refund online gift certificate issued against a CVS order.</td>
		</tr>
		<tr>
			<td>RefundVGC</td>
			<td>Refund Virtual Gift Cards. 'Refund' used for accounting purposes to differentiate Refunds from the original tender.</td>
		</tr>
		<tr>
			<td>VGC</td>
			<td>Functionally similar to OGC’s except they are processed by a 3rd-party gift-card provider (ex: SVS or ValueLink).</td>
		</tr>
		<tr>
			<td>Warranty</td>
			<td>Product warranty. A virtual item representing a warranty purchased for another physical item on the order.</td>
		</tr>
		<tr>
			<td>WarrantyDep</td>
			<td>This item is a dependent warranty. May only be purchased with another item. Is not included in search results.</td>
		</tr>
		<tr>
			<td>WarrantyAloneAndDep</td>
			<td>Warranty item may be sold independently or with the purchase of another item. Is included in search results.</td>
		</tr>
		<tr>
			<td>InstallSvcDep</td>
			<td>This item is a type of installation service that maybe purchase with another item. When searching this item is not returned in the search results. This item may not be purchased alone.</td>
		</tr>
		<tr>
			<td>InstallSvcAloneAndDep</td>
			<td>this item is a type of installation service that maybe purchased independently or with another item. When searching this item may be in the results.</td>
		</tr>
		<tr>
		</tr>
	</tbody>
</table>

## Language

In eBay Enterprise Retail Order Management terms, some attribute values can vary on language. In Magento terms this means retrieve the different values for these nodes from stores, if such stores exist. (For these purposes we do not distinguish between a "store" and a "store view".) If the config.xml `translate` element is set to 0, the resultant XML should contain a single node with no `xml:lang` attribute.`

### Where to find language in the feeds

Feeds denote language according to one of two possible structures, either:

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

### What to do with language-specific attribute values

Mappings with a `translate` element set to `1` will be included in the feed multiple times, once for each store view containing a unique "Store Language Code" and attribute value combination.

For example, assume you have a Magento instance set up the following way:

| Scope               | Language      | Product name  |
| ------------------- | ------------- | ------------- |
| Default             | "en-us"       | Pickle        |
| Website1            | "use default" |               |
| Website1:Storeview1 | "use default" | "use default" |
| Website1:Storeview2 | "fr-ca"       | pétrin        |
| Website2            | "de-de"       |               |
| Website2:Storeview3 | "it-it"       | sottaceto     |
| Website2:Storeview4 | "en-us"       | Dill Pickle   |
| Website2:Storeview5 | "use website" | Essiggurke    |
| Website2:Storeview6 | "zh-cn"       | "use default" |

And assuming this configuration:

```xml
...
<mappings>
	<name>
		<class>eb2cproduct/pim</class>
		<type>helper</type>
		<method>passString</method>
		<xml_dest>BaseAttributes/Title</xml_dest>
		<translate>1</translate>
	</name>
	...
</mappings>
```

The resultant XML would look like:

```xml
<BaseAttributes>
	<Title xml:lang="en-us">Pickle</Title>
	<Title xml:lang="fr-ca">pétrin</Title>
	<Title xml:lang="it-it">sottaceto</Title>
	<Title xml:lang="en-us">Dill Pickle</Title>
	<Title xml:lang="de-de">Essiggurke</Title>
	<Title xml:lang="zh-cn">Pickle</Title>
	...
</BaseAttributes>
```

If two store views with the same Client Id, store code and language have different values for an attribute, both values will be included in the feed with the same language:

```xml
<Foo xml:lang="en-us">Fish</Foo>
<Foo xml:lang="en-us">Osteichthyes</Foo>
```

This is harmless and eBay Enterprise Retail Order Management will normalize the input.

## Exporting Configurable Products

Configurable products will be included in all of the product feeds from Magento. Configurable products will be related to used simple products via the Style Id. Simple products being used by a configurable product will have a Style Id matching the SKU of the configurable product to indicate the relationship. Configurable products will be given a Style Id matching the product's SKU to indicate that it is a parent product.

_Note that in the product export feeds, simple products may only be related to a single configurable product via the Style Id. For this reason, eBay Enterprise Retail Order Management does not support simple products being associated with multiple configurable products. In such scenarios, the product export should not crash but the exact behavior of simple products associated with more than one configurable product is not strictly defined._

## Images

Please see [Image Feeds](IMAGE_EXPORT.md) documentation for details on exporting images to eBay Enterprise Retail Order Management. Image export is required to make images available services such as eBay Enterprise Marketing Solutions Email and marketplace integrations.
