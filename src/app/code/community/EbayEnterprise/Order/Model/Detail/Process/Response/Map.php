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

use eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderDetailResponse;

class EbayEnterprise_Order_Model_Detail_Process_Response_Map
	implements EbayEnterprise_Order_Model_Detail_Process_Response_IMap
{
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_config;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;

	/**
	 * @param array $initParams optional keys:
	 *                          - 'config' => EbayEnterprise_Eb2cCore_Model_Config_Registry
	 *                          - 'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 */
	public function __construct(array $initParams=[])
	{
		list($this->_config, $this->_coreHelper) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'config', Mage::helper('ebayenterprise_order')->getConfigModel()),
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore'))
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Eb2cCore_Model_Config_Registry $config,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	)
	{
		return [$config, $coreHelper];
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
	 * @see EbayEnterprise_Order_Model_Detail_Process_IResponse::process()
	 */
	public function extract(IOrderDetailResponse $detail)
	{
		return $this->_extractData($detail);
	}

	/**
	 * Extracting the response data using the configuration
	 * callback methods.
	 *
	 * @return array
	 */
	protected function _extractData(IOrderDetailResponse $detail)
	{
		$data = [];
		foreach ($this->_config->mapDetailResponse as $key => $callback) {
			if ($callback['type'] !== 'disabled') {
				$getter = $callback['getter'];
				$callback['parameters'] = [$detail, $getter];
				$data[$key] = $this->_coreHelper->invokeCallback($callback);
			}
		}
		return $data;
	}
}
