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

class EbayEnterprise_Order_Model_Cancel implements EbayEnterprise_Order_Model_ICancel
{
	/** @var IOrderCancelRequest */
	protected $_request;
	/** @var IOrderCancelResponse */
	protected $_response;
	/** @var Mage_Sales_Model_Order */
	protected $_order;

	/**
	 * @param array $initParams Must have this key:
	 *                          - 'order' => Mage_Sales_Model_Order
	 */
	public function __construct(array $initParams)
	{
		list($this->_order) = $this->_checkTypes($initParams['order']);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  Mage_Sales_Model_Order
	 * @return array
	 */
	protected function _checkTypes(Mage_Sales_Model_Order $order)
	{
		return [$order];
	}

	/**
	 * @see EbayEnterprise_Order_Model_ICancel::process()
	 */
	public function process()
	{
		return $this->_buildRequest()
			->_sendRequest()
			->_processResponse();
	}

	/**
	 * Build order cancel payload.
	 *
	 * @return self
	 */
	protected function _buildRequest()
	{
		$this->_request = Mage::getModel('ebayenterprise_order/cancel_build_request', [
			'order' => $this->_order,
		])->build();
		return $this;
	}

	/**
	 * Send order cancel payload.
	 *
	 * @return self
	 */
	protected function _sendRequest()
	{
		$this->_response = Mage::getModel('ebayenterprise_order/cancel_send_request', [
			'request' => $this->_request,
		])->send();
		return $this;
	}

	/**
	 * Process order cancel response.
	 *
	 * @return self
	 */
	protected function _processResponse()
	{
		Mage::getModel('ebayenterprise_order/cancel_process_response', [
			'response' => $this->_response,
			'order' => $this->_order,
		])->process();
		return $this;
	}
}
