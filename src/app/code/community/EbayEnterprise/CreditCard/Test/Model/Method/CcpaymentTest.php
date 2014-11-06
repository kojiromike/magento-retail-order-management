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

use \eBayEnterprise\RetailOrderManagement\Api;
use \eBayEnterprise\RetailOrderManagement\Payload;

class EbayEnterprise_CreditCard_Test_Model_Method_CcpaymentTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var Mage_Checkout_Model_Session $checkoutSession (stub) */
	protected $_checkoutSession;

	public function setUp()
	{
		$this->_checkoutSession = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->getMock();
	}
	/**
	 * Needs to be called in ever test method to prevent "headers already sent
	 * errors." Can't be in setUp as replaceByMock in setUp doesn't get cleaned
	 * up properly after the test is complete.
	 * @return self
	 */
	protected function _replaceCheckoutSession()
	{
		$this->replaceByMock('singleton', 'checkout/session', $this->_checkoutSession);
		return $this;
	}
	/**
	 * Get an address object with the given data
	 * @param  array  $addrData
	 * @return Mage_Sales_Model_Order_Address
	 */
	protected function _getOrderAddress(array $addrData=array())
	{
		return Mage::getModel('sales/order_address', $addrData);
	}
	/**
	 * Test the override of getConfigData for getting cctypes. Should return only
	 * the available credit card types but in same format as expected if it were
	 * coming directly from the config.
	 */
	public function testGetConfigDataOverride()
	{
		$availableCardTypes = array('AE' => 'American Express', 'VI' => 'Visa', 'MC' => 'Master Card');
		$helper = $this->getHelperMock('ebayenterprise_creditcard', array('getAvailableCardTypes'));
		$helper->expects($this->any())
			->method('getAvailableCardTypes')
			->will($this->returnValue($availableCardTypes));

		$method = Mage::getModel('ebayenterprise_creditcard/method_ccpayment', array('helper' => $helper));
		$this->assertSame(
			'AE,VI,MC',
			$method->getConfigData('cctypes', null)
		);
	}
	/**
	 * Test when an invalid payload is provided.
	 */
	public function testAuthorizeApiInvalidPayload()
	{
		$this->_replaceCheckoutSession();
		// invalid payload should throw this exception to redirect back to payment
		// step to recollect/correct payment info
		$this->setExpectedException('EbayEnterprise_CreditCard_Exception');

		$payment = Mage::getModel('sales/order_payment');
		$amount = 25.50;

		$request = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthRequest');
		$response = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthReply');
		$api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
		$api->expects($this->any())
			->method('send')
			->will($this->throwException(new Payload\Exception\InvalidPayload));
		$api->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($request));
		$api->expects($this->any())
			->method('getResponseBody')
			->will($this->returnValue($response));

		$coreHelper = $this->getHelperMock('eb2ccore', array('getSdkApi'));
		$coreHelper->expects($this->any())
			->method('getSdkApi')
			->will($this->returnValue($api));
		$ccHelper = $this->getHelperMock('ebayenterprise_creditcard', array('getTenderTypeForCcType'));
		$ccHelper->expects($this->any())
			->method('getTenderTypeForCcType')
			->will($this->returnValue('TT'));

		$payment = $this->getModelMockBuilder('ebayenterprise_creditcard/method_ccpayment')
			->setMethods(array('_prepareApiRequest'))
			->setConstructorArgs(array(array('core_helper' => $coreHelper, 'helper' => $ccHelper, 'checkout_session' => $this->_checkoutSession)))
			->getMock();
		$payment->expects($this->any())
			->method('_prepareApiRequest')
			->will($this->returnSelf());

		$payment->authorize($payment, $amount);
	}
	/**
	 * Network errors for payment
	 */
	public function testAuthorizeApiNetworkError()
	{
		$this->_replaceCheckoutSession();
		$this->setExpectedException('EbayEnterprise_CreditCard_Exception');

		$payment = Mage::getModel('sales/order_payment');
		$amount = 25.50;

		$request = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthRequest');
		$response = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthReply');
		$api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
		$api->expects($this->any())
			->method('send')
			->will($this->throwException(new Api\Exception\NetworkError));
		$api->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($request));
		$api->expects($this->any())
			->method('getResponseBody')
			->will($this->returnValue($response));

		$coreHelper = $this->getHelperMock('eb2ccore', array('getSdkApi'));
		$coreHelper->expects($this->any())
			->method('getSdkApi')
			->will($this->returnValue($api));
		$ccHelper = $this->getHelperMock('ebayenterprise_creditcard', array('getTenderTypeForCcType'));
		$ccHelper->expects($this->any())
			->method('getTenderTypeForCcType')
			->will($this->returnValue('TT'));

		$payment = $this->getModelMockBuilder('ebayenterprise_creditcard/method_ccpayment')
			->setMethods(array('_prepareApiRequest'))
			->setConstructorArgs(array(array('core_helper' => $coreHelper, 'helper' => $ccHelper, 'checkout_session' => $this->_checkoutSession)))
			->getMock();
		$payment->expects($this->any())
			->method('_prepareApiRequest')
			->will($this->returnSelf());

		$payment->authorize($payment, $amount);
	}
	/**
	 * Build a mock credit card auth reply payload scripted to return the given
	 * values for various success checks.
	 * @param  bool $isSuccess
	 * @param  bool $isAcceptable
	 * @param  bool $isAvsSuccess
	 * @param  bool $isCvvSuccess
	 * @return Payload\Payment\ICreditCardAuthReply
	 */
	protected function _buildPayloadToValidate($isSuccess=true, $isAcceptable=true, $isAvsSuccess=true, $isCvvSuccess=true)
	{
		$payload = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthReply');
		$payload->expects($this->any())
			->method('getIsAuthSuccessful')
			->will($this->returnValue($isSuccess));
		$payload->expects($this->any())
			->method('getIsAuthAcceptable')
			->will($this->returnValue($isAcceptable));
		$payload->expects($this->any())
			->method('getIsAVSSuccessful')
			->will($this->returnValue($isAvsSuccess));
		$payload->expects($this->any())
			->method('getIsCVV2Successful')
			->will($this->returnValue($isCvvSuccess));
		return $payload;
	}
	/**
	 * Provide a payload to validate and the name of the exception that should
	 * be thrown if the payload is invalid.
	 * @return array
	 */
	public function providePayloadAndException()
	{
		return array(
			// all pass
			array($this->_buildPayloadToValidate(true, true, true, true), null),
			// no success but acceptable, no AVS or CVV failes
			array($this->_buildPayloadToValidate(false, true, true, true), null),
			// AVS failure
			array($this->_buildPayloadToValidate(false, false, false, true), 'EbayEnterprise_CreditCard_Exception'),
			// CVV failure
			array($this->_buildPayloadToValidate(false, false, true, false), 'EbayEnterprise_CreditCard_Exception'),
			// no success, no failures but sill unacceptable
			array($this->_buildPayloadToValidate(false, false, true, true), 'EbayEnterprise_CreditCard_Exception'),
		);
	}
	/**
	 * Test validating response payloads to pass or thrown the expected exception
	 * @param  Payload\Payment\ICreditCardAuthReply $payload
	 * @param  string|null $exception Name of exception to throw, null if no expected exception
	 * @dataProvider providePayloadAndException
	 */
	public function testValidateResponse(Payload\Payment\ICreditCardAuthReply $payload, $exception)
	{
		$this->_replaceCheckoutSession();
		if ($exception) {
			$this->setExpectedException($exception);
		}
		$paymentMethod = Mage::getModel('ebayenterprise_creditcard/method_ccpayment');
		$this->assertSame(
			$paymentMethod,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($paymentMethod, '_validateResponse', array($payload))
		);
	}
	/**
	 * Validate card data when CSE is enabled. When disabled, uses parent validation
	 * which is provided by Magento.
	 * @param  string $infoModel      Model alias for the payment info model
	 * @param  string $billingCountry
	 * @param  string $expYear
	 * @param  string $expMonth
	 * @param  string $cardType
	 * @param  bool   $isValid
	 * @dataProvider dataProvider
	 */
	public function testValidateWithEncryptedCardData($infoModel, $billingCountry, $expYear, $expMonth, $cardType, $isValid)
	{
		$this->_replaceCheckoutSession();

		$quoteBillingAddress = Mage::getModel('sales/quote_address', array('country_id' => $billingCountry));
		$orderBillingAddress = Mage::getModel('sales/order_address', array('country_id' => $billingCountry));
		$quote = Mage::getModel('sales/quote');
		$quote->setBillingAddress($quoteBillingAddress);
		$order = Mage::getModel('sales/order');
		$order->setBillingAddress($orderBillingAddress);
		$info = Mage::getModel($infoModel)
			// use setters instead of contstructor data as some of these setters have
			// actual implementation in some of the payment info models
			->setQuote($quote)
			->setOrder($order)
			->setCcType($cardType)
			->setCcExpYear($expYear)
			->setCcExpMonth($expMonth);

		// stub the helper to return a known set of available card types and a
		// config model with CSE enabled
		$helper = $this->getHelperMock('ebayenterprise_creditcard', array('getAvailableCardTypes', 'getConfigModel'));
		$helper->expects($this->any())
			->method('getAvailableCardTypes')
			->will($this->returnValue(array('VI' => 'Visa')));
		$helper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array('useClientSideEncryptionFlag' => true))));

		$ccMethod = $this->getModelMock(
			'ebayenterprise_creditcard/method_ccpayment',
			array('canUseForCountry'),
			false,
			array(array('helper' => $helper))
		);
		$ccMethod->setInfoInstance($info);
		// mock canUseForCountry call to prevent config dependency
		$ccMethod->expects($this->any())
			->method('canUseForCountry')
			->will($this->returnValueMap(array(
				array('US', true),
			)));

		if (!$isValid) {
			$this->setExpectedException('EbayEnterprise_CreditCard_Exception');
		}
		$this->assertSame($ccMethod, $ccMethod->validate());
	}
	/**
	 * Test assigning data to the payment info object. Ensure correct cc last 4
	 * is assigned as this is not typical data being submitted.
	 */
	public function testAssignData()
	{
		$config = $this->buildCoreConfigRegistry(array('useClientSideEncryptionFlag' => true));
		$helper = $this->getHelperMock('ebayenterprise_creditcard', array('getConfigModel'));
		$helper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($config));
		$lastFour = '1111';
		$info = Mage::getModel('payment/info');
		$data = array('cc_last4' => $lastFour);
		$method = Mage::getModel('ebayenterprise_creditcard/method_ccpayment', array('helper' => $helper));
		$method->setInfoInstance($info);
		$method->assignData($data);
		$this->assertSame($lastFour, $info->getCcLast4());
	}
}
