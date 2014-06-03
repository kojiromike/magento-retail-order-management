<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
		$mappings = Mage::helper('eb2corder')->getConfigModel()->getConfigData($this->_getConfigPath());

		return !empty($mappings) ? array_reduce(
			array_keys($mappings),
			function ($attributes, $attribute) use ($mappings, $helper, $item) {
				$callback = isset($mappings[$attribute]) ? $mappings[$attribute] : array();
				// exclude any mappings that have a type of "disabled"
				if (isset($callback['type']) && $callback['type'] !== 'disabled') {
					$callback['parameters'] = array($item, $attribute);
					$attributes[$attribute] = $helper->invokeCallback($callback);
				}
				return $attributes;
			}) : array();
	}
}
