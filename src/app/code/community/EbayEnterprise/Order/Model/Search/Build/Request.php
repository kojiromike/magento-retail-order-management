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

use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryRequest;
use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSearch;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;


class EbayEnterprise_Order_Model_Search_Build_Request
	implements EbayEnterprise_Order_Model_Search_Build_IRequest
{
	/** @var string */
	protected $_customerId;
	/** @var string */
	protected $_orderId;
	/** @var IOrderSummaryRequest */
	protected $_payload;
	/** @var EbayEnterprise_Order_Helper_Factory */
	protected $_factory;
	/** @var IBidirectionalApi */
	protected $_api;

	/**
	 * @param array $initParams Must have these keys:
	 *                          - 'api' => IBidirectionalApi
	 *                          - 'customer_id' => string
	 */
	public function __construct(array $initParams)
	{
		list($this->_api, $this->_customerId, $this->_orderId) = $this->_checkTypes(
			$initParams['api'],
			$initParams['customer_id'],
			$this->_nullCoalesce($initParams, 'order_id', null)
		);
		$this->_payload = $this->_api->getRequestBody();
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  IBidirectionalApi
	 * @param  string
	 * @param  string | null
	 * @return array
	 */
	protected function _checkTypes(
		IBidirectionalApi $api,
		$customerId,
		$orderId = null
	)
	{
		return [$api, $customerId, $orderId];
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
	 * @see EbayEnterprise_Order_Model_Search_Build_IRequest::build()
	 */
	public function build()
	{
		$this->_buildPayload();
		return $this->_payload;
	}

	/**
	 * Populate order summary request payload.
	 *
	 * @return self
	 */
	protected function _buildPayload()
	{
		/** @var IOrderSearch */
		$orderSearch = $this->_payload->getOrderSearch();
		$this->_payload->setOrderSearch($this->_buildOrderSearch($orderSearch));
		return $this;
	}

	/**
	 * Populate order search sub-payload.
	 *
	 * @param  IOrderSearch
	 * @return IOrderSearch
	 */
	protected function _buildOrderSearch(IOrderSearch $orderSearch)
	{
		return $orderSearch
			->setCustomerId($this->_customerId)
			->setCustomerOrderId($this->_orderId);
	}
}
