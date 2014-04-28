# Mapping Order Create Custom Attributes

## Assumption

We made the assumption that any order level fields, order context level fields or order item level fields that the SI configured in the custom attributes map will exist in magento through some other independent module plug-ins. This configuration assumes that these data can be retrieved using the get magic method on any concrete class instances that extended a **Varien_Object** class.

## How to configure order custom attributes by order levels:

There are three levels to configure for custom attributes
- Order Level
- Order Context Level
- Order Item Level

## XML Configuration
The order custom attribute xml will exist in **app/etc/ordercustomattributes.xml.sample**
```xml
<custom_attribute_mappings>
	<order_level>
		<increment_id> <!-- A known data that can be retrieved via 'get' magic method on any concrete class instance that extend Varien_Object class-->
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</increment_id>
	</order_level>
	<order_item_level>
		<sku> <!-- A known data that can be retrieved via 'get' magic method on any concrete class instance that extend Varien_Object class-->
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</sku>
	</order_item_level>
	<order_context_level>
		<name>
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</name>
	</order_context_level>
</custom_attribute_mappings>
```

## The 'CustomAttributes' node at order level in the OrderCreateRequest Sample:

When an SI configured the custom attributes at the order level the configuration should look like the example below:

```xml
<custom_attribute_mappings>
	<order_level>
		<increment_id>
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</increment_id>
	</order_level>
</custom_attribute_mappings>
```

The OrderCreateRequest xml will look like the example below:

```xml
<OrderCreateRequest>
	<Order>
		<Customer>...</Customer>
		<CreateTime>...</CreateTime>
		<OrderItems>...</OrderItems>
		<Shipping>...</Shipping>
		<Payment>...</Payment>
		<Currency>...</Currency>
		<TaxHeader>...</TaxHeader>
		<Locale>...</Locale>
		<CustomAttributes>
			<Attribute>
				<Key>increment_id</Key>
				<Value>0005400000003</Value>
			</Attribute>
		</CustomAttributes>
		<OrderHistoryUrl>...</OrderHistoryUrl>
		<OrderTotal>...</OrderTotal>
	</Order>
	<Context>...</Context>
</OrderCreateRequest>
```

## The 'CustomAttributes' node at order item level in the OrderCreateRequest Sample:

When an SI configured the custom attributes at the order item level the configuration should look like the example below:

```xml
<custom_attribute_mappings>
	<order_item_level>
		<sku>
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</sku>
	</order_item_level>
</custom_attribute_mappings>
```

The OrderCreateRequest xml will look like the example below:

```xml
<OrderCreateRequest>
	<Order>
		<Customer>...</Customer>
		<CreateTime...</CreateTime>
		<OrderItems>
			<OrderItem>
				<ItemId>...</ItemId>
				<Quantity>2</Quantity>
				<Description>...</Description>
				<Pricing>...</Pricing>
				<ShippingMethod>...</ShippingMethod>
				<EstimatedDeliveryDate>...</EstimatedDeliveryDate>
				<CustomAttributes>
					<Attribute>
						<Key>sku</Key>
						<Value>45-HTCT60</Value>
					</Attribute>
				</CustomAttributes>
				<ReservationId>...</ReservationId>
			</OrderItem>
		</OrderItems>
		<Shipping>...</Shipping>
		<Payment>...</Payment>
		<Currency>...</Currency>
		<TaxHeader>...</TaxHeader>
		<Locale>...</Locale>
		<OrderHistoryUrl>...</OrderHistoryUrl>
		<OrderTotal>...</OrderTotal>
	</Order>
	<Context>...</Context>
</OrderCreateRequest>
```

## The 'CustomAttributes' node at order context level in the OrderCreateRequest Sample:

When an SI configured the custom attributes at the order context level the configuration should look like the example below:

```xml
<custom_attribute_mappings>
	<order_context_level>
		<name>
			<class>eb2corder/map</class>
			<type>helper</type>
			<method>getAttributeValue</method>
		</name>
	</order_context_level>
</custom_attribute_mappings>
```

The OrderCreateRequest xml will look like the example below:

```xml
<OrderCreateRequest>
	<Order>...</Order>
	<Context>
		<BrowserData>...</BrowserData>
		<TdlOrderTimestamp>...</TdlOrderTimestamp>
		<SessionInfo>...</SessionInfo>
		<CustomAttributes>
			<Attribute>
				<Key>name</Key>
				<Value/>
			</Attribute>
		</CustomAttributes>
	</Context>
</OrderCreateRequest>
```