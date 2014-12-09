<?php

class EbayEnterprise_PayPal_Test_Model_Method_ExpressTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var EbayEnterprise_PayPal_Model_Express_Payment_Info */
	protected $_paymentInfo;

	/**
	 * setup necessary mocks
	 */
	public function setUp()
	{
		$this->_paymentInfo = $this->getModelMock(
			'ebayenterprise_paypal/express_payment_info',
			array('setAdditionalInformation')
		);
	}

	/**
	 * verify:
	 * - That Magento will see our Model as a Payment Method via payment Helper Method getMethodInstance
	 * - If it does, it should return the exact same thing as Mage::getModel() of our Model.
	 */
	public function testIsRegisteredAsPaymentMethod()
	{
		$rawModel = Mage::getModel('ebayenterprise_paypal/method_express');
		$method = Mage::helper('payment')->getMethodInstance(
			'ebayenterprise_paypal_express'
		);
		$this->assertInstanceOf(get_class($rawModel), $method);
	}

	/**
	 * verify:
	 * - given an array with a 'token' key; the token will be stored as additionalinformation in the payment info model.
	 * - the method will work with a varien object as well.
	 *
	 * @dataProvider provideTrueFalse
	 */
	public function testAssignData($isDataObject)
	{
		$data = array(
			'token' => 'thetokenstring' // setExpress
		);
		if ($isDataObject) {
			$data = new Varien_Object($data);
		}
		$this->_paymentInfo->expects($this->once())
			->method('setAdditionalInformation')->with(
				$this->identicalTo('token'),
				$this->identicalTo('thetokenstring')
			)->will($this->returnSelf());
		$express = Mage::getModel('ebayenterprise_paypal/method_express');
		$express->setInfoInstance($this->_paymentInfo);
		$express->assignData($data);
	}
}
