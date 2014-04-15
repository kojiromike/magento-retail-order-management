# Magento as a Product Information Management System

When Magento is to be the system of record for product information (except for inventory management), the extension enables the system to be configured to export product data as XML files. The implementation should consist mostly of filter, dump and transform operations on the Magento flat products table to provide the expected XML output for Sterling Integrator.

## XML Feeds

Products are exported to XML formats that closely mirror the inbound ItemMaster, ContentMaster, PriceEvent, and iShip feeds. Each outbound feed file shall validate to the same XML schema of the respective inbound feed.

## Mappings

Mappings are located in the app/etc directory of the Magento root in an XML configuration file named productexport.xml.sample. This file must be copied or renamed to productexport.xml in order for exports to function.

	__WARNING:__ Be careful when modifying this file. Invalid Changes can cause schema validation errors which may prevent feed files from being generated.

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

Attribute codes in Magento are mapped using an ordered list of xml structures that relate a Magento product attribute code to a method. The method should transform the data into an appropriate xml representation. The result is added as a child of the element specified in the config. The method declarations are similar to Magento's observer configuration.

```xml
<mage_attribute_code> <!-- The attribute code for an attribute in Magento -->
	<class>eb2cproduct/pim</class> <!-- Magento object factory string -->
	<type>(disabled|model|helper|singleton)</type> <!-- Add helper to what dispatchEvent handles -->
	<method>takeAction</method> <!-- Any public method -->
	<xml_dest>Explicit/Relative/Path/To/Node</xml_dest> <!-- Subset-of-xpath expression describing how to create the destination node. -->
	<translate>(0|1)</translate> <!-- Whether the resultant xml can contain more than one node differentiated by a `xml:lang` attribute or only a single node with no `xml:lang` -->
</mage_attribute_code>
...
```

If `type` is `disabled`, the attribute will not be built into the outbound file.

The `xml_dest` specifies the element that will be the parent of the transformed data. Its value is a string that takes its syntax from XPath. The path shall unambiguously describe a single destination node and consist of elements separated by slashes.

A fatal exception will be thrown in the following cases:

1. Ambiguous `xml_dest` paths
1. Paths with leading slashes
1. Paths starting with `..`

The element referenced by the final part of the path will always be created as a new element even if an element with the same tag name exists. A trailing '/' at the end of the path overrides this feature and instead attaches the method results as the children of the existing element.

Note: no nodes are overwritten.

The first element of the `xml_dest` path is expected to be one of BaseAttributes, ExtendedAttributes or CustomAttributes, but it is not explicitly forbidden for it to be something else. Please refer to the XML schema for more information.

There is limited support for element attributes using a subset of XPath predicate syntax.
For example, the path `foo/bar/c[@attr="attrvalue"]` will result in the following xml output:

```xml
	<foo>
		<bar>
			<c attr="attrvalue">
				<!-- result of method -->
			</c>
		</bar>
	</foo>
```

To send a hard-coded value regardless of the actual attribute value for the product, create a method that returns that value. (Ideally, the method would fetch a hard-coded value from some other reasonable config.xml node, but if we really want to be idealistic we should not send hard-coded values.)

### Special Attribute Configurations

There are cases where data is not stored as a product attribute or calculated from other sources. Prepending the "attribute code" element's tag name with an underscore will tell the export module to not attempt to retrieve the value from the product, but still execute the method. The result, if any, will be added to the document.

```xml
<_name_of_attribute_or_concept> <!-- prepend the xml tag name with an "_" -->
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

As long as the resultant xml validates according to the schema (see xsd subdirectory of the Eb2cCore module), you can customize this mapping however you like, including writing your own mapping methods.


### Built-in methods

An eb2cproduct pim helper should have predefined passthrough methods for the following data types:

- boolean
- string
- integer
- float

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

## Dependency and Non-Dependency Attributes

_This section to consist of description of attributes that Sterling Integrator depends on, and should not be overwritten. Also, we need to describe how configurable products get mapped to styles._

## Language

In Eb2c terms, some attribute values can vary on language. In Magento terms this means we distribute the different values for these nodes across different stores, if such stores exist. (For these purposes we do not distinguish between a "store" and a "store view".) If the config.xml `translate` element is set to 0, the resultant xml should contain a single node with no `xml:lang` attribute.`

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

Thus, if the base path starts with `CustomAttributes`, the generator must produce one `Attribute` node per language. Otherwise, the leaf node can be duplicated per language.

### What to do with language-specific attribute values

Once you have acquired an attribute node with a language, apply the value to all scopes that have that language. For example, assume you have a Magento instance set up the following way:

| Scope               | Language      | Product name  |
| ------------------- | ------------- | ------------- |
| Default             | "en-us"       | Pickle        |
| Website1            | "use default" | "use default" |
| Website1:Storeview1 | "use default" | "use default" |
| Website1:Storeview2 | "fr-ca"       | pétrin        |
| Website2            | "de-de"       | Gurke         |
| Website2:Storeview3 | "it-it"       | sottaceto     |
| Website2:Storeview4 | "en-us"       | Dill Pickle   |
| Website2:Storeview5 | "use website" | Essiggurke    |
| Website2:Storeview6 | "zh-cn"       | "use website" |

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
	<Title xml:lang="de-de">Gurke</Title>
	<Title xml:lang="it-it">sottaceto</Title>
	<Title xml:lang="en-us">Dill Pickle</Title>
	<Title xml:lang="zh-cn">Pickle</Title>
	...
