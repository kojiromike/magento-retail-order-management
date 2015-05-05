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
use \eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;

class EbayEnterprise_Order_Model_Cancel_Send_Request
	implements EbayEnterprise_Order_Model_Cancel_Send_IRequest
{
	/** @var IOrderCancelRequest */
	protected $_request;
	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_orderCfg;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_logContext;

	/**
	 * @param array $initParams Must have this key:
	 *                          - 'request' => IOrderCancelRequest
	 */
	public function __construct(array $initParams)
	{
		list($this->_request, $this->_orderHelper, $this->_orderCfg, $this->_coreHelper, $this->_logger, $this->_logContext) = $this->_checkTypes(
			$initParams['request'],
			$this->_nullCoalesce($initParams, 'order_helper', Mage::helper('ebayenterprise_order')),
			$this->_nullCoalesce($initParams, 'order_cfg', Mage::helper('ebayenterprise_order')->getConfigModel()),
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($initParams, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  IOrderCancelRequest
	 * @param  EbayEnterprise_Order_Helper_Data
	 * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @param  EbayEnterprise_MageLog_Helper_Data
	 * @param  EbayEnterprise_MageLog_Helper_Context
	 * @return array
	 */
	protected function _checkTypes(
		IOrderCancelRequest $request,
		EbayEnterprise_Order_Helper_Data $orderHelper,
		EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $logContext
	)
	{
		return [$request, $orderHelper, $orderCfg, $coreHelper, $logger, $logContext];
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
	 * @see EbayEnterprise_Order_Model_Cancel_Send_IRequest::send()
	 */
	public function send()
	{
		return $this->_sendRequest();
	}

	/**
	 * Get the api object.
	 *
	 * @return IBidirectionalApi
	 */
	protected function _getApi()
	{
		return $this->_coreHelper->getSdkApi(
			$this->_orderCfg->apiService,
			$this->_orderCfg->apiCancelOperation
		);
	}

	/**
	 * Sending the order cancel request and returning the response.
	 *
	 * @return IOrderCancelResponse | null
	 */
	protected function _sendRequest()
	{
		$response = null;
		try {
			$response = $this->_getApi()
				->setRequestBody($this->_request)
				->send()
				->getResponseBody();
		} catch(Exception $e) {
			$this->_processException($e);
		}
		return $response;
	}

	/**
	 * Determine the type of exception and logged it accordingly.
	 *
	 * @param  Exception
	 * @return self
	 */
	protected function _processException(Exception $e)
	{
		if ($e instanceof NetworkError) {
			$logMessage = 'Caught a network error sending order cancel. Will retry later.';
			$this->_logger->warning($logMessage, $this->_getLogContext($e));
		} elseif ($e instanceof UnsupportedOperation || $e instanceof UnsupportedHttpAction) {
			$logMessage = 'Order cancel request could not be sent. Please check your configuration.';
			$this->_logger->critical($logMessage, $this->_getLogContext($e));
		} else {
			$logMessage = 'Encountered a fatal error attempting to send order cancel request.';
			$this->_logger->warning($logMessage, $this->_getLogContext($e));
		}

		return $this;
	}

	/**
	 * Get the log meta data.
	 *
	 * @param  Exception
	 * @return array
	 */
	protected function _getLogContext(Exception $e)
	{
		return $this->_logContext->getMetaData(__CLASS__, [], $e);
	}
}
