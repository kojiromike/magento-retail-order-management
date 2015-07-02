<?xml version = "1.0" encoding = "utf-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml"/>
	<xsl:template match="@*">
		<xsl:apply-templates />
	</xsl:template>
	<xsl:template match="ItemMaster">
		<product_to_be_deleted>
			<xsl:for-each select="Item">
				<xsl:variable name="itemType" select="BaseAttributes/ItemType" />
				<xsl:if test="@operation_type='Delete' and $itemType!='GiftWrap'">
					<sku>
						<xsl:apply-templates select="@*"/>
						<xsl:value-of select="ItemId/ClientItemId" />
					</sku>
				</xsl:if>
			</xsl:for-each>
		</product_to_be_deleted>
	</xsl:template>
	<xsl:template match="@*">
		<xsl:copy/>
	</xsl:template>
</xsl:stylesheet>