</BaseAttributes>
```

If two store views with the same client id, store code and language have different values for an attribute, it is OK to list both of them. In fact, for sake of simplicity it is acceptable to list redundant attribute nodes, like:

```xml
<Foo xml:lang="en-us">Fish</Foo>
<Foo xml:lang="en-us">Fish</Foo>
```

This is harmless and Sterling Integrator will normalize the input.

##Available Magento Attributes for Pricing Feed

- sku
- msrp
- special_price
- special_from_date
- special_to_date
- price

=======
## How to Export Image Feeds

1.	Go to **System > Scheduler > Schedule Configuration** in the Admin.
2.	Click the checkbox next to eb2cproduct_image_export_feed.
3.	Select run now from the actions drop down.
4.	Click submit button.

Image Feed Files will be generated per store configured in Magento in the /ExchangePlatform/Feed/out-box/ folder having the following file pattern ({client_id}_{store_id}_{feed_type}_{time_stamp}_{current_store_id}.xml)

# File Pattern Map:
| Pattern               | Value                                                    |
| --------------------- | -------------------------------------------------------- |
| {client_id}           | eb2ccore/general/client_id                               |
| {store_id}            | eb2ccore/general/store_id                                |
| {feed_type}           | eb2cproduct/image_master_feed/filename_format            |
| {time_stamp}          | Mage::getModel('core/date')->gmtDate('YmdHis', time())   |
| {current_store_id}    | Mage::getModel('core/store')->getId()                    |

# Sample Image Feed Export
```xml
<ItemImages imageDomain="example.com" clientId="TST" timestamp="2014-04-07T11:42:27+00:00">
	<MessageHeader>...</MessageHeader>
	<Item id="54-E491B455-Ite">
		<Images>
			<Image imageview="image" imagename="This is a test label" imageurl="http://example.com/media/catalog/product/e/a/earth.png" imagewidth="700" imageheight="700"/>
			<Image imageview="small_image" imagename="This is a test label" imageurl="http://example.com/media/catalog/product/e/a/earth.png" imagewidth="700" imageheight="700"/>
			<Image imageview="thumbnail" imagename="This is a test label" imageurl="http://example.com/media/catalog/product/e/a/earth.png" imagewidth="700" imageheight="700"/>
		</Images>
	</Item>
</ItemImages>
```

# Mapping of Image Feed xml nodes/xml node attributes to Magento Product Image Data:
| XML Nodes/Attributes                                 | Magento Product Image Data          | Example                                                                                                                                 |
| ---------------------------------------------------- | ----------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| /ItemImages[@imageDomain]                            | the host of the install magento app | parse_url(Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB))['host']                                     |
| /ItemImages[@clientId]                               | the client id configure in magento  | eb2ccore/etc/config.xml (eb2ccore/general/client_id)                                                                                    |
| /ItemImages[@timestamp]                              | the current date time               | (xsd:date Mage::getModel('core/date')->date('c'))                                                                                       |
| /ItemImages/MessageHeader                            | the export message header           | Mage::helper('eb2cproduct')->generateMessageHeader($cfg->imageFeedEventType)                                                            |
| /ItemImages/Item[@id]                                | the product sku                     | $product->getSku() (not currently being de-normalized???)                                                                               |
| /ItemImages/Item[@id]/Images/Image[@imageview]       | the product image view types        | image_media type (image, small_image, thumbnail)                                                                                        |
| /ItemImages/Item[@id]/Images/Image[@imagename]       | the product image label             | ($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getLabel())                                                  |
| /ItemImages/Item[@id]/Images/Image[@imageurl]        | the product image url               | ($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getUrl())                                                    |
| /ItemImages/Item[@id]/Images/Image[@imagewidth       | the image width                     | getimagesize(path|url) (getimagesize($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getPath()|getUrl()))     |
| /ItemImages/Item[@id]/Images/Image[@imageheight]     | the image height                    | getimagesize(path|url) (getimagesize($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getPath()|getUrl()))     |


*NOTE: if the products in Magento have no image data then the image export will not generate any feed files.*

=======
##ItemMaster Export

### Exchange Platform Attributes
These attributes are added to the Magento Installation to support ItemMaster Export. Any required attribute that does not have a value will cause that product to be excluded from the export. The product SKU and an appropriate messages are logged at a WARN level to the system log.

| Attribute                         | Req? |
|-----------------------------------|------|
| drop_ship_supplier_name           |  -   |
| drop_ship_supplier_number         |  -   |
| drop_ship_supplier_part_number    |  -   |
| drop_shipped                      |  Y   |
| hierarchy_class_description       |  -   |
| hierarchy_class_number            |  Y   |
| hierarchy_dept_description        |  -   |
| hierarchy_dept_number             |  Y   |
| hierarchy_subclass_description    |  -   |
| hierarchy_subclass_number         |  Y   |
| hierarchy_subdept_description     |  -   |
| hierarchy_subdept_number          |  Y   |
| item_type                         |  -   |
| tax_code                          |  Y   |

### Magento System Attributes
Any product with a sku attribute longer than 15 characters is excluded from the export. The product SKU is logged at a WARN level to the system log.

### Complete Mapping Details
Are specified in Confluence: [http://confluence.tools.us.gspt.net/display/v11dev/Product+Export+Mappings]

