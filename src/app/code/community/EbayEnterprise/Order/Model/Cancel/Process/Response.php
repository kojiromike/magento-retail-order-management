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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCancelResponse;

class EbayEnterprise_Order_Model_Cancel_Process_Response implements EbayEnterprise_Order_Model_Cancel_Process_IResponse
{
    const CANCELLED_RESPONSE = 'CANCELLED';

    /** @var IOrderCancelResponse */
    protected $_response;
    /** @var Mage_Sales_Model_Order */
    protected $_order;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_logContext;

    /**
     * @param array $initParams Must have these keys:
     *                          - 'response' => IOrderCancelResponse
     *                          - 'order' => Mage_Sales_Model_Order
     */
    public function __construct(array $initParams)
    {
        list($this->_response, $this->_order, $this->_logger, $this->_logContext) = $this->_checkTypes(
            $initParams['response'],
            $initParams['order'],
            $this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->_nullCoalesce($initParams, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  IOrderCancelResponse
     * @param  Mage_Sales_Model_Order
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function _checkTypes(
        IOrderCancelResponse $response,
        Mage_Sales_Model_Order $order,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return [$response, $order, $logger, $logContext];
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
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
     * @see EbayEnterprise_Order_Model_Cancel_Process_IResponse::process()
     */
    public function process()
    {
        return $this->_processResponse();
    }

    /**
     * Check the response status if equal to static::CANCELLED_RESPONSE,
     * we cancel the order otherwise we simply log the response status.
     *
     * @return self
     */
    protected function _processResponse()
    {
        switch ($this->_response->getResponseStatus()) {
            case static::CANCELLED_RESPONSE:
                $this->_cancelOrder();
                break;
            default:
                $this->_logResponse();
                break;
        }
        return $this;
    }

    /**
     * Cancel the order if is in the current Magento store, otherwise, simply
     * set the state of the order to a state of canceled.
     *
     * @return self
     */
    protected function _cancelOrder()
    {
         if ($this->_order->getId()) {
            // Only save order that's in this magento store
            $this->_order->cancel()->save();
        } else {
            // The order is not in this magento store, simply set state of this empty order to cancel
            $this->_order->setState(Mage_Sales_Model_Order::STATE_CANCELED);
        }
        return $this;
    }

    /**
     * Log order cancel response status.
     *
     * @return self
     */
    protected function _logResponse()
    {
        $logMessage = "Order could not be canceled ROM order cancel response return this status '{$this->_response->getResponseStatus()}'.";
        $this->_logger->warning($logMessage, $this->_logContext->getMetaData(__CLASS__));
        return $this;
    }
}
