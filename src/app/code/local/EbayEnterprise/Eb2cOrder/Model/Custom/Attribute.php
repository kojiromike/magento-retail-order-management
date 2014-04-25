<?php
/**
 * This is the base custom attribute class any concrete class that wishes to use
 * the self::extractData method must extend this class and define their own
 * static::MAPPING_PATH constant to the path of where their configuration exists
 * in the etc/config.xml file.
 */
class EbayEnterprise_Eb2cOrder_Model_Custom_Attribute
{
	/**
	 * hold the path to the order mapping level. This constant should be defined
	 * in each child class that extend this parent class in order to extract
	 * specific order level data
	 */
	const MAPPING_PATH = '';
	/**
	 * Get the path to the specific level custom attribute map.
	 * @return string
	 */
	protected function _getConfigPath()
	{
		return static::MAPPING_PATH;
	}
	/**
	 * extracting the custom configured attribute data for any given object
	 * inherited by the Varien_Object class
	 * @param  mixed $item an object inherited by the Varien_Object class
	 * @return array
	 */
	public function extractData(Varien_Object $item)
	{
		$helper = Mage::helper('eb2ccore/feed');
		$mappings = $helper->getConfigData($this->_getConfigPath());

		return array_reduce(
			array_keys($mappings),
			function ($attributes, $attribute) use ($mappings, $helper, $item) {
				$callback = isset($mappings[$attribute]) ? $mappings[$attribute] : array();
				// exclude any mappings that have a type of "disabled"
				if (isset($callback['type']) && $callback['type'] !== 'disabled') {
					$callback['parameters'] = array($item, $attribute);
					$attributes[$attribute] = $helper->invokeCallback($callback);
				}
				return $attributes;
			});
	}
}
