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

class EbayEnterprise_Order_Model_Detail_Process_Response_Payment extends Mage_Sales_Model_Order_Payment
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_config;

    /**
     * @param array $initParams Must have this key:
     *                          - 'order' => EbayEnterprise_Order_Model_Detail_Process_IResponse
     */
    public function __construct(array $initParams = [])
    {
        list($this->_config) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'config', Mage::helper('ebayenterprise_order')->getConfigModel())
        );
        parent::__construct($this->_removeKnownKeys($initParams));
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config
    ) {
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
     * @see parent::_construct()
     * overriding this method to update ROM payment type
     * with Magento payment type.
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        // @see parent::_order property.
        $this->setOrder($this->getData('order'));
        // remove the order key, we no long need it.
        $this->unsetData('order');
        if ($this->getPaymentTypeName()) {
            $this->_updatePayments();
        }
    }

    /**
     * get the payment method name for the payment type
     * @param  string $paymentTypeName
     * @return string
     * @throws EbayEnterprise_Order_Exception_Critical_Exception If payment extraction mappings are not configured
     */
    protected function _getPaymentMethod($paymentTypeName)
    {
        $mappings = $this->_config->mapPaymentMethods;
        if (!isset($mappings[$paymentTypeName])) {
            throw Mage::exception(
                'EbayEnterprise_Order_Exception_Critical',
                "$paymentTypeName elements are not mapped to a valid Magento payment method."
            );
        }
        return $mappings[$paymentTypeName];
    }

    /**
     * get update payment information
     * @return self
     */
    protected function _updatePayments()
    {
        $cards = [];
        $this->setMethod($this->_getPaymentMethod($this->getPaymentTypeName()));
        switch ($this->getPaymentTypeName()) {
            case 'CreditCard':
                $ccData = [
                    'cc_last4' => substr($this->getAccountUniqueId(), -4),
                    'cc_type' => $this->getTenderType(),
                ];
                // Expiration date from SDK is a single DateTime. Magento cc payment
                // expects separate month and year. When provided from the SDK,
                // map the DateTime to the expected month and year data properties.
                $expirationDate = $this->getExpirationDate();
                if ($expirationDate instanceof DateTime) {
                    $ccData['cc_exp_month'] = $expirationDate->format('m');
                    $ccData['cc_exp_year'] = $expirationDate->format('Y');
                }
                $this->addData($ccData);
                break;
            case 'StoredValueCard':
                $cards[] = [
                    'ba' => $this->getAmount(),
                    'a' => $this->getAmount(),
                    'c' => $this->getAccountUniqueId(),
                ];
                break;
        }
        $this->_setCards($this->getOrder(), $cards);
        return $this;
    }

    protected function _setCards(Varien_Object $to, $value)
    {
        $serializedValue = serialize($value);
        $to->setGiftCards($serializedValue);
    }
}
