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

class EbayEnterprise_Address_Model_Order_Address_Validation
{
    /** @var Mage_Core_Model_App */
    protected $app;
    /** @var EbayEnterprise_Address_Model_Validator */
    protected $validator;

    /**
     * @param array $args may contains these keys:
     *                          - 'app' => Mage_Core_Model_App
     *                          - 'validator' => EbayEnterprise_Address_Model_Validator
     */
    public function __construct(array $args = [])
    {
        list($this->app, $this->validator) = $this->checkTypes(
            $this->nullCoalesce($args, 'app', Mage::app()),
            $this->nullCoalesce($args, 'validator', Mage::getModel('ebayenterprise_address/validator'))
        );
    }

    /**
     * Type checks for constructor args array.
     *
     * @param  Mage_Core_Model_App
     * @param  EbayEnterprise_Address_Model_Validator
     * @return array
     */
    protected function checkTypes(
        Mage_Core_Model_App $app,
        EbayEnterprise_Address_Model_Validator $validator
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param array      $arr
     * @param string|int $field Valid array key
     * @param mixed      $default
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Set the passed in address object to ignore validation only when
     * we are currently saving an order and ROM Address validation is not
     * needed.
     *
     * @param  Mage_Customer_Model_Address_Abstract
     * @return self
     */
    public function allowAddressValidation(Mage_Customer_Model_Address_Abstract $address)
    {
       /** @var Mage_Core_Controller_Request_Http */
        $request = $this->app->getRequest();
        /** @var bool */
        $needValidation = $this->validator->shouldValidateAddress($address);
        // We only want to ignore address validation when we are actually creating an order.
        // The assumption is if we get to this point, then, validating the address is
        // unnecessary if it is already valid in ROM.
        if (!$needValidation && $request->getActionName() === 'saveOrder') {
            $address->setShouldIgnoreValidation(true);
        }
        return $this;
    }
}
