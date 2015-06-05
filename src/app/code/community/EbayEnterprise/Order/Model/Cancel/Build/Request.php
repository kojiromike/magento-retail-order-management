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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCancelRequest;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;

class EbayEnterprise_Order_Model_Cancel_Build_Request implements EbayEnterprise_Order_Model_Cancel_Build_IRequest
{
    /** @var Mage_Sales_Model_Order */
    protected $_order;
    /** @var IOrderCancelRequest */
    protected $_payload;
    /** @var EbayEnterprise_Order_Helper_Data */
    protected $_orderHelper;
    /** @var IBidirectionalApi */
    protected $_api;

    /**
     * @param array $initParams Must have these keys:
     *                          - 'api' => IBidirectionalApi
     *                          - 'order' => Mage_Sales_Model_Order
     */
    public function __construct(array $initParams)
    {
        list($this->_api, $this->_order, $this->_orderHelper) = $this->_checkTypes(
            $initParams['api'],
            $initParams['order'],
            $this->_nullCoalesce($initParams, 'order_helper', Mage::helper('ebayenterprise_order'))
        );
        $this->_payload = $this->_api->getRequestBody();
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  IBidirectionalApi
     * @param  Mage_Sales_Model_Order
     * @param  EbayEnterprise_Order_Helper_Data
     * @return array
     */
    protected function _checkTypes(
        IBidirectionalApi $api,
        Mage_Sales_Model_Order $order,
        EbayEnterprise_Order_Helper_Data $orderHelper
    ) {
        return [$api, $order, $orderHelper];
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
            ->setReasonCode($this->_getReasonCode())
            ->setReason($this->_getReasonDescription());
        return $this;
    }

    /**
     * Get the cancel reason code from the sales/order object stash in class
     * property the self::_order. If it doesn't exist in sales/order object simply generate one.
     *
     * @return string
     */
    protected function _getReasonCode()
    {
        return $this->_order->getCancelReasonCode() ?: $this->_generateReasonCode();
    }

    /**
     * Get the cancel reason description from the order cancel description map
     * on on the order cancel code in the sales/order object.
     *
     * @return string | null
     */
    protected function _getReasonDescription()
    {
        $reasonCode = $this->_order->getCancelReasonCode();
        return $reasonCode? $this->_orderHelper->getCancelReasonDescription($reasonCode) : null;
    }
}
