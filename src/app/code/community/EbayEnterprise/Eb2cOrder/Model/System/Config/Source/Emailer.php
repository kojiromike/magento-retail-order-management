<?php
class EbayEnterprise_Eb2cOrder_Model_System_Config_Source_Emailer
{

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$arr = $this->toArray();
		return array_map(function ($k) use ($arr) {
			return array('value' => $k, 'label' => $arr[$k]);
		}, array_keys($arr));
	}

	/**
	 * Get options in "key-value" format
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'eb2c' => Mage::helper('eb2corder')->__('eBay Enterprise Email'),
			'mage' => Mage::helper('eb2corder')->__('Magento'),
		);
	}

}
