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

class EbayEnterprise_Order_Overrides_Model_Enterprise_Rma extends Enterprise_Rma_Model_Rma
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_config;

    public function __construct(array $initParams = [])
    {
        list($this->_config) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'config', Mage::helper('ebayenterprise_order')->getConfigModel())
        );
        parent::__construct($this->_removeKnownKeys($initParams));
    }

    /**
     * Remove the all the require and optional keys from the $initParams
     * parameter.
     *
     * @param  array
     * @return array
     */
    protected function _removeKnownKeys(array $initParams)
    {
        foreach (['config'] as $key) {
            if (isset($initParams[$key])) {
                unset($initParams[$key]);
            }
        }
        return $initParams;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function _checkTypes(EbayEnterprise_Eb2cCore_Model_Config_Registry $config)
    {
        return [$config];
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
     * Sending email with RMA data
     *
     * @return Enterprise_Rma_Model_Rma
     */
    public function sendNewRmaEmail()
    {
        if ($this->_config->transactionalEmailer === 'eb2c') {
            return $this;
        } else {
            return parent::sendNewRmaEmail();
        }
    }

    /**
     * Sending authorizing email with RMA data
     *
     * @return Enterprise_Rma_Model_Rma
     */
    public function sendAuthorizeEmail()
    {
        if ($this->_config->transactionalEmailer === 'eb2c') {
            return $this;
        } else {
            return parent::sendAuthorizeEmail();
        }
    }
}
