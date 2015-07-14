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

use eBayEnterprise\RetailOrderManagement\Payload\IPayload;

abstract class EbayEnterprise_Order_Helper_Map_Abstract
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $creditcardConfig;

    public function __construct(array $initParams=[])
    {
        list($this->coreHelper, $this->creditCardConfig) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
            $this->nullCoalesce($initParams, 'creditcard_config', Mage::helper('ebayenterprise_creditcard')->getConfigModel())
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $creditcardConfig
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Get a unique value.
     *
     * @return string
     */
    protected function _getUniqueValue()
    {
        return uniqid('OCS_');
    }

    /**
     * Get a string value.
     *
     * @param  IPayload
     * @param  string
     * @return mixed
     */
    protected function _getStringValue(IPayload $payload, $getter)
    {
        return $payload->$getter();
    }

    /**
     * Get a date/time value.
     *
     * @param  IPayload
     * @param  string
     * @return string | null
     */
    protected function _getDatetimeValue(IPayload $payload, $getter)
    {
        $value = $this->_getStringValue($payload, $getter);
        return ($value instanceof DateTime) ? $value->format('c') : null;
    }

    /**
     * Get a float value.
     *
     * @param  IPayload
     * @param  string
     * @return mixed
     */
    protected function _getFloatValue(IPayload $payload, $getter)
    {
        return (float) $this->_getStringValue($payload, $getter);
    }

    /**
     * Get a boolean value.
     *
     * @param  IPayload
     * @param  string
     * @return mixed
     */
    protected function _getBooleanValue(IPayload $payload, $getter)
    {
        return $this->coreHelper->parseBool($this->_getStringValue($payload, $getter));
    }
}
