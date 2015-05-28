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

use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryResponse;
use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryIterable;

class EbayEnterprise_Order_Model_Search_Process_Response
	implements EbayEnterprise_Order_Model_Search_Process_IResponse
{
	/** @var IOrderSummaryResponse */
	protected $_response;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var EbayEnterprise_Order_Helper_Factory */
	protected $_factory;
	/** @var EbayEnterprise_Order_Model_Search_Process_Response_IMap */
	protected $_map;

	/**
	 * @param array $initParams Must have this key:
	 *                          - 'response' => IOrderSummaryResponse
	 */
	public function __construct(array $initParams)
	{
		list($this->_response, $this->_coreHelper, $this->_factory, $this->_map) = $this->_checkTypes(
			$initParams['response'],
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
			$this->_nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory')),
			$this->_nullCoalesce($initParams, 'map', Mage::getModel('ebayenterprise_order/search_process_response_map'))
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  IOrderSummaryResponse
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @param  EbayEnterprise_Order_Helper_Factory
	 * @param  EbayEnterprise_Order_Model_Search_Process_Response_IMap
	 * @return array
	 */
	protected function _checkTypes(
		IOrderSummaryResponse $response,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_Order_Helper_Factory $factory,
		EbayEnterprise_Order_Model_Search_Process_Response_IMap $map
	)
	{
		return [$response, $coreHelper, $factory, $map];
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
	 * @see EbayEnterprise_Order_Model_Search_Process_IResponse::process()
	 */
	public function process()
	{
		return $this->_response instanceof IOrderSummaryResponse
			? $this->_processResponse()
			: $this->_factory->getNewSearchProcessResponseCollection();
	}

	/**
	 * Build a collection using the response data.
	 *
	 * @return EbayEnterprise_Order_Model_Search_Process_Response_ICollection
	 */
	protected function _processResponse()
	{
		/** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
		$collection = $this->_factory->getNewSearchProcessResponseCollection();
		/** @var IOrderSummaryIterable */
		$summaries = $this->_response->getOrderSummaries();
		return $this->_buildResponseCollection($summaries, $collection);
	}

	/**
	 * From the passed in IOrderSummaryIterable object build
	 * the passed in Varien_Data_Collection object.
	 *
	 * @param  IOrderSummaryIterable
	 * @param  EbayEnterprise_Order_Model_Search_Process_Response_ICollection
	 * @return EbayEnterprise_Order_Model_Search_Process_Response_ICollection
	 */
	protected function _buildResponseCollection(
		IOrderSummaryIterable $summaries,
		EbayEnterprise_Order_Model_Search_Process_Response_ICollection $collection
	)
	{
		/** @var IOrderSummary $summary */
		foreach ($summaries as $summary){
			$collection->addItem($this->_factory->getNewVarienObject($this->_map->extract($summary)));
		}
		return $collection->sort();
	}
}
