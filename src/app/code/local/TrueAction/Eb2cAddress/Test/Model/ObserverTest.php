<?php

class TrueAction_Eb2cAddress_Test_Model_ObserverTest
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

	public function testValidateAddressSuccess()
	{
		$expectedError = 'Error from validation';

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
}