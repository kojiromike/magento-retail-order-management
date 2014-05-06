<?php
class EbayEnterprise_Eb2cProduct_Model_Observers
{
	/**
	 * Takes the argument, a comma separated list of attributes, and returns an array of those attributes.
	 * @return array || null
	 */
	protected function _attributeStringToArray($attributeListString)
	{
		$attrArray = array();
		if (strpos($attributeListString,',')) {
			$attrArray = preg_split('/,/', $attributeListString, -1, PREG_SPLIT_NO_EMPTY);
		} else if (strlen($attributeListString)) {
			$attrArray[] = $attributeListString;
		}
		return count($attrArray) ? $attrArray : null;
	}
	/**
	 * This observer locks attributes we've configured as read_only
	 * @return void
	 */
	public function lockReadOnlyAttributes(Varien_Event_Observer $observer)
	{
		$readOnlyAttributes =  $this->_attributeStringToArray(
			Mage::helper('eb2cproduct')->getConfigModel()->readOnlyAttributes
		);
		if ($readOnlyAttributes) {
			$product = $observer->getEvent()->getProduct();
			foreach ($readOnlyAttributes as $readOnlyAttribute) {
				$product->lockAttribute($readOnlyAttribute);
			}
		}
	}
}
