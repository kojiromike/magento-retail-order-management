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

class EbayEnterprise_Inventory_Helper_Details_Item_Shipping
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    // @var array */
    protected $methods = [];

    public function __construct()
    {
        $this->logger = Mage::helper('ebayenterprise_magelog');
        $this->logContext = Mage::helper('ebayenterprise_magelog/context');
        $this->coreHelper = Mage::helper('eb2ccore');
    }

    public function getUsableMethod(Mage_Customer_Model_Address_Abstract $address)
    {
        $this->fetchAvailableShippingMethods();
        return $address->getShippingMethod() ?: $this->getFirstAvailable();
    }

    public function getMethodSdkId($shippingMethod)
    {
        $this->fetchAvailableShippingMethods();
        if (!isset($this->methods[$shippingMethod]['sdk_id'])) {
            $this->logger->error(
                'Unable to get the SDK identifier for shipping method "{shippingMethod}"',
                $this->logContext->getMetaData(__CLASS__, ['shippingMethod' => $shippingMethod])
            );
            throw Mage::exception('EbayEnterprise_Inventory', 'Unable to find a valid shipping method');
        }
        return $this->methods[$shippingMethod]['sdk_id'];
    }

    /**
     * get a display string of the given shipping method
     * @param  string
     * @return string
     */
    public function getMethodTitle($shippingMethod)
    {
        $this->fetchAvailableShippingMethods();
        return isset($this->methods[$shippingMethod]['display_text']) ?
            $this->methods[$shippingMethod]['display_text'] : '';
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
        $carrierMethods = $model->getAllowedMethods();
        $carrierTitle = $this->getCarrierTitle($carrierCode);
        foreach ((array) $carrierMethods as $methodCode => $method) {
            $this->storeShippingMethodInfo(
                $carrierCode . '_' . $methodCode,
                $this->buildNameString($carrierTitle, $method)
            );
        }
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
        $sdkId = $this->coreHelper->lookupShipMethod($shippingMethod);
        if (!$sdkId) {
            return;
        }
        $this->methods[$shippingMethod] = [
            'sdk_id' => $sdkId,
            'display_text' => $displayString,
        ];
    }

    /**
     * @return Mage_Shipping_Model_Config
     */
    protected function getShippingConfig()
    {
        return Mage::getSingleton('shipping/config');
    }

    /**
     * get the title for the carrier code as configured in
     * magento
     *
     * @param string
     * @return string
     */
    protected function getCarrierTitle($carrierCode)
    {
        return Mage::getStoreConfig("carriers/$carrierCode/title");
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
        return trim("$carrierTitle $methodName");
    }

    /**
     * get the first available shipping method
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
