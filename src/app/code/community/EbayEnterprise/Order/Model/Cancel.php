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
     *                          - 'order' => Mage_Sales_Model_Order
     */
    public function __construct(array $initParams)
    {
        list($this->_order, $this->_factory, $this->_orderCfg, $this->_coreHelper) = $this->_checkTypes(
            $initParams['order'],
            $this->_nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory')),
            $this->_nullCoalesce($initParams, 'order_cfg', Mage::helper('ebayenterprise_order')->getConfigModel()),
            $this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore'))
        );
        $this->_api = $this->getApi();
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  Mage_Sales_Model_Order
     * @param  EbayEnterprise_Order_Helper_Factory
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @return array
     */
    protected function _checkTypes(
        Mage_Sales_Model_Order $order,
        EbayEnterprise_Order_Helper_Factory $factory,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
    ) {
        return [$order, $factory, $orderCfg, $coreHelper];
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
            $this->_orderCfg->apiCancelOperation
        );
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
        $this->_request = $this->_factory
            ->getNewCancelBuildRequest($this->_api, $this->_order)
            ->build();
        return $this;
    }

    /**
     * Send order cancel payload.
     *
     * @return self
     */
    protected function _sendRequest()
    {
        $this->_response = $this->_factory
            ->getNewCancelSendRequest($this->_api, $this->_request)
            ->send();
        return $this;
    }

    /**
     * Process order cancel response.
     *
     * @return self
     */
    protected function _processResponse()
    {
        $this->_factory
            ->getNewCancelProcessResponse($this->_response, $this->_order)
            ->process();
        return $this;
    }
}
