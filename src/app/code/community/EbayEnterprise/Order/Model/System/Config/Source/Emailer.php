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

class EbayEnterprise_Order_Model_System_Config_Source_Emailer
{
	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;

	public function __construct(array $initParams)
	{
		list($this->_orderHelper) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'order_helper', Mage::helper('ebayenterprise_order'))
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  EbayEnterprise_Order_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(EbayEnterprise_Order_Helper_Data $orderHelper)
	{
		return [$orderHelper];
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the default value.
	 *
	 * @param  array
	 * @param  string $field Valid array key
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$arr = $this->toArray();
		return array_map(function ($k) use ($arr) {
			return ['value' => $k, 'label' => $arr[$k]];
		}, array_keys($arr));
	}

	/**
	 * Get options in "key-value" format
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'eb2c' => $this->_orderHelper->__('eBay Enterprise Email'),
			'mage' => $this->_orderHelper->__('Magento'),
		];
	}
}
