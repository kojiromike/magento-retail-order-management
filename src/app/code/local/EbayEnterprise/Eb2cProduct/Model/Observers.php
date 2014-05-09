<?php
class EbayEnterprise_Eb2cProduct_Model_Observers
{
	/**
	 * This observer locks attributes we've configured as read-only
	 * @return void
	 */
	public function lockReadOnlyAttributes(Varien_Event_Observer $observer)
	{
		$readOnlyAttributesString = Mage::helper('eb2cproduct')->getConfigModel()->readOnlyAttributes;
		// We use preg_split's PREG_SPLIT_NO_EMPTY so multiple ',' won't populate an array slot
		//  with an empty string. A single string without separators ends up at index 0.
		$readOnlyAttributes = preg_split('/,/', $readOnlyAttributesString, -1, PREG_SPLIT_NO_EMPTY);
		if ($readOnlyAttributes) {
			$product = $observer->getEvent()->getProduct();
			foreach ($readOnlyAttributes as $readOnlyAttribute) {
				$product->lockAttribute($readOnlyAttribute);
			}
		}
	}
}
