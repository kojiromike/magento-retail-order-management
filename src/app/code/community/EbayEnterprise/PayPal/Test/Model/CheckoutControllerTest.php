<?php

require_once 'EbayEnterprise/PayPal/controllers/CheckoutController.php';


class EbayEnterprise_PayPal_Test_Model_CheckoutControllerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var Mock_EbayEnterprise_PayPal_Model_Method_Express */
	protected $_expressMethod;
	/** @var Mock_EbayEnterprise_PayPal_Helper_Data */
	protected $_helper;
	/** @var Mock_Mage_Checkout_Helper_Data */
	protected $_checkoutHelper;
	/** @var Mock_EbayEnterprise_PayPal_Model_Express_Api */
	protected $_api;

	public function setUp()
	{
		$this->_api = $this->getModelMock('ebayenterprise_paypal/api_express', array('setExpressCheckout'));
		$this->_helper = $this->getHelperMock('ebayenterprise_paypal/data');
		$this->_helper->expects($this->any())
			->method('__')->with($this->isType('string'))
			->will($this->returnArgument(0));
		$this->_expressMethod = $this->getModelMock('ebayenterprise_paypal/method_express', array('start', 'getStartRedirectUrl', 'assignData'));
		$payment = $this->getModelMock('sales/quote_payment', array('getMethodInstance'));
		$payment->expects($this->any())
			->method('getMethodInstance')->will($this->returnValue($this->_expressMethod));
		$quote = $this->getModelMock('sales/quote', array('getPayment'));
		$quote->expects($this->any())
			->method('getPayment')->will($this->returnValue($payment));
		// disable the constructor to prevent unecessary stubbing
		$this->_session = $this->getModelMockBuilder('core/session')->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$this->_checkoutHelper = $this->getHelperMock('checkout/data', array('getCheckout', 'getQuote'));
		$this->_checkoutHelper->expects($this->any())
			->method('getCheckout')->will($this->returnValue($this->_session));
		$this->_checkoutHelper->expects($this->any())
			->method('getQuote')->will($this->returnValue($quote));
	}
	/**
	 * verify when EbayEnterprise_PayPal_Exception is thrown:
	 * - the exception message is displayed to the customer as an error.
	 * - the customer will be redirected to the previous page.
	 */
	public function testStartActionWithError()
	{
		$this->replaceByMock('model', 'ebayenterprise_paypal/api_express', $this->_api);
		$this->_api->expects($this->once())
			->method('setExpressCheckout')->will($this->throwException(Mage::exception('EbayEnterprise_PayPal')));
		$this->_session->expects($this->any())
			->method('addError')->with($this->isType('string'));
		$controller = $this->getMockBuilder('EbayEnterprise_PayPal_CheckoutController')->disableOriginalConstructor()
			->setMethods(array('_redirect'))
			->getMock();
print_r(get_class($controller));
		$controller->expects($this->once())
			->method('_redirect')->with($this->identicalTo('checkout/cart'))->will($this->returnSelf());

		EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, array(
			'_helper' => $this->_helper
		));
		$controller->startAction();
	}

	public function provideForStartAction()
	{
		return array(
			array(true, 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=thetokenstring'),
			array(false, 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=thetokenstring'),
		);
	}
	/**
	 * verify
	 * - express::start() is called.
	 * - only redirect to the paypal start url.
	 * @dataProvider provideForStartAction
	 */
	public function testStartAction($isSandboxedFlag, $expectedUrl)
	{
		$data = array('token' => 'thetokenstring');
		$config = $this->buildCoreConfigRegistry(array('isSandboxedFlag' => $isSandboxedFlag));
		$this->replaceByMock('model', 'ebayenterprise_paypal/api_express', $this->_api);
		$this->_api->expects($this->once())
			->method('setExpressCheckout')->with($this->isInstanceOf('Mage_Sales_Model_Quote'))
			->will($this->returnValue($data));
		$this->_expressMethod->expects($this->once())
			->method('assignData')->with($this->equalTo($data))
			->will($this->returnSelf());
		$response = $this->getMock('Mage_Core_Controller_Response_Http');
		// disable constructor to avoid excessive stubbing
		$controller = $this->getMockBuilder('EbayEnterprise_PayPal_ExpressController')->disableOriginalConstructor()
			->setMethods(array('_redirect', 'getResponse'))
			->getMock();
		$controller->expects($this->any())
			->method('getResponse')->will($this->returnValue($response));
		$controller->expects($this->never())
			->method('_redirect');
		$response->expects($this->once())
			->method('setRedirect')->with($this->identicalTo($expectedUrl))
			->will($this->returnSelf());
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, array(
			'_checkoutHelper' => $this->_checkoutHelper, '_config' => $config
		));
		$controller->startAction();
	}

	/**
	 * verify:
	 * - the get express request is made and sent
	 * - the data is imported by the payment
	 * @loadExpectation
	 */
	public function testReturnAction($isVirtualQuote = false)
	{
		$data = $this->expected('get_express_reply')->getData();
		$this->replaceByMock('model', 'ebayenterprise_paypal/api_express', $this->_api);
		$this->_api->expects($this->once())
			->method('getExpressCheckout')->with($this->isType('string'), $this->isType('string'), $this->isType('string'))
			->will($this->returnValue($data));
		$response = $this->getMock('Mage_Core_Controller_Response_Http');
		// disable constructor to avoid excessive stubbing
		$controller = $this->getMockBuilder('EbayEnterprise_PayPal_CheckoutController')->disableOriginalConstructor()
			->setMethods(array('_redirect', 'getResponse'))
			->getMock();
		$controller->expects($this->never())
			->method('_redirect');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, array(
			'_checkoutHelper' => $this->_checkoutHelper, '_config' => $config
		));
		$controller->startAction();
	}
}
