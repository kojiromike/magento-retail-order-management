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

class EbayEnterprise_Address_Model_Observer
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;
    /** @var EbayEnterprise_Address_Model_Validator */
    protected $validator;
    /** @var EbayEnterprise_Address_Model_Order_Address_Validation */
    protected $orderAddressValidation;

    /**
     * @param array $args may contains these keys:
     *                          - 'config' => EbayEnterprise_Eb2cCore_Model_Config_Registry
     *                          - 'validator' => EbayEnterprise_Address_Model_Validator
     *                          - 'order_address_validation' => EbayEnterprise_Address_Model_Order_Address_Validation
     */
    public function __construct(array $args = [])
    {
        list($this->config, $this->validator, $this->orderAddressValidation) = $this->checkTypes(
            $this->nullCoalesce($args, 'config', Mage::helper('ebayenterprise_address')->getConfigModel()),
            $this->nullCoalesce($args, 'validator', Mage::getModel('ebayenterprise_address/validator')),
            $this->nullCoalesce($args, 'order_address_validation', Mage::getModel('ebayenterprise_address/order_address_validation'))
        );
    }

    /**
     * Type checks for constructor args array.
     *
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param  EbayEnterprise_Address_Model_Validator
     * @param  EbayEnterprise_Address_Model_Order_Address_Validation
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config,
        EbayEnterprise_Address_Model_Validator $validator,
        EbayEnterprise_Address_Model_Order_Address_Validation $orderAddressValidation
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
     * @return bool Whether or not address validation is enabled
     */
    protected function _isEnabled()
    {
        return $this->config->isValidationEnabled;
    }

    /**
     * For frontend area: Observe address validation events and perform Address validation.
     * Event data is expected to include the address object to be validated.
     * @param Varien_Event_Observer $observer "Observer" object with access to the address to validate
     */
    public function validateAddress($observer)
    {
        $this->_validateAddress($observer);
    }

    /**
     * For Adminhtml area: Observe address validation events and perform address validation.
     * Event data is expected to include the address object to be validated.
     * @param Varien_Event_Observer $observer "Observer" object with access to the address to validate
     */
    public function validateAddressAdminhtml($observer)
    {
        $this->_validateAddress($observer, Mage_Core_Model_App_Area::AREA_ADMINHTML);
    }

    /**
     * The function to call the actual validation process if we are enabled to perform it.
     * @param Varien_Event_Observer $observer "Observer" object with access to the address to validate
     * @param optional Mage_Core_Model_App::AREA_xxx designation
     */
    protected function _validateAddress($observer, $area = null)
    {
        if (!$this->_isEnabled()) {
            return;
        }
        $address = $observer->getEvent()->getAddress();
        $validationError = $this->validator->validateAddress($address, $area);
        if ($validationError) {
            $address->addError($validationError);
        }
    }

    /**
     * Render the suggestions block to be added into the response.
     * @param Mage_Core_Controller_Varien_Action $controller
     * @return string - rendered block
     */
    protected function _getAddressBlockHtml(Mage_Core_Controller_Varien_Action $controller)
    {
        $layout = $controller->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_address_suggestions');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /**
     * When address validation suggestions are present, add a re-rendered
     * address block to the response which will include the address suggestions.
     * @param Varien_Event_Observer $observer "Observer" object with access to the OPC controller.
     */
    public function addSuggestionsToResponse($observer)
    {
        if (!$this->_isEnabled()) {
            return;
        }
        $validator = $this->validator;
        $controller = $observer->getEvent()->getControllerAction();
        $body = Mage::helper('core')->jsonDecode($controller->getResponse()->getBody());
        if (isset($body['error']) && !$validator->isValid()) {
            $body['suggestions'] = $this->_getAddressBlockHtml($controller);
            $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($body));
        }
        $validator->getAddressCollection()->setHasFreshSuggestions(false);
    }

    /**
     * Observe the 'customer_address_validation_after' event, get the address from the event, and
     * then pass it down to the ebayenterprise_address/order_address_validation::allowAddressValidation()
     * method.
     *
     * @param  Varien_Event_Observer
     * @return self
     */
    public function handleCustomerAddressValidationAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Address_Abstract */
        $address = $observer->getEvent()->getAddress();
        if ($address instanceof Mage_Customer_Model_Address_Abstract) {
            $this->orderAddressValidation->allowAddressValidation($address);
        }
        return $this;
    }
}
