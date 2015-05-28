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

class EbayEnterprise_Order_Overrides_Block_Order_Abstract extends Mage_Core_Block_Template
{
	const HELPER_CLASS = 'ebayenterprise_order';

	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;
	/** @var Mage_Core_Helper_Data */
	protected $_coreHelper;

	public function __construct(array $initParams = [])
	{
		list($this->_orderHelper, $this->_coreHelper) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'order_helper', Mage::helper('ebayenterprise_order')),
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('core'))
		);
		parent::__construct($this->_removeKnownKeys($initParams));
	}

	/**
	 * Remove the all the require and optional keys from the $initParams
	 * parameter.
	 *
	 * @param  array
	 * @return array
	 */
	protected function _removeKnownKeys(array $initParams)
	{
		foreach (['order_helper', 'core_helper'] as $key) {
			if (isset($initParams[$key])) {
				unset($initParams[$key]);
			}
		}
		return $initParams;
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  EbayEnterprise_Order_Helper_Data
	 * @param  Mage_Core_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Order_Helper_Data $orderHelper,
		Mage_Core_Helper_Data $coreHelper
	)
	{
		return [$orderHelper, $coreHelper];
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
	 * @see Mage_Core_Block_Abstract::getHelper()
	 * Returns a helper instance.
	 *
	 * @return EbayEnterprise_Order_Helper_Data
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getHelper($type)
	{
		return $this->_orderHelper;
	}

	/**
	 * Return the self::HELPER_CLASS class constant.
	 *
	 * @return string
	 */
	public function getHelperClass()
	{
		return static::HELPER_CLASS;
	}

	/**
	 * Price format the passed in amount parameter.
	 *
	 * @param  string
	 * @return string formatted amount
	 */
	public function formatPrice($amount)
	{
		return $this->_coreHelper->formatPrice($amount);
	}
}
