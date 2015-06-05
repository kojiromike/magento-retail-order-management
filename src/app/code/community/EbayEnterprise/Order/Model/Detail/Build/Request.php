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

use eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderDetailRequest;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;

class EbayEnterprise_Order_Model_Detail_Build_Request implements EbayEnterprise_Order_Model_Detail_Build_IRequest
{
    /** @var string */
    protected $_orderId;
    /** @var IOrderDetailRequest */
    protected $_payload;
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $_factory;
    /** @var IBidirectionalApi */
    protected $_api;

    /**
     * @param array $initParams Must have these keys:
     *                          - 'api' => IBidirectionalApi
     *                          - 'order_id' => string
     */
    public function __construct(array $initParams)
    {
        list($this->_api, $this->_orderId) = $this->_checkTypes(
            $initParams['api'],
            $initParams['order_id']
        );
        $this->_payload = $this->_api->getRequestBody();
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  IBidirectionalApi
     * @param  string
     * @return array
     */
    protected function _checkTypes(IBidirectionalApi $api, $orderId)
    {
        return [$api, $orderId];
    }

    /**
     * @see EbayEnterprise_Order_Model_Detail_Build_IRequest::build()
     */
    public function build()
    {
        $this->_buildPayload();
        return $this->_payload;
    }

    /**
     * Populate order detail request payload.
     *
     * @return self
     */
    protected function _buildPayload()
    {
        $this->_payload->setOrderType(static::DEFAULT_ORDER_DETAIL_SEARCH_TYPE)
            ->setCustomerOrderId($this->_orderId);
        return $this;
    }
}
