<?php

class TrueAction_Eb2c_Address_Test_Model_ObserverTest
	extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * When the event already has errors in it, nothing should happen.
	 * @test
	 */
	public function testValidateAddressExistingErrors()
	{
		$errorContainer = $this->getMock('Varien_Object', array('getErrors'));
		$errorContainer->expects($this->any())
			->method('getErrors')
			->will($this->returnValue(array('Previous error.')));
		$event = $this->getMock('Varien_Event', array('getErrorContainer'));
		$event->expects($this->any())
			->method('getErrorContainer')
			->will($this->returnValue($errorContainer));
		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));

		$validator = $this->getModelMock('eb2caddress/validator', array('validateAddress'));
		$validator->expects($this->never())
			->method('validateAddress');

		$addressObserver = Mage::getSingleton('eb2caddress/observer');
		$addressObserver->validateAddress($observer);
	}

	/**
	 * Make sure that when there are not pre-existing error messages that validation
	 * gets called and that when validation returns an error message that
	 * the error message gets added back to the observer object.
	 * @test
	 */
	public function testValidateAddressValidationErrors()
	{
		$expectedError = 'Error from validation';

		$errorContainer = $this->getMock('Varien_Object', array('getErrors', 'setErrors'));
		$errorContainer->expects($this->any())
			->method('getErrors')
			->will($this->returnValue(array()));

		$errorContainer->expects($this->once())
			->method('setErrors')
			->with(array($expectedError));

		$address = $this->getModelMock('customer/address');

		$event = $this->getMock('Varien_Event', array('getErrorContainer', 'getAddress'));

		$event->expects($this->any())
			->method('getErrorContainer')
			->will($this->returnValue($errorContainer));

		$event->expects($this->any())
			->method('getAddress')
			->will($this->returnValue($address));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));

		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));

		$validator = $this->getModelMock('eb2caddress/validator', array('validateAddress'));

		$validator->expects($this->once())
			->method('validateAddress')
			->with($this->equalTo($address))
			->will($this->returnValue($expectedError));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$addressObserver = Mage::getSingleton('eb2caddress/observer');
		$addressObserver->validateAddress($observer);
	}

	/**
	 * Ensure when validation is successful, that no errors are added to the
	 * errors container.
	 * @test
	 */
	public function testValidateAddressSuccess()
	{
		$errorContainer = $this->getMock('Varien_Object', array('getErrors', 'setErrors'));
		$errorContainer->expects($this->any())
			->method('getErrors')
			->will($this->returnValue(array()));

		$errorContainer->expects($this->never())
			->method('setErrors');

		$address = $this->getModelMock('customer/address');

		$event = $this->getMock('Varien_Event', array('getErrorContainer', 'getAddress'));

		$event->expects($this->any())
			->method('getErrorContainer')
			->will($this->returnValue($errorContainer));

		$event->expects($this->any())
			->method('getAddress')
			->will($this->returnValue($address));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));

		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));

		$validator = $this->getModelMock('eb2caddress/validator', array('validateAddress'));

		$validator->expects($this->once())
			->method('validateAddress')
			->with($this->equalTo($address))
			->will($this->returnValue(null));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$addressObserver = Mage::getSingleton('eb2caddress/observer');
		$addressObserver->validateAddress($observer);
	}

	/**
	 * When address validation was successful/there are no errors in the response
	 * the response body should go unchanged.
	 */
	public function testResponseSuggestionsNoErrors()
	{
		$validator = $this->getModelMock('eb2caddress/validator', array('hasSuggestions'));
		// when there aren't errors in the response, this shouldn't get called
		$validator->expects($this->never())
			->method('hasSuggestions');
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$response = $this->getMock('Mage_Core_Controller_Response_Http', array('getBody', 'setBody'));
		// response body must be JSON and in this case not inlcude an "error" property
		$response->expects($this->once())
			->method('getBody')
			->will($this->returnValue('{}'));
		// body of the response should not be changed
		$response->expects($this->never())
			->method('setBody');

		// core/layout_update should not be touched in this scenario
		$update = $this->getModelMock('core/layout_update', array('load'));
		$update->expects($this->never())
			->method('load');

		// core/layout should not be touched in this scenario
		$layout = $this->getModelMock(
			'core/layout',
			array('getUpdate', 'generateXml', 'generateBlocks', 'getOutput')
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
			->setMethods(array('getResponse', 'getLayout'))
			->getMock();
		$controller->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));
		$controller->expects($this->never())
			->method('getLayout');

		$event = $this->getMock('Varien_Event', array('getControllerAction'));
		$event->expects($this->once())
			->method('getControllerAction')
			->will($this->returnValue($controller));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$addressObserver = Mage::getSingleton('eb2caddress/observer')->addSuggestionsToResponse($observer);
	}

	/**
	 * When there are errors but no suggestions, nothing should be added to
	 * the response.
	 * @test
	 */
	public function testResponseSuggestionsNoSuggestions()
	{
		$validator = $this->getModelMock('eb2caddress/validator', array('hasSuggestions'));
		// when there aren't errors in the response, this shouldn't get called
		$validator->expects($this->once())
			->method('hasSuggestions')
			->will($this->returnValue(false));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$response = $this->getMock('Mage_Core_Controller_Response_Http', array('getBody', 'setBody'));
		// response body must be JSON and in this case should include an "error" property
		$response->expects($this->once())
			->method('getBody')
			->will($this->returnValue('{"error":1}'));
		// body of the response should not be changed
		$response->expects($this->never())
			->method('setBody');

		// core/layout_update should not be touched in this scenario
		$update = $this->getModelMock('core/layout_update', array('load'));
		$update->expects($this->never())
			->method('load');

		// core/layout should not be touched in this scenario
		$layout = $this->getModelMock(
			'core/layout',
			array('getUpdate', 'generateXml', 'generateBlocks', 'getOutput')
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
			->setMethods(array('getResponse', 'getLayout'))
			->getMock();
		$controller->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));
		$controller->expects($this->never())
			->method('getLayout');

		$event = $this->getMock('Varien_Event', array('getControllerAction'));
		$event->expects($this->once())
			->method('getControllerAction')
			->will($this->returnValue($controller));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$addressObserver = Mage::getSingleton('eb2caddress/observer')->addSuggestionsToResponse($observer);
	}

	/**
	 * When the response is invalid and there are suggestions to show,
	 * the response JSON should be modified to include the markup for the suggestions.
	 * @test
	 */
	public function testResponseSuggestionsErrorsAndSuggestions()
	{
		$validator = $this->getModelMock('eb2caddress/validator', array('hasSuggestions'));
		// when there aren't errors in the response, this shouldn't get called
		$validator->expects($this->once())
			->method('hasSuggestions')
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$response = $this->getMock('Mage_Core_Controller_Response_Http', array('getBody', 'setBody'));
		// response body must be JSON and in this case not inlcude an "error" property
		$response->expects($this->once())
			->method('getBody')
			->will($this->returnValue('{"error":1}'));
		// body of the response should not be changed
		$response->expects($this->once())
			->method('setBody');

		// core/layout_update should not be touched in this scenario
		$update = $this->getModelMock('core/layout_update', array('load'));
		$update->expects($this->once())
			->method('load');

		// core/layout should not be touched in this scenario
		$layout = $this->getModelMock(
			'core/layout',
			array('getUpdate', 'generateXml', 'generateBlocks', 'getOutput')
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
			->setMethods(array('getResponse', 'getLayout'))
			->getMock();
		$controller->expects($this->exactly(2))
			->method('getResponse')
			->will($this->returnValue($response));
		$controller->expects($this->once())
			->method('getLayout')
			->will($this->returnValue($layout));

		$event = $this->getMock('Varien_Event', array('getControllerAction'));
		$event->expects($this->once())
			->method('getControllerAction')
			->will($this->returnValue($controller));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$addressObserver = Mage::getSingleton('eb2caddress/observer')->addSuggestionsToResponse($observer);
	}
}