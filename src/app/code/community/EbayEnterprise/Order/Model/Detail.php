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

class EbayEnterprise_Order_Model_Detail implements EbayEnterprise_Order_Model_IDetail
{
    const ORDER_DETAIL_NOT_FOUND_EXCEPTION = 'Order "%s" was not found.';

    /** @var IOrderDetailRequest */
    protected $_request;
    /** @var IOrderDetailResponse */
    protected $_response;
    /** @var string */
    protected $_orderId;
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $_factory;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_orderCfg;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var IBidirectionalApi */
    protected $_api;

    /**
     * @param array $initParams Must have this key:
     *                          - 'order_id' => string
     */
    public function __construct(array $initParams)
    {
        list($this->_orderId, $this->_factory, $this->_orderCfg, $this->_coreHelper) = $this->_checkTypes(
            $initParams['order_id'],
            $this->_nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory')),
            $this->_nullCoalesce($initParams, 'order_cfg', Mage::helper('ebayenterprise_order')->getConfigModel()),
            $this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore'))
        );
        $this->_api = $this->getApi();
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  string
     * @param  EbayEnterprise_Order_Helper_Factory
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @return array
     */
    protected function _checkTypes(
        $orderId,
        EbayEnterprise_Order_Helper_Factory $factory,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
    ) {
        return [$orderId, $factory, $orderCfg, $coreHelper];
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
     * @see EbayEnterprise_Order_Model_IApi::_getApi()
     */
    public function getApi()
    {
        return $this->_coreHelper->getSdkApi(
            $this->_orderCfg->apiService,
            $this->_orderCfg->apiDetailOperation
        );
    }

    /**
     * @see EbayEnterprise_Order_Model_IDetail::process()
     */
    public function process()
    {
        return $this->_buildRequest()
            ->_sendRequest()
            ->_processResponse();
    }

    /**
     * Build order detail payload request and stash the
     * request to the class property self::$_request.
     *
     * @return self
     */
    protected function _buildRequest()
    {
        $this->_request = $this->_factory
            ->getNewDetailBuildRequest($this->_api, $this->_orderId)
            ->build();
        return $this;
    }

    /**
     * Send order detail payload request and stash the
     * return response to the class property self::$_response.
     *
     * @return self
     */
    protected function _sendRequest()
    {
        $this->_response = $this->_factory
            ->getNewDetailSendRequest($this->_api, $this->_request)
            ->send();
        return $this;
    }

    /**
     * Process order detail payload response.
     *
     * @return EbayEnterprise_Order_Model_Detail_Process_IResponse
     * @throws EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception
     */
    protected function _processResponse()
    {
        if (!$this->_response instanceof IOrderDetailResponse) {
            throw Mage::exception(
                'EbayEnterprise_Order_Exception_Order_Detail_Notfound',
                sprintf(static::ORDER_DETAIL_NOT_FOUND_EXCEPTION, $this->_orderId)
            );
        }
        return $this->_factory
            ->getNewDetailProcessResponse($this->_response)
            ->process();
    }
}
