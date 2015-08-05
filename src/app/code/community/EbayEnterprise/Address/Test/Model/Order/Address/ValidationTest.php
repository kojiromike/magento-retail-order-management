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

class EbayEnterprise_Address_Test_Model_Order_Address_ValidationTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Scenario: Allow validation for an address
     * Given an address object
     * When allowing validation for an address
     * Then check address against ROM address validation
     * And, if ROM said it doesn't need validation simply set the
     * should ignore validation flag on the address to true.
     */
    public function testAllowAddressValidation()
    {
        /** @var string */
        $actionName = 'saveOrder';
        /** @var bool */
        $needValidation = false;

        /** @var Mage_Sales_Model_Quote_Address */
        $address = $this->getModelMock('sales/quote_address', ['setShouldIgnoreValidation']);
        $address->expects($this->once())
            ->method('setShouldIgnoreValidation')
            ->with($this->identicalTo(true))
            ->will($this->returnSelf());

        /** @var Mage_Core_Controller_Request_Http */
        $request = $this->getMock('Mage_Core_Controller_Request_Http', ['getActionName']);
        $request->expects($this->once())
            ->method('getActionName')
            ->will($this->returnValue($actionName));

        /** @var Mage_Core_Model_App */
        $app = $this->getModelMock('core/app', ['getRequest']);
        $app->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        /** @var EbayEnterprise_Address_Model_Validator */
        $validator = $this->getModelMock('ebayenterprise_address/validator', ['shouldValidateAddress']);
        $validator->expects($this->once())
            ->method('shouldValidateAddress')
            ->with($this->identicalTo($address))
            ->will($this->returnValue($needValidation));

        /** @var EbayEnterprise_Address_Model_Order_Address_Validation */
        $orderAddressValidation = Mage::getModel('ebayenterprise_address/order_address_validation', [
            'app' => $app,
            'validator' => $validator,
        ]);
        $this->assertSame($orderAddressValidation, $orderAddressValidation->allowAddressValidation($address));
    }
}
