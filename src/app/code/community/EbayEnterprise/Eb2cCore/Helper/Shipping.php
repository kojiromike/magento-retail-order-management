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

class EbayEnterprise_Eb2cCore_Helper_Shipping
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var array */
    protected $methods = [];
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;

    public function __construct(array $init = [])
    {
        list(
            $this->config,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce($init, 'config', Mage::helper('eb2ccore')->getConfigModel()),
            $this->nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($init, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    protected function checkTypes(
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param  array
     * @param  string
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * get a magento shipping method identifer for either:
     * - the shipping method used by $address
     * - the first of all valid shipping methods
     *
     * @param Mage_Customer_Model_Address_Abstract
     * @return string
     */
    public function getUsableMethod(Mage_Customer_Model_Address_Abstract $address)
    {
        $this->fetchAvailableShippingMethods();
        return $address->getShippingMethod() ?: $this->getFirstAvailable();
    }

    /**
     * get the ROM identifier for the given magento shipping method code
     * return null if $shippingMethod evaluates to false
     *
     * @param string
     * @return string
     * @throws EbayEnterprise_Eb2cCore_Exception If an id for the shipping method cannot be found.
     */
    public function getMethodSdkId($shippingMethod)
    {
        $this->fetchAvailableShippingMethods();
        if (!$shippingMethod) {
            $this->logger->error(
                'SDK shipping method id for empty shipping method requested.',
                $this->logContext->getMetaData(__CLASS__)
            );
            throw Mage::exception('EbayEnterprise_Eb2cCore', 'Unable to find a valid shipping method');
        }
        if (!isset($this->methods[$shippingMethod]['sdk_id'])) {
            $this->logger->error(
                'Unable to get the SDK identifier for shipping method {shipping_method}',
                $this->logContext->getMetaData(__CLASS__, ['shipping_method' => $shippingMethod])
            );
            throw Mage::exception('EbayEnterprise_Eb2cCore', 'Unable to find a valid shipping method');
        }
        return $this->methods[$shippingMethod]['sdk_id'];
    }

    /**
     * Get the code of the shipping method to use for virtual items.
     *
     * @return string
     */
    public function getVirtualMethodSdkId()
    {
        return $this->config->virtualShippingMethodId;
    }

    /**
     * Get the description of the shipping method to use for virtual items.
     *
     * @return string
     */
    public function getVirtualMethodDescription()
    {
        return $this->config->virtualShippingMethodDescription;
    }

    /**
     * get a display string of the given shipping method
     * return null if $shippingMethod evaluates to false
     *
     * @param  string
     * @return string|null
     */
    public function getMethodTitle($shippingMethod)
    {
        $this->fetchAvailableShippingMethods();
        return isset($this->methods[$shippingMethod]['display_text']) ?
            $this->methods[$shippingMethod]['display_text'] : null;
    }

    /**
     * collect all available shipping methods that are mapped to a
     * ROM shipping method
     *
     * @link http://blog.ansyori.com/magento-get-list-active-shipping-methods-payment-methods/
     * @return array
     */
    protected function fetchAvailableShippingMethods()
    {
        if (!$this->methods) {
            $activeCarriers = $this->getShippingConfig()->getActiveCarriers();
            foreach ($activeCarriers as $carrierCode => $carrierModel) {
                $this->addShippingMethodsFromCarrier($carrierCode, $carrierModel);
            }
        }
        return $this->methods;
    }

    /**
     * add valid shipping methods from the carrier to the list.
     *
     * @param string
     * @param Mage_Shipping_Model_Carrier_Abstract
     */
    protected function addShippingMethodsFromCarrier($carrierCode, Mage_Shipping_Model_Carrier_Abstract $model)
    {
        $carrierTitle = $this->getCarrierTitle($model);
        foreach ((array) $model->getAllowedMethods() as $methodCode => $method) {
            $this->storeShippingMethodInfo(
                $carrierCode . '_' . $methodCode,
                $this->buildNameString($carrierTitle, $method)
            );
        }
    }

    /**
     * get the title from the carrier
     *
     * @param Mage_Shipping_Model_Carrier_Abstract
     * @return string
     */
    protected function getCarrierTitle(Mage_Shipping_Model_Carrier_Abstract $model)
    {
        // ensure consistent scope when we're querying config data
        return $model->setStore($this->config->getStore())->getConfigData('title');
    }
    /**
     * add the shipping method to the the list if it is a
     * valid ROM shipping method
     *
     * @param string
     * @param string
     */
    protected function storeShippingMethodInfo($shippingMethod, $displayString)
    {
        $sdkId = $this->lookupShipMethod($shippingMethod);
        if (!$sdkId) {
            $this->logger->warning(
                'Encountered active shipping method with no ROM mapping.',
                $this->logContext->getMetaData(__CLASS__, ['shipping_method' => $shippingMethod, 'display_string' => $displayString])
            );
            return;
        }
        $this->methods[$shippingMethod] = [
            'sdk_id' => $sdkId,
            'display_text' => $displayString,
        ];
    }

    /**
     * Return the eb2c ship method configured to correspond to a known Magento ship method.
     *
     * @param string Magento shipping method code
     * @return string ROM shipping method identifier
     */
    protected function lookupShipMethod($mageShipMethod)
    {
        return $this->nullCoalesce((array) $this->config->shippingMethodMap, $mageShipMethod, '');
    }

    /**
     * @return Mage_Shipping_Model_Config
     */
    protected function getShippingConfig()
    {
        return Mage::getSingleton('shipping/config');
    }

    /**
     * build a string to display for the shipping method
     *
     * @param string
     * @param string
     * @return string
     */
    protected function buildNameString($carrierTitle, $methodName)
    {
        // add a hyphen to make it look the way it does on the order
        // review page.
        return trim(
            $carrierTitle
            . ($carrierTitle && $methodName ? ' - ' : '')
            .  $methodName
        );
    }

    /**
     * get the first available magento shipping method code
     *
     * @param  array
     * @return string
     */
    protected function getFirstAvailable()
    {
        if ($this->methods) {
            reset($this->methods);
            return key($this->methods);
        }
        return null;
    }
}
