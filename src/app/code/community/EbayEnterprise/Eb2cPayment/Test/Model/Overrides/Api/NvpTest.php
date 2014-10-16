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

class EbayEnterprise_Eb2cPayment_Test_Model_Overrides_Api_NvpTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_nvp;
	public function buildQuoteMock()
	{
		$paymentMock = $this->getMock(
			'Mage_Sales_Model_Quote_Payment',
			array(
				'getEb2cPaypalToken', 'getEb2cPaypalPayerId', 'getEb2cPaypalTransactionID',
				'setEb2cPaypalToken', 'setEb2cPaypalPayerId', 'setEb2cPaypalTransactionID', 'save'
			)
		);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalPayerId')
			->will($this->returnValue('1234')
			);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalTransactionID')
			->will($this->returnValue('O-5SM75867VD734394E')
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalToken')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalPayerId')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalTransactionID')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getAllItems', 'getPayment')
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1)
			);
		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getName', 'getQty', 'getPrice')
		);
		$itemMock->expects($this->any())
			->method('getName')
			->will($this->returnValue('Product A')
			);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getPrice')
			->will($this->returnValue(25.00)
			);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('getPayment')
			->will($this->returnValue($paymentMock)
			);
		return $quoteMock;
	}
	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 *
	 * @return void
	 */
	public function replaceByMockCheckoutSessionModel()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('addSuccess', 'addError', 'addException', 'getQuoteId'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('addSuccess')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addError')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addException')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('getQuoteId')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
	}
	/**
	 * replacing by mock of the eb2cpayment/paypal_do_express_checkout class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalDoExpressCheckoutModel()
	{
		$paypalDoExpressCheckoutMock = $this->getModelMockBuilder('eb2cpayment/paypal_do_express_checkout')
			->disableOriginalConstructor()
			->setMethods(array('doExpressCheckout', 'parseResponse'))
			->getMock();
		$paypalDoExpressCheckoutMock->expects($this->any())
			->method('doExpressCheckout')
			->will($this->returnValue('<foo></foo>'));
		$paypalDoExpressCheckoutMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(
				new Varien_Object(
					array(
						'response_code' => 'success',
						'transaction_id' => '324533453453',
						'payer_id' => '134',
						'payment_status' => 'pending',
						'pending_reason' => 'Waiting fund verification',
						'reason_code' => 'na',
					)
				)
			));
		$this->replaceByMock('model', 'eb2cpayment/paypal_do_express_checkout', $paypalDoExpressCheckoutMock);
	}
	/**
	 * replacing by mock of the eb2cpayment/paypal_do_authorization class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalDoAuthorizationModel()
	{
		$paypalDoAuthorizationMock = $this->getModelMockBuilder('eb2cpayment/paypal_do_authorization')
			->disableOriginalConstructor()
			->setMethods(array('doAuthorization', 'parseResponse'))
			->getMock();
		$paypalDoAuthorizationMock->expects($this->any())
			->method('doAuthorization')
			->will($this->returnValue('<foo></foo>'));
		$paypalDoAuthorizationMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(
				new Varien_Object(
					array(
						'response_code' => 'success',
						'payment_status' => 'pending',
						'pending_reason' => 'Waiting fund verification',
						'reason_code' => 'na',
					)
				)
			));
		$this->replaceByMock('model', 'eb2cpayment/paypal_do_authorization', $paypalDoAuthorizationMock);
	}
	/**
	 * replacing by mock of the eb2cpayment/paypal_do_void class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalDoVoidModel()
	{
		$paypalDoVoidMock = $this->getModelMockBuilder('eb2cpayment/paypal_do_void')
			->disableOriginalConstructor()
			->setMethods(array('doVoid', 'parseResponse'))
			->getMock();
		$paypalDoVoidMock->expects($this->any())
			->method('doVoid')
			->will($this->returnValue('<foo></foo>'));
		$paypalDoVoidMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(new Varien_Object(array(
				'response_code' => 'success',
			))));
		$this->replaceByMock('model', 'eb2cpayment/paypal_do_void', $paypalDoVoidMock);
	}
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
		$this->replaceByMockCheckoutSessionModel();
	}
	/**
	 * testing callDoExpressCheckoutPayment method
	 *
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallDoExpressCheckoutPayment()
	{
		$this->replaceByMockPaypalDoExpressCheckoutModel();
		$configObject = new Mage_Paypal_Model_Config();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_nvp, '_config', $configObject);
		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_nvp, '_cart', $cartObject);
		$this->_nvp->setAddress(new Varien_Object());
		$this->assertNull($this->_nvp->callDoExpressCheckoutPayment());
	}
	/**
	 * testing callDoAuthorization method
	 *
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallDoAuthorization()
	{
		$this->replaceByMockPaypalDoAuthorizationModel();
		$configObject = new Mage_Paypal_Model_Config();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_nvp, '_config', $configObject);
		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_nvp, '_cart', $cartObject);
		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cPayment_Overrides_Model_Api_Nvp',
			$this->_nvp->callDoAuthorization()
		);
	}
	/**
	 * testing callDoVoid method
	 *
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallDoVoid()
	{
		$this->replaceByMockPaypalDoVoidModel();
		$configObject = new Mage_Paypal_Model_Config();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_nvp, '_config', $configObject);
		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_nvp, '_cart', $cartObject);
		$this->assertNull($this->_nvp->callDoVoid());
	}
	/**
	 * Test that when any non-overridden API call tries to get through the call
	 * method while Eb2cPayments is enabled, an exception is thrown.
	 *
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallExceptions()
	{
		$this->setExpectedException('Mage_Core_Exception');
		Mage::getModel('paypal/api_nvp')->call('UnsupportedPayPalMethod', array());
	}
	/**
	 * testing callGetExpressCheckoutDetails method
	 *
	 * @covers EbayEnterprise_Eb2cPayment_Overrides_Model_Api_Nvp::callGetExpressCheckoutDetails
	 * @covers EbayEnterprise_Eb2cPayment_Overrides_Model_Api_Nvp::_getNvpGetExpressResponseArray
	 * @loadExpectation
	 */
	public function testCallGetExpressCheckoutDetailsAddressExport()
	{
		$paypal = $this->getModelMock('eb2cpayment/paypal', array('loadByQuoteId'));
		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypal);
		$paypal->expects($this->any())->method('loadByQuoteId')->will($this->returnSelf());
		$paypal->expects($this->any())->method('getEb2cPaypalToken')->will($this->returnValue('the token'));
		$expressCheckout = $this->getModelMock('eb2cpayment/paypal_get_express_checkout', array(
			'processExpressCheckout', 'parseResponse'
		));
		$this->replaceByMock('model', 'eb2cpayment/paypal_get_express_checkout', $expressCheckout);
		$expressCheckout->expects($this->once())
			->method('processExpressCheckout')
			->will($this->returnValue('<foo></foo>'));
		$expressCheckout->expects($this->once())
			->method('parseResponse')
			->will($this->returnValue(
				new Varien_Object($this->expected('express_checkout_response_data')->getData())
			));
		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			// the constructor calls getConfigModel which needs to be mocked.
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$this->replaceByMock('helper', 'eb2cpayment', $helper);
		$helper->expects($this->any())->method('getConfigModel')->will($this->returnValue(
			$this->buildCoreConfigRegistry(array(
				'isPaymentEnabled' => 1
			))
		));
		$nvp = $this->getModelMock('paypal/api_nvp', array(
			'_importFromResponse',
			'_prepareExpressCheckoutCallRequest',
			'_getExpressCheckoutDetailsRequest',
			'_exportToRequest',
			'_exportAddressses',
			'getPalDetails',
			'call',
		));
		$paymentInfoResponse = array('paymentinforesponse');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($nvp, array(
			'_cart' => Mage::getModel('paypal/cart', array($this->buildQuoteMock())),
			'_config' => Mage::getModel('paypal/config'),
			'_paymentInformationResponse' => $paymentInfoResponse,
		));
		$nvp->expects($this->never())->method('call');
		$nvp->expects($this->once())
			->method('_importFromResponse')
			->with($this->identicalTo($paymentInfoResponse), $this->isType('array'));
		$expected = $this->expected('exported_address_data')->getData();
		$testCase = $this;
		$nvp->expects($this->once())
			->method('_exportAddressses')
			->with($this->callback(function($data) use ($expected, $testCase) {
				$testCase->assertEquals($expected, $data);
				return true;
			}));
		$nvp->callGetExpressCheckoutDetails();
	}
}
