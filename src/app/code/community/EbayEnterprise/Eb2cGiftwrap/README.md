# Gift Wrapping Attribute Mappings

The following table describes how elements in the XML gift wrapping feeds are imported and used by Magento.

*All XPath expressions are relative to the repeating XML node representing a single gift wrapping in the feed, e.g. `Item` in ItemMaster.*

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
			<td>BaseAttributes/ItemDescription</td>
			<td>Design of the Enterprise Gift Wrapping in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>1</td>
			<td>Status of the Enterprise Gift Wrapping in Magento will always be set to the literal value '1'</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ExtendedAttributes/Price</td>
			<td>Base Price of the Enterprise Gift Wrapping in Magento.</td>
			<td>No</td>
		</tr>
		<tr>
			<td></td>
			<td>image of the Enterprise Gift Wrapping in Magento is disabled; therefore, there's no mapping for it in ROM import feed.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>ItemId/ClientItemId</td>
			<td>Eb2c sku a custom field that will be created once the ROM eb2cgiftwrap module is install and will be use for sending Tax Duty Quote Request and in sending Order Create Request gifting node for individual items or for entire quote.</td>
			<td>No</td>
		</tr>
		<tr>
			<td>BaseAttributes/TaxCode</td>
			<td>Eb2c Tax Class custom field needed for  sending Tax Duty Quote Request for gifting nodes.</td>
			<td>No</td>
		</tr>
	</tbody>
</table>