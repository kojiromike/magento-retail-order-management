# Product Import

## Contents

- [Enabling Product Import ](#enabling-product-import)
- [Product Attribute Mappings](#product-attribute-mappings)
- [Required Custom Attributes](#required-custom-attributes)
- [Import Products into Different Magento Websites ](#import-products-into-different-magento-websites)
- [Product Import Mapping](#product-import-mapping)
- [Dependency and Non-Dependency Attributes](#dependency-and-non-dependency-attributes)
- [Language](#language)
- [Importing Configurable Products](#importing-configurable-products)
- [Importing Gift Cards](#importing-gift-cards)
- [Images](#images)

## Enabling Product Import

1. Ensure the eBay Enterprise Retail Order Management Extension is configured. See the [Installation and Configuration Guide](INSTALL.md) for more details.
1. Copy `app/etc/productimport.xml.sample` to `app/etc/productimport.xml` to enable the local XML configuration.
1. Ensure cron jobs are set to run on the Magento instance.

### Local XML Configuration

The local XML configuration for product import provides settings for:

- **Cron scheduling**: Set to run every 15 minutes by default
- **SFTP remote directory paths and file patterns**: These values will be provided by eBay Enterprise
- **Product attribute mappings**: See [Product Attribute Mappings](#product-attribute-mappings) for details on mapping feed data to product attributes

## Product Attribute Mappings

The following table describes how elements in the XML product feeds are imported and used by Magento. The eBay Enterprise Retail Order Management Extension provides these mappings. Any additional elements in the XML that are not included below can be mapped and imported to meet the specific needs of a Magento store. See [Product Import Mapping](#product-import-mapping) for more details on mapping additional elements.

*All XPath expressions are relative to the repeating XML node representing a single product in the feed, e.g. `Item` in ItemMaster or `Content` in ContentMaster.*

<table>
	<thead>
		<tr>
			<th>XPath</th>
			<th>Description</th>
			<th>Lang Support</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th colspan="3">ItemMaster</th>
		</tr>
		<tr>
			<td>@operation_type</td>
			<td>One of <code>Add</code> or <code>Change</code>. When importing into Magento, both operation types will either add a new product or update an existing product.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>@gsi_client_id</td>
			<td>Client ID provided by eBay Enterprise and configured for the website. Products will only be imported to websites with a matching Client ID. See <a href="#import-products-into-different-magento-websites">Import Products into Different Magento Websites</a> for more details on matching products to websites.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>@catalog_id</td>
			<td>Catalog ID provided by eBay Enterprise and configured for the Magento instance. Only items with a Catalog ID matching the value configured in for the Magento instance will be imported. </td>
			<td>No</td>
		</tr>
		<tr>
			<td>ItemId/ClientItemId</td>
			<td>SKU of the product in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>BaseAttributes/CatalogClass</td>
			<td>Controls the "Visibility" of the product. Values of <code>regular</code> and <code>always</code> result in products with a visibility of "Catalog, Search". A values of <code>nosale</code> will be given a visibility of "Not Visible Individually."</td>
			<td>No</td>
		</tr>
		<tr>
			<td>BaseAttributes/IsDropShipped</td>
			<td>Provides the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Drop Shipped"</a> product attribute.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>BaseAttributes/ItemType</td>
			<td>Provides the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Item Type"</a> product attribute.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>BaseAttributes/ItemStatus</td>
			<td>Sets the "Status" of the item. May be one of: <code>Active</code>, <code>Discontinued</code>, or <code>Inactive</code>. A Value of <code>Active</code> will result in a product that is "Enabled" in Magento. Both <code>Discontinued</code> and <code>Inactive</code> will result in a product that is "Disabled" in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>BaseAttributes/TaxCode</td>
			<td>Provides the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Tax Code"</a> product attribute used in the tax duty request. Note that this is separate from the "Tax Class" in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>DropShipSupplierInformation/SupplierName</td>
			<td>Provides the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Drop Ship Supplier Name"</a> product attribute. This value is only needed when the item is being fulfilled by a drop ship supplier.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>DropShipSupplierInformation/SupplierNumber</td>
			<td>Provides the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Drop Ship Supplier Number"</a> product attribute. This value is only needed when the item is being fulfilled by a drop ship supplier.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>DropShipSupplierInformation/SupplierPartNumber</td>
			<td>Provides the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Drop Ship Supplier Part Number"</a> product attribute. This value is only needed when the item is being fulfilled by a drop ship supplier.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/AllowGiftMessage</td>
			<td>Sets the "Allow Message" attribute for gift cards.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/ColorAttributes/Color</td>
			<td>
Used to specify the "Color" attribute of the product.
Consists of at least 2 child nodes:
<ul>
<li>A color "Code", a unique value used to identify the color. This will be used as the admin label for the color option. When colors are being imported, if a color option with an admin label matching the code already exists, that color option will be reused for the product. When a new "Code" is encountered, a new option will be created for the color.
</li>
<li>One or more "Description" nodes, each with an "xml:lang" attribute. The description is used for to all stores that use the language specified.
</li></td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/ColorAttributes/Color/Description</td>
			<td>The localized name of the color. This value will be used as the store view specific label for color option and will be applied to any store views that are configured with a eBay Enterprise Retail Order Management Store Language Code matching the <code>xml:lang</code> attribute of this node.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/CountryOfOrigin</td>
			<td>Specifies the "Country of Manufacture" product attribute. This value should be the two character ISO 3166 country code.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/GiftCardTenderCode</td>
			<td>
				Specifies the type of gift card. Within Magento, this value specifies the gift card's "Card Type" attribute. The following mapping between feed values and Magento types is used:
				<table>
					<thead>
						<tr>
							<th>Feed Value</th>
							<th>Magento Type</th>
						</tr>
					</thead>
					<tbody>
						<tr><td>SD</td><td>virtual</td></tr>
						<tr><td>SP</td><td>physical</td></tr>
						<tr><td>ST</td><td>combined</td></tr>
						<tr><td>SV</td><td>virtual</td></tr>
						<tr><td>SX</td><td>combined</td></tr>
					</tbody>
				</table>
				This mapping can be customized by changing the <code>config/default/eb2ccore/feed/gift_card_tender_code</code> values in the <code>app/etc/rom.xml</code> file for the Magento store.
			</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/ItemDimension/Shipping/Mass</td>
			<td>The "Weight" product attribute uses the value of the <code>Weight</code> child node. The <code>unit_of_measure</code> attribute on the <code>Mass</code> node must be present and set to either <code>lbs</code> or <code>kg</code>. The <code>unit_of_measure</code> attribute value is not used for Magento but is required for other eBay Enterprise Retail Order Management systems.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/SalesClass</td>
			<td>
				Specifies the "Manage Stock" inventory setting for the product. The following mapping between valid sales classes and Magento "Managed Stock" settings.
				<table>
					<thead>
						<tr><th>Feed Value</th><th>Managed Stock Setting</th></tr>
					</thead>
					<tbody>
						<tr><td>stock</td><td>Yes</td></tr>
						<tr><td>advanceOrderOpen</td><td>No</td></tr>
						<tr><td>advanceOrderLimited</td><td>Yes</td></tr>
					</tbody>
				</table>
			</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/Style/StyleId</td>
			<td>This value sets the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Style ID"</a> product attribute. This attribute is used to related a simple product to a parent configurable product. The Style ID of the used simple product should match the SKU of the parent configurable product.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CustomAttributes</td>
			<td>Additional key/value pairs that may be included in the feed. While there is no mapping provided to import these values into Magento it may be necessary to include these in the feeds to provide additional values that are not already implemented in the feed. If these values are to be imported into Magento, <a href="#product-import-mapping">additional product mappings will need to be added</a>.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>CustomAttributes/Attribute[@name="ProductType"]/Value</td>
			<td>The Magento product type. Possible values include <code>bundle</code>, <code>configurable</code>, <code>downloadable</code>, <code>giftcard</code>, <code>grouped</code>, <code>simple</code> and <code>virtual</code>, but only <code>configurable</code>, <code>downloadable</code>, <code>simple</code> and <code>virtual</code> are supported.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CustomAttributes/Attribute[@name="ConfigurableAttributes"]/Value</td>
			<td>A comma-separated list of attributes on which the product can be configured. Required for any product having a ProductType of <code>Configurable</code>.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CustomAttributes/Attribute[@name="AttributeSet"]/Value</td>
			<td>Specifies the product attribute set to apply to to product. Should exactly match the name of the attribute set in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<th colspan="3">ContentMaster</th>
		</tr>
		<tr>
			<td>@gsi_client_id</td>
			<td>Client ID provided by eBay Enterprise and configured for the website. Products will only be imported to websites with a matching Client ID. See <a href="#import-products-into-different-magento-websites">Import Products into Different Magento Websites</a> for more details on matching products to websites.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>@catalog_id</td>
			<td>Catalog ID provided by eBay Enterprise and configured for the Magento instance. Only items with a Catalog ID matching the value configured in for the Magento instance will be imported. </td>
			<td>No</td>
		</tr>
		<tr>
			<td>@gsi_store_id</td>
			<td>Store ID provided by eBay Enterprise and configured for website. Product data will only be imported to websites with a matching Store ID. See <a href="#import-products-into-different-magento-websites">Import Products into Different Magento Websites</a> for more details on matching products to websites.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>UniqueId</td>
			<td>The product SKU.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>StyleId</td>
			<td>This value sets the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"Style ID"</a> product attribute. This attribute is used to related a simple product to a parent configurable product. The Style ID of the used simple product should match the SKU of the parent configurable product.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ProductLinks</td>
			<td>Encapsulates related, up-sell and cross-sell product links.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ProductLinks/ProductLink@link_type</td>
			<td>
				Specifies the type of link to create. The following link types will be imported by Magento:
				<table>
					<thead>
						<tr><th>Feed Value</th><th>Managed Product Link Type</th></tr>
					</thead>
					<tbody>
						<tr><td>ES_Accessory</td><td>Related</td></tr>
						<tr><td>ES_CrossSelling</td><td>Cross-Sell</td></tr>
						<tr><td>ES_UpSelling</td><td>Up-Sell</td></tr>
					</tbody>
				</table>
			</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ProductLinks/ProductLink@operation_type</td>
			<td>Specify what to do with the product link. May be <code>Add</code> to create new links or <code>Delete</code> to remove an existing link.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ProductLinks/ProductLink/LinkToUniqueId</td>
			<td>SKU of the product to create the link to.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CategoryLinks</td>
			<td>Encapsulates data used to link products to categories in Magento. Any category links present in the feeds will always replace any existing category links for the product. Links will only be created to categories that already exist within the Magento instance.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CategoryLinks/CategoryLink@catalog_id</td>
			<td>Should match the Catalog ID configured for the Magento instance.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CategoryLinks/CategoryLink@import_mode</td>
			<td>Specifies how the links should be handled. Links with an <code>operation_type</code> of "Delete" will not be imported. All other category links will be imported and replace any existing category links.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CategoryLinks/CategoryLink/Name</td>
			<td>
				<p>Specifies a category hierarchy indicating a category the product should be placed in. The each category should be delimited by a dash (<code>-</code>). The product will only be added to the last category specified by the hierarchy. The first category in each path must be a Root Category.</p>

				<p>For example, the following category links XML:

				<pre>
&lt;CategoryLinks&gt;
	&lt;CategoryLink import_mode="Update"&gt;
		&lt;Name&gt;Store Root-Women&lt;/Name&gt;
	&lt;/CategoryLink&gt;
	&lt;CategoryLink import_mode="Update"&gt;
		&lt;Name&gt;Store Root-Women-Shoes-Boots&lt;/Name&gt;
	&lt;/CategoryLink&gt;
&lt;/CategoryLinks&gt;
				</pre>

				will result in the product being linked to the "Women" and "Boots" categories but not the "Store Root" or "Shoes" category.</p>
			</td>
			<td>No</td>
		</tr>
		<tr>
			<td>BaseAttributes/Title</td>
			<td>The "Name" product attribute.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/ColorAttributes/Color/Code</td>
			<td>Used to specify the "Color" attribute of the product. The color "Code" should be a unique value used to identify the color and will be used as the admin label for the color option. When colors are being imported, if a color option with an admin label matching the code already exists, that color option will be reused for the product. When a new "code" is encountered, a new option will be created for the color.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/ColorAttributes/Color/Description</td>
			<td>The localized name of the color. This value will be used as the store view specific label for color option and will be applied to any store views that are configured with a eBay Enterprise Retail Order Management Store Language Code matching the <code>xml:lang</code> attribute of this node.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/LongDescription</td>
			<td>The "Description" product attribute.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/ShortDescription</td>
			<td>The "Short Description" product attribute.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>CustomAttributes</td>
			<td>Additional key/value pairs that may be included in the feed. While there is no mapping provided to import these values into Magento it may be necessary to include these in the feeds to provide additional values that are not already implemented in the feed. If these values are to be imported into Magento, <a href="#product-import-mapping">additional product mappings will need to be added</a>.</td>
			<td>Yes</td>
		</tr>
		<tr>
			<td>CustomAttributes/Attribute[@name="ProductType"]/Value</td>
			<td>The Magento product type. Possible values include <code>bundle</code>, <code>configurable</code>, <code>downloadable</code>, <code>giftcard</code>, <code>grouped</code>, <code>simple</code> and <code>virtual</code>, but only <code>configurable</code>, <code>downloadable</code>, <code>simple</code> and <code>virtual</code> are supported.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CustomAttributes/Attribute[@name="ConfigurableAttributes"]/Value</td>
			<td>A comma-separated list of attributes on which the product can be configured. Required for any product having a ProductType of <code>Configurable</code>.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>CustomAttributes/Attribute[@name="AttributeSet"]/Value</td>
			<td>Specifies the product attribute set to apply to to product. Should exactly match the name of the attribute set in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<th colspan="3">Prices</th>
		</tr>
		<tr>
			<td>@gsi_store_id</td>
			<td>Store ID provided by eBay Enterprise and configured for the Magento website. Products will only be imported to websites with a matching Store ID. See <a href="#import-products-into-different-magento-websites">Import Products into Different Magento Websites</a> for more details on matching products to websites.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>@gsi_client_id</td>
			<td>Client ID provided by eBay Enterprise and configured for the website. Products will only be imported to websites with a matching Client ID. See <a href="#import-products-into-different-magento-websites">Import Products into Different Magento Websites</a> for more details on matching products to websites.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>@catalog_id</td>
			<td>Catalog ID provided by eBay Enterprise and configured for the Magento instance. Only items with a Catalog ID matching the value configured in for the Magento instance will be imported. </td>
			<td>No</td>
		</tr>
		<tr>
			<td>ClientItemId</td>
			<td>The product SKU.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>Event</td>
			<td>Any new price events for a product in a feed will replace any existing product pricing data for the product "Price," "Special Price," "Special Price From Date," and "Special Price To Date."</td>
			<td></td>
		<tr>
			<td>Event/Price</td>
			<td>When an `Event/AlternatePrice1` value is not included for the product, this will be used as the "Price" product attribute and will be the price of the product on the site. When an `Event/AlternatePrice1` is included for the product, this will be used as the "Special Price" product attribute and will be the sale price of the product while the special price is available.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>Event/MSRP</td>
			<td>The "MSPR" of the product.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>Event/AlternatePrice1</td>
			<td>When present, this will be used as the "Price" product attribute. Due to handling of the `Event/Price` element, this will result in this value being used as the "WAS" price while the special price is available.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>Event/StartDate</td>
			<td>The "Special Price From Date" of the product. While this value is a full date time in the feed, only the date will be used whithin Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>Event/EndDate</td>
			<td>The "Special Price To Date" of the product. While this value is a full date time in the feed, only the date will be used within Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<th colspan="3">iShip</th>
		</tr>
		<tr>
			<td>@operation_type</td>
			<td>One of <code>Add</code> or <code>Change</code>. When importing into Magento, both operation types will either add a new product or update an existing product.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>@gsi_client_id</td>
			<td>Client ID provided by eBay Enterprise and configured for the website. Products will only be imported to websites with a matching Client ID. See <a href="#import-products-into-different-magento-websites">Import Products into Different Magento Websites</a> for more details on matching products to websites.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>@catalog_id</td>
			<td>Catalog ID provided by eBay Enterprise and configured for the Magento instance. Only items with a Catalog ID matching the value configured in for the Magento instance will be imported. </td>
			<td>No</td>
		</tr>
		<tr>
			<td>ItemId</td>
			<td>The product SKU.</td>
			<td></td>
		</tr>
		<tr>
			<td>ExtendedAttributes/CountryOfOrigin</td>
			<td>Specifies the "Country of Manufacture" product attribute. This value should be the two character ISO 3166 country code.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>HTSCodes</td>
			<td>List of HTS Codes applicable to the product for international tax and duties. All HTS Codes for a product are stored in the <a href="#attributes-provided-by-the-ebay-enterprise-retail-order-management-extension">"HTS Codes"</a> product attribute.</td>
			<td>No</td>
		</tr>
	</tbody>
</table>

## Required Custom Attributes

The following custom attributes are not part of any eBay Enterprise Retail Order Management schema, but must be set for Magento to properly handle a feed.

<dl>
	<dt>ProductType</dt><dd>The Magento product <code>type</code>. Possible values include <code>bundle</code>, <code>configurable</code>, <code>downloadable</code>, <code>giftcard</code>, <code>grouped</code>, <code>simple</code> and <code>virtual</code>, but only <code>configurable</code>, <code>downloadable</code>, <code>simple</code>, <code>giftcard</code> and <code>virtual</code> are supported</dd>
	<dt>ConfigurableAttributes</dt><dd>A comma-separated list of attributes on which the product can be configured. Required for any product having a <code>ProductType</code> of `configurable`</dd>
	<dt>AttributeSet</dt><dd>Used along with the <code>operation_type</code> of the <code>AttributeSet</code> custom attribute to update the attribute set for the product.</dd>
</dl>

These values should be included in the set of `CustomAttributes` for a product as such:

```xml
<Item>
	…
	<CustomAttributes>
		<Attribute name="ProductType">
			<Value>configurable</Value>
		</Attribute>
		<Attribute name="ConfigurableAttributes">
			<Value>color,size</Value>
		</Attribute>
		<Attribute name="AttributeSet">
			<Value>Shoes</Value>
		</Attribute>
		…
	</CustomAttributes>
	…
</Item>
```

## Import Products into Different Magento Websites

The combination of incoming catalog_id, gsi_client_id and gsi_store_id are mapped to Magento Websites.

Product import first gathers all the websites for the Magento Instance, and then for each Magento Website, extracts relevant information from each of the feed files according to these rules:

* The ‘catalog_id’, if present, must match the Magento website’s Catalog ID configuration. All Magento websites for an instance use the same Catalog ID, this effectively filters out any items that have a Catalog ID, and that Catalog ID does not match the Magento website.
* The ‘client_id’, if present, must match the Magento website’s Client ID configuration.
* The ’store_id’, if present, must match the Magento website’s Store ID configuration.

It is important to note that the absence of an an attribute in the incoming feed effectively acts as a wildcard.

Consider a Magento installation with 2 websites, configured with the same Client ID but different Store IDs. An incoming feed that specifies only the client_id will be assigned to **both** websites.

An incoming feed node specifying **none** of the these attributes will be assigned to **all** websites.

## Product Import Mapping

The mappings themselves are all defined in config XML in a manner similar to how Magento config already defines event observers:

```xml
<eb2cproduct_feed_attribute_mappings>
	<mage_attribute_code> <!-- The attribute code for this attribute in Magento -->
		<class>eb2cproduct/map</class> <!-- Magento object factory string -->
		<type>(disabled|model|helper|singleton)</type> <!-- Type of object factory to use -->
		<method>takeAction</method> <!-- Any public method -->
		<xpath>Relative/Path/To/Node</xpath> <!-- Relative to the current item or content node -->
	</mage_attribute_code>
	...
</eb2cproduct_feed_attribute_mappings>
```

The method mapped by the configuration will be invoked with the XML node list matched by the XPath and a loaded product instance of the product being imported. The methods should return the value to be set to the product attribute matching the attribute code the mapping is for. See `EbayEnterprise_Eb2cProduct_Helper_Map` for additional details on product mapping methods.

The XPath expressions are evaluated relative the the XML node representing a single product in the feed. For example, the `Item` node in the ItemMaster or `Content` node in the ContentMaster.

Predefined mappings can be found in `app/code/community/EbayEnterprise/Eb2cProduct/etc/config.xml` and `app/etc/local/productimport.xml.sample` at `config/default/eb2cproduct/feed_attribute_mappings`. See [Dependency and Non-Dependency Attributes](#dependency-and-non-dependency-attributes) for more details regarding the separation of mappings in each file.

### Built-in methods

The following methods are provided by EbayEnterprise_Eb2cProduct_Helper_Map to cover a majority of import scenarios:

- **extractStringValue**: Returns the string value of the first matched XML node.
- **extractBoolValue**: Returns the Bool value of the first matched XML node.
- **extractIntValue**: Returns the Int value of the first matched XML node.
- **extractFloatValue**: Returns the Float value of the first matched XML node.
- **passThrough**: Returns whatever value the method is called with.
- **extractSkuValue**: This method should be used whenever a product SKU is being extracted to ensure the SKU is properly normalized to include the Catalog ID prefix required by eBay Enterprise Retail Order Management.

### Examples

A simple example mapping IsDropShipped from BaseAttributes to the is_drop_shipped Yes/No attribute would look like:

```xml
<is_drop_shipped>
	<class>eb2cproduct/map</class>
	<type>helper</type>
	<method>extractBoolValue</method>
	<xpath>BaseAttributes/IsDropShipped</xpath>
</is_drop_shipped>
```

XPath has a lot of power for finding nodes and even transforming them itself, so much of the work can be done in the XPath expression. For example, a standard custom attribute might look like this:

```xml
<my_custom_attribute>
	<class>eb2cproduct/map</class>
	<type>helper</type>
	<method>extractStingValue</method>
	<xpath>CustomAttributes/Attribute/[@name="my_custom_attribute"]/Value</xpath>
</my_custom_attribute>
```

## Dependency and Non-Dependency Attributes

Mappings for attributes that Magento and/or eBay Enterprise Retail Order Management depend on are defined in the config.xml for Eb2cProduct itself, and should not be overridden. Mappings for other attributes are either unspecified or included in `app/etc/productimport.xml.sample` and may be customized as needed. Any data in the feeds that does not have a mapping will be ignored.

### Attributes Provided by the eBay Enterprise Retail Order Management Extension

The following attributes have are created by the eBay Enterprise Retail Order Management Extension and added to the "Default" attribute set in Magento and should be included in every product attribute set in the Magento instance.

| Attribute Name | Attribute Code | Description |
|----------------|----------------|-------------|
| Size | size | Product size. |
| Style ID | style_id | Relates simple products to configurable products. A simple products Style ID relates to the configurable product's SKU. |
| Is Clean | is_clean | Flag indicating if the product has had all of its product links resolved. |
| Unresolved Product Links | unresolved_product_links | Any related, cross-sell or up-sell product links for the product that have not yet been created, typically due to the target products not existing in Magento yet. |
| HTS Codes | hts_codes | Mapping of tax codes used for calculating international taxes and duties. |
| Tax Code | tax_code | eBay Enterprise assigned tax group. |
| Item Type | item_type | Item specification used by eBay Enterprise Retail Order Management. |
| Drop Shipped | drop_shipped | Specifies if the item can be fulfilled using a drop shipper.  |
| Drop Ship Supplier Name | drop_ship_supplier_name | Name of the drop ship supplier fulfilling the product. |
| Drop Ship Supplier Number | drop_ship_supplier_number | eBay Enterprise assigned code for the drop ship supplier. |
| Drop Ship Supplier Part Number | drop_ship_supplier_part_number | ID or SKU used by the drop ship supplier to identify the item. |
| Hierarchy Department Number | hierarchy_dept_number | Hierarchy Level 1 number. |
| Hierarchy Department Description | hierarchy_dept_description | Hierarchy Level 1 description. |
| Hierarchy Subdepartment Number | hierarchy_subdept_number | Hierarchy Level 2 number. |
| Hierarchy Subdepartment Description | hierarchy_subdept_description | Hierarchy Level 2 description. |
| Hierarchy Class Number | hierarchy_class_number | Hierarchy Level 3 number. |
| Hierarchy Class Description | hierarchy_class_description | Hierarchy Level 3 description. |
| Hierarchy Subclass Number | hierarchy_subclass_number | Hierarchy Level 4 number. |
| Hierarchy Subclass Description | hierarchy_subclass_description | Hierarchy Level 4 description. |
| Gift Card Tender Code | gift_card_tender_code | Type of gift card to be used for activiation. Allowable valies: "SD" (TRU DIGITAL GIFT CARD), "SP" (SVS Physical Gift Card), "ST" (SmartClixx Gift Card Canada), "SV" (SVS Virtual Gift Card), "SX" (SmartClixx Gift Card). |

### Attributes Directly Affecting the Behavior of eBay Enterprise Retail Order Management

These product attributes are required by the eBay Enterprise Retail Order Management Extension and are mapped in the module level configuration.

- is_clean
- item_type
- sku
- style_id
- tax_code
- unresolved_product_links

### Required by Magento Out-of-the-Box

These product attributes are required for out-of-the-box Magento stores. Mappings for these product attributes are provided in the module level configuration.

- name
- description
- short_description
- sku
- price
- weight
- status
- visibility
- tax_class_id
- price_view
- allow_open_amount
- type_id

## Language

In eBay Enterprise Retail Order Management terms, some attribute values can vary on language. In Magento terms this means we distribute the different values for these nodes across different stores, if such stores exist. (For these purposes we do not distinguish between a "store" and a "store view".)

The mapping framework used to extract data from feeds will ensure that values with a specific language code will be properly imported into the correct stores. The mapped callbacks will typically not need to specifically handle translations.

### Where to Find Language in the Feeds

eBay Enterprise Retail Order Management feeds denote language according to one of two possible structures, either:

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

### Language-Specific Attribute Values

Once you have acquired an attribute node with a language, apply the value to all stores that have that language. For example, assume you have a Magento instance set up the following way:

| Scope               | Language      |
| ------------------- | ------------- |
| Default             | "en-us"       |
| Website1            | "use default" |
| Website1:Storeview1 | "use default" |
| Website1:Storeview2 | "fr-ca"       |
| Website2            | "de-de"       |
| Website2:Storeview3 | "it-it"       |
| Website2:Storeview4 | "en-us"       |
| Website2:Storeview5 | "use website" |
| Website2:Storeview6 | "zh-cn"       |

And assuming this configuration:

```xml
<feed_attribute_mappings>
	<name>
		<class>eb2cproduct/map</class>
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

The data import would have this result.

| Scope               | Language      | Product name  | Notes         |
| ------------------- | ------------- | ------------- | ------------- |
| Default             | "en-us"       | Dill Pickle   | Default value |
| Website1            | "use default" |               |               |
| Website1:Storeview1 | "use default" |               |               |
| Website1:Storeview2 | "fr-ca"       |               | No data for this language is provided so the default value will be used |
| Website2            | "de-de"       |               | Product data is never set at the website leve |
| Website2:Storeview3 | "it-it"       | sottaceto     |               |
| Website2:Storeview4 | "en-us"       |               | Falls back to the default value as the languages match an no translated data is set in an intermediate scope |
| Website2:Storeview5 | "use website" | Dillgurke     | Language config is set at website level but actual product data is saved at the store view |
| Website2:Storeview6 | "zh-cn"       | 泡菜           |               |

Scopes with values have data set in that scope. Empty "Product name" fields fall back through parent scopes.

#### Scope notes:

1. If the language of a store view is the same as the default language, and the value of an attribute is changes at that upper scope, then any attribute value already set at the inner scope should be removed, and it should be set to fall back as well. This is what happened to Storeview1 and Storeview4.
2. The processor can only apply values at the lowest scope of the attribute itself. Thus, if "name" were a global attribute, only the "en-us" value would be applicable because the language at the default scope is "en-us".

## Importing Configurable Products

When importing configurable products, the product only needs to be included in the ContentMaster feed. The product must include the "ProductType" custom attribute and the "ConfigurableAttributes" custom attribute. The "ProductType" should be `configurable` and "ConfigurableAttributes" should be a comma separated list of product attribute codes the product is configured on.

Simple products are linked to configurable products by matching the Style ID of the simple product to the SKU of the configurable product. Any time new products are imported, the products will be checked for fulfilling either end of a configurable/simple product relationship.

## Importing Gift Cards

Gift cards are specified in the product feeds by setting `giftcard` as the "ProductType" custom attribute.

```xml
…
<Item>
	…
	<CustomAttributes>
		…
		<Attribute name="ProductType">
			<Value>giftcard</Value>
		</Attribute>
		…
	</CustomAttributes>
	…
</Item>
…
```

### Gift Card Attribute Mappings

The following mappings are used to map feed values to Magento gift card attributes:

```xml
<config>
	<default>
		<eb2cproduct>
			<feed_attribute_mappings>
				<giftcard_type>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractGiftcardTenderValue</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</giftcard_type>
				<is_redeemable>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractIsRedeemable</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</is_redeemable>
				<use_config_is_redeemable>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractIsRedeemable</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</use_config_is_redeemable>
				<lifetime>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractLifetime</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</lifetime>
				<allow_message>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractBoolValue</method>
					<xpath>ExtendedAttributes/AllowGiftMessage</xpath>
				</allow_message>
				<email_template>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractEmailTemplate</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</email_template>
			</feed_attribute_mappings>
		</eb2cproduct>
	</default>
</config>
```

### ItemMaster Feed Requirements

The following date in the product feeds is used to configure gift card products in Magento:

| XPath                                           | Usage |
| CustomAttributes/Attribute[@name="ProductType"] | Specifies the product as being a gift card. |
| ExtendedAttributes/AllowGiftMessage             | Sets whether a gift message may be included with the gift card purchase. This value is optional and will default to use the configuration setting. |
| ExtendedAttributes/GiftCardTenderCode           | Used to match the eBay Enterprise Retail Order Management gift card type to a Magento gift card type. The value provided in the feed *must* be included in the [gift card tender type map](#gift-card-tender-type-map)|

### Gift Card Tender Type Map

eBay Enterprise Retail Order Management gift card tender types are mapped to they types of gift cards available in Magento to create virtual, physical, and combined gift cards. This mapping is located in `app/etc/exchangeplatform.xml`:

```xml
<config>
	<default>
		<eb2ccore>
			<feed>
				<gift_card_tender_code>
					<SD>virtual</SD>
					<SP>physical</SP>
					<ST>combined</ST>
					<SV>virtual</SV>
					<SX>combined</SX>
				</gift_card_tender_code>
			</feed>
		</eb2ccore>
	</default>
</config>
```

This mapping may be modified if needed to associate eBay Enterprise Retail Order Management types to different Magento gift card types.

Any gift cards imported into Magento *must* have a tender code included in the mapping to import successfully.

### Default Attribute Values

The following gift card attributes are not provided in the product feeds and will default to using the values specified by the Magento Gift Card configuration:

- **Lifetime**: Gift Card General Settings > Lifetime (days)
- **Email Template**: Gift Card Email Settings > Gift Card Notification Email Template
- **Is Redeemable**: Gift Card General Settings > Redeemable

## Images

The eBay Enterprise Retail Order Management Extension does not currently support importing images.

Please see [Image Feeds](IMAGE_EXPORT.md) documentation for details on exporting images to eBay Enterprise Retail Order Management. Image export is required when importing products to make images available services such as eBay Enterprise Marketing Solutions Email and marketplace integrations.
