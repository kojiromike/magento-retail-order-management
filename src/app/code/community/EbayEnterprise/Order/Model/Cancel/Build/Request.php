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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCancelRequest;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Order_Model_Cancel_Build_Request
	implements EbayEnterprise_Order_Model_Cancel_Build_IRequest
{
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var IOrderCancelRequest */
	protected $_payload;

	/**
	 * @param array $initParams Must have these keys:
	 *                          - 'order' => Mage_Sales_Model_Order
	 */
	public function __construct(array $initParams)
	{
		list($this->_order, $this->_payload) = $this->_checkTypes(
			$initParams['order'],
			$this->_nullCoalesce($initParams, 'payload', $this->_getEmptyPayload())
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  Mage_Sales_Model_Order
	 * @param  IOrderCancelRequest
	 * @return array
	 */
	protected function _checkTypes(Mage_Sales_Model_Order $order, IOrderCancelRequest $payload)
	{
		return [$order, $payload];
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the default value.
	 * @param  array $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Get empty order cancel request payload.
	 *
	 * @return IOrderCancelRequest
	 */
	protected function _getEmptyPayload()
	{
		return $this->_getNewPayloadFactory()
			->buildPayload(static::PAYLOAD_CLASS);
	}

	protected function _getNewPayloadFactory()
	{
		return new PayloadFactory();
	}

	/**
	 * Generate unique random string.
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _generateReasonCode()
	{
		return uniqid('OCR-');
	}

	/**
	 * @see EbayEnterprise_Order_Model_Cancel_Build_IRequest::build()
	 */
	public function build()
	{
		$this->_buildPayload();
		return $this->_payload;
	}

	/**
	 * Populate order cancel payload.
	 *
	 * @return self
	 */
	protected function _buildPayload()
	{
		$this->_payload->setOrderType(static::ORDER_TYPE)
			->setCustomerOrderId($this->_order->getIncrementId())
			->setReasonCode($this->_generateReasonCode());
		return $this;
	}
}
