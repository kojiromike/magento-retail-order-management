<?php
class EbayEnterprise_Eb2cOrder_Helper_Map
{
	/**
	 * extract the data value from an pass in object that is inherited an
	 * Varien_Object class
	 * @param  Varien_Object $item
	 * @param  string $attributeCode
	 * @return string
	 */
	public function getAttributeValue(Varien_Object $item, $attributeCode)
	{
		return Mage::helper('core')->htmlEscape($item->getDataUsingMethod($attributeCode));
	}
}
