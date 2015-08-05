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

use EcomDev_PHPUnit_Test_Case_Util as TestUtil;

class EbayEnterprise_Address_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function setUp()
    {
        $this->_setupBaseUrl();
    }

    protected function _mockConfig($enabled)
    {
        $config = $this->getModelMockBuilder('eb2ccore/config_registry')
            ->disableOriginalConstructor()
            ->setMethods(['__get', 'addConfigModel'])
            ->getMock();
        $config->expects($this->any())
            ->method('addConfigModel')
            ->will($this->returnSelf());
        $config->expects($this->any())
            ->method('__get')
            ->will($this->returnValue($enabled));
        $this->replaceByMock('model', 'eb2ccore/config_registry', $config);
        return $config;
    }

    /**
     * When disabled, observer method should do nothing.
     */
    public function testValidationValidationDisabled()
    {
        $this->_mockConfig(0);

        $observer = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $observer->expects($this->never())
            ->method('getEvent');
        $validator = $this->getModelMock('ebayenterprise_address/validator', ['validateAddress']);
        $validator->expects($this->never())
            ->method('validateAddress');

        Mage::getSingleton('ebayenterprise_address/observer')->validateAddress($observer);
    }

    /**
     * When disabled, observer method should do nothing.
     */
    public function testAddSuggestionsValidationDisabled()
    {
        $this->_mockConfig(0);

        $observer = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $observer->expects($this->never())
            ->method('getEvent');
        $validator = $this->getModelMock('ebayenterprise_address/validator', ['hasSuggestions']);
        $validator->expects($this->never())
            ->method('hasSuggestions');
        $addressObserver = $this->getModelMock('ebayenterprise_address/observer', ['_getAddressBlockHtml']);
        $addressObserver->expects($this->never())
            ->method('_getAddressBlockHtml');

        $addressObserver->addSuggestionsToResponse($observer);
    }

    /**
     * Test that when address validation fails, the errors are added to the address
     * object's set of errors.
     */
    public function testValidateAddressValidationErrors()
    {
        $this->_mockConfig(1);

        $expectedError = 'Error from validation';
        $address = $this->getModelMock('customer/address', ['addError']);
        $address->expects($this->once())
            ->method('addError')
            ->with($this->identicalTo($expectedError))
            ->will($this->returnSelf());

        $event = new Varien_Object();
        $event->setAddress($address);

        $observer = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $observer->expects($this->any())
            ->method('getEvent')
            ->will($this->returnValue($event));

        $validator = $this->getModelMock('ebayenterprise_address/validator', ['validateAddress']);
        $validator->expects($this->once())
            ->method('validateAddress')
            ->with($this->equalTo($address))
            ->will($this->returnValue($expectedError));

        /** @var EbayEnterprise_Address_Model_Observer */
        $addressObserver = Mage::getModel('ebayenterprise_address/observer', ['validator' => $validator]);
        $this->assertNull($addressObserver->validateAddress($observer));
    }

    /**
     * Ensure when validation is successful, that no errors are added to the address
     */
    public function testValidateAddressSuccess()
    {
        $this->_mockConfig(1);

        $address = $this->getModelMock('customer/address', ['addError']);
        $address->expects($this->never())
            ->method('addError');

        $event = new Varien_Object();
        $event->setAddress($address);

        $observer = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $observer->expects($this->any())
            ->method('getEvent')
            ->will($this->returnValue($event));

        $validator = $this->getModelMock('ebayenterprise_address/validator', ['validateAddress']);
        $validator->expects($this->once())
            ->method('validateAddress')
            ->with($this->equalTo($address))
            ->will($this->returnValue(null));

        /** @var EbayEnterprise_Address_Model_Observer */
        $addressObserver = Mage::getModel('ebayenterprise_address/observer', ['validator' => $validator]);
        $this->assertNull($addressObserver->validateAddress($observer));
    }

    /**
     * When address validation was successful/there are no errors in the response
     * the response body should go unchanged.
     */
    public function testResponseSuggestionsNoErrors()
    {
        $this->_mockConfig(1);

        $suggestionGroup = $this->getModelMock('ebayenterprise_address/suggestion_group', ['setHasFreshSuggestions']);
        $suggestionGroup->expects($this->once())
            ->method('setHasFreshSuggestions')
            ->with($this->isFalse())
            ->will($this->returnSelf());
        $validator = $this->getModelMock('ebayenterprise_address/validator', ['isValid', 'getAddressCollection']);
        // when there aren't errors in the response, this shouldn't get called
        $validator->expects($this->never())
            ->method('isValid');
        $validator->expects($this->once())
            ->method('getAddressCollection')
            ->will($this->returnValue($suggestionGroup));

        $response = $this->getMock('Mage_Core_Controller_Response_Http', ['getBody', 'setBody']);
        // response body must be JSON and in this case not inlcude an "error" property
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue('{}'));
        // body of the response should not be changed
        $response->expects($this->never())
            ->method('setBody');

        // core/layout_update should not be touched in this scenario
        $update = $this->getModelMock('core/layout_update', ['load']);
        $update->expects($this->never())
            ->method('load');

        // core/layout should not be touched in this scenario
        $layout = $this->getModelMock(
            'core/layout',
            ['getUpdate', 'generateXml', 'generateBlocks', 'getOutput']
        );
        $layout->expects($this->never())
            ->method('getUpdate');
        $layout->expects($this->never())
            ->method('generateXml');
        $layout->expects($this->never())
            ->method('generateBlocks');
        $layout->expects($this->never())
            ->method('getOutput');

        // controller should be asked for the response but shouldn't generage a layout
        $controller = $this->getMockBuilder('Mage_Checkout_Controller_Action')
            ->disableOriginalConstructor()
            ->setMethods(['getResponse', 'getLayout'])
            ->getMock();
        $controller->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));
        $controller->expects($this->never())
            ->method('getLayout');

        $event = $this->getMock('Varien_Event', ['getControllerAction']);
        $event->expects($this->once())
            ->method('getControllerAction')
            ->will($this->returnValue($controller));

        $observer = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $observer->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($event));

        /** @var EbayEnterprise_Address_Model_Observer */
        $addressObserver = Mage::getModel('ebayenterprise_address/observer', ['validator' => $validator]);
        $this->assertNull($addressObserver->addSuggestionsToResponse($observer));
    }

    /**
     * When the response is invalid and there are suggestions to show,
     * the response JSON should be modified to include the markup for the suggestions.
     */
    public function testResponseWithErrors()
    {
        $validator = $this->getModelMock('ebayenterprise_address/validator', ['isValid']);
        // when there aren't errors in the response, this shouldn't get called
        $validator->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $this->_mockConfig(1);

        $response = $this->getMock('Mage_Core_Controller_Response_Http', ['getBody', 'setBody']);
        // response body must be JSON and in this case not inlcude an "error" property
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue('{"error":1}'));
        // body of the response should not be changed
        $response->expects($this->once())
            ->method('setBody');

        // core/layout_update should not be touched in this scenario
        $update = $this->getModelMock('core/layout_update', ['load']);
        $update->expects($this->once())
            ->method('load');

        // core/layout should not be touched in this scenario
        $layout = $this->getModelMock(
            'core/layout',
            ['getUpdate', 'generateXml', 'generateBlocks', 'getOutput']
        );
        $layout->expects($this->once())
            ->method('getUpdate')
            ->will($this->returnValue($update));
        $layout->expects($this->once())
            ->method('generateXml');
        $layout->expects($this->once())
            ->method('generateBlocks');
        $layout->expects($this->once())
            ->method('getOutput');

        // controller should be asked for the response and the layout
        $controller = $this->getMockBuilder('Mage_Checkout_Controller_Action')
            ->disableOriginalConstructor()
            ->setMethods(['getResponse', 'getLayout'])
            ->getMock();
        $controller->expects($this->exactly(2))
            ->method('getResponse')
            ->will($this->returnValue($response));
        $controller->expects($this->once())
            ->method('getLayout')
            ->will($this->returnValue($layout));

        $event = $this->getMock('Varien_Event', ['getControllerAction']);
        $event->expects($this->once())
            ->method('getControllerAction')
            ->will($this->returnValue($controller));

        $observer = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $observer->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($event));

        /** @var EbayEnterprise_Address_Model_Observer */
        $addressObserver = Mage::getModel('ebayenterprise_address/observer', ['validator' => $validator]);
        $this->assertNull($addressObserver->addSuggestionsToResponse($observer));
    }

    /**
     * Suggestions should not be added to the OPC response when the address is valid,
     * even if there are errors in the response.
     */
    public function testResposeValidAddressWithErrors()
    {
        $this->_mockConfig(1);

        $validator = $this->getModelMock('ebayenterprise_address/validator', ['isValid']);
        // when there aren't errors in the response, this shouldn't get called
        $validator->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $response = $this->getMock('Mage_Core_Controller_Response_Http', ['getBody', 'setBody']);
        // response body must be JSON and in this case not inlcude an "error" property
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue('{"error":1}'));
        // body of the response should not be changed
        $response->expects($this->never())
            ->method('setBody');

        // core/layout_update should not be touched in this scenario
        $update = $this->getModelMock('core/layout_update', ['load']);
        $update->expects($this->never())
            ->method('load');

        // core/layout should not be touched in this scenario
        $layout = $this->getModelMock(
            'core/layout',
            ['getUpdate', 'generateXml', 'generateBlocks', 'getOutput']
        );
        $layout->expects($this->never())
            ->method('getUpdate');
        $layout->expects($this->never())
            ->method('generateXml');
        $layout->expects($this->never())
            ->method('generateBlocks');
        $layout->expects($this->never())
            ->method('getOutput');

        // controller should be asked for the response but shouldn't generage a layout
        $controller = $this->getMockBuilder('Mage_Checkout_Controller_Action')
            ->disableOriginalConstructor()
            ->setMethods(['getResponse', 'getLayout'])
            ->getMock();
        $controller->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));
        $controller->expects($this->never())
            ->method('getLayout');

        $event = $this->getMock('Varien_Event', ['getControllerAction']);
        $event->expects($this->once())
            ->method('getControllerAction')
            ->will($this->returnValue($controller));

        $observer = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $observer->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($event));

        /** @var EbayEnterprise_Address_Model_Observer */
        $addressObserver = Mage::getModel('ebayenterprise_address/observer', ['validator' => $validator]);
        $this->assertNull($addressObserver->addSuggestionsToResponse($observer));
    }

    /**
     * Scenario: Observe customer address validation after event
     * Given a Varien Event Observer object
     * When observing customer address validation after event
     * Then get the customer address from the passed in observer object
     * And if the customer address is a valid object, then pass it down to the
     * ebayenterprise_address/order_address_validation::allowAddressValidation()
     * method.
     */
    public function testHandleCustomerAddressValidationAfter()
    {
        /** @var Mage_Sales_Model_Quote_Address */
        $address = Mage::getModel('sales/quote_address');

        /** @var EbayEnterprise_Address_Model_Order_Address_Validation */
        $orderAddressValidation = $this->getModelMock('ebayenterprise_address/order_address_validation', ['allowAddressValidation']);
        $orderAddressValidation->expects($this->once())
            ->method('allowAddressValidation')
            ->with($this->identicalTo($address))
            ->will($this->returnSelf());

        /** @var Varien_Event_Observer */
        $observer = new Varien_Event_Observer(['event' => new Varien_Event([
            'address' => $address,
        ])]);

        /** @var EbayEnterprise_Address_Model_Observer */
        $addressObserver = Mage::getModel('ebayenterprise_address/observer', [
            'order_address_validation' => $orderAddressValidation,
        ]);
        $this->assertSame($addressObserver, $addressObserver->handleCustomerAddressValidationAfter($observer));
    }
}
