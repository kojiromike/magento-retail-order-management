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

require_once 'EbayEnterprise/Order/controllers/GuestController.php';

class EbayEnterprise_Order_Test_Controller_GuestControllerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	const RESPONSE_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\OrderDetailResponse';

	/** @var Mock_EbayEnterprise_Order_GuestController */
	protected $_controller;
	/** @var Mock_Mage_Core_Controller_Request_Http */
	protected $_request;
	/** @var Mock_Zend_Controller_Response_Abstract */
	protected $_response;
	/** @var Mock_OrderDetailResponse */
	protected $_payload;

	/**
	 * mock the request and a controller instance to test with.
	 */
	public function setUp()
	{
		$this->_request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')
			->disableOriginalConstructor()
			->setMethods(['getParam'])
			->getMock();
		$this->_payload = $this->getMockBuilder(static::RESPONSE_CLASS)
			->disableOriginalConstructor()
			->getMock();

		$this->_controller = $this->getMockBuilder('EbayEnterprise_Order_GuestController')
			->disableOriginalConstructor()
			->setMethods(['_loadValidOrder', '_canViewOrder', 'loadLayout', 'renderLayout', 'getRequest', '_redirect'])
			->getMock();
		$this->_controller->expects($this->any())
			->method('getRequest')
			->will($this->returnValue($this->_request));
		/** @var Mock_Zend_Controller_Response_Abstract */
		$this->_response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');
	}
	/**
	 * verify the order and shipment are setup correctly.
	 * verify the request is handled properly.
	 */
	public function testPrintOrderShipmentAction()
	{
		$this->_request->expects($this->any())
			->method('getParam')
			->will($this->returnValue('theid'));
		$this->_controller->expects($this->any())
			->method('_loadValidOrder')
			->will($this->returnValue(true));
		$this->_controller->expects($this->any())
			->method('_canViewOrder')
			->will($this->returnValue(true));
		$this->_controller->expects($this->once())
			->method('loadLayout')
			->with($this->isType('string'))
			->will($this->returnValue($this->getModelMock('core/layout')));
		// if all went well, a call to renderLayout should
		// be observed.
		$this->_controller->expects($this->once())
			->method('renderLayout')
			->will($this->returnSelf());
		$this->_controller->printOrderShipmentAction();
	}
	/**
	 * provide shipment ids and return values for the mocked methods _loadValidOrder and _canViewOrder
	 * so the printOrderShipmentAction method should not attempt to render any content.
	 * @return array
	 */
	public function providePrintOrderFailData()
	{
		return [
			[false, true, 2],
			[true, false, '2'],
			[true, true, null],
		];
	}
	/**
	 * if any of the following is false redirect to either to the order history page or the guest form.
	 * 	- _loadValidOrder
	 * 	- _canViewOrder
	 * 	- no shipment id is given
	 * @param bool  $loaded   whether the order was loaded or not
	 * @param bool  $viewable whether the order should be viewable
	 * @param mixed $shipId   shipment id
	 * @dataProvider providePrintOrderFailData
	 */
	public function testPrintOrderShipmentActionFailure($loaded, $viewable, $shipId)
	{
		$this->_request->expects($this->any())
			->method('getParam')
			->will($this->returnValue($shipId));
		$this->_controller->expects($this->any())
			->method('_loadValidOrder')
			->will($this->returnValue($loaded));
		$this->_controller->expects($this->any())
			->method('_canViewOrder')
			->will($this->returnValue($viewable));
		// a call to the _redirect function signals a proper
		// failure to get required data.
		$this->_controller->expects($this->once())
			->method('_redirect')
			->with($this->isType('string'));
		$this->_controller->printOrderShipmentAction();
	}

	/**
	 * @return array
	 */
	public function providerViewAction()
	{
		return [
			[false, true],
			[false, false],
			[true, true],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Order_GuestController::_viewAction()
	 * is invoked and it will call the method EbayEnterprise_Order_GuestController::getRequest()
	 * which will return an instance of an object of type Zend_Controller_Request_Http class.
	 * Then, the method Zend_Controller_Request_Abstract::getPost() will be invoked 4 time. In the first
	 * called it will be passed the string literal 'oar_order_id' and we expect an order id to return. In the second
	 * called it will be passed the string literal 'oar_email' and we expect an email address string to be returned.
	 * In the third called it will be passed the string literal 'oar_zip' and we expect a zip code string to be returned.
	 * In the fourth called it will be passed the string literal 'oar_billing_lastname' and we expect a last name string to be returned.
	 * Then, the method ebayenterprise_order/factory::getCoreSessionModel() will be invoked and return an object of type core/session. Then, the method
	 * ebayenterprise_order/factory::getNewRomOrderDetailModel() will be invoked and return an object of type ebayenterprise_order/detail. Then, the method
	 * ebayenterprise_order/detail::process() will be called passing in the order id from post. If no exception is thrown, then, the method
	 * EbayEnterprise_Order_GuestController::_hasValidOrderResult() will be invoked and passed in as first parameter
	 * the ebayenterprise_order/detail_process_response object, as second parameter the email address, as third parameter the zip code, and
	 * as fourth parameter the last name. If the return value from calling the method EbayEnterprise_Order_GuestController::_hasValidOrderResult()
	 * is true, then, the method EbayEnterprise_Order_GuestController::_redirect() will be invoked and passed in as first parameter a string literal
	 * representing URI path, and as second parameter an array with key 'order_id'  mapped to the order id.
	 * However, if the method EbayEnterprise_Order_GuestController::_hasValidOrderResult() return false, then,
	 * the method core/session::addError() will be called and passed in the return value from calling the method
	 * ebayenterprise_order/data::__() and passed in the string literal 'Order not found'. Then, the method
	 * EbayEnterprise_Order_GuestController::_redirect() will be called and passed in the string literal 'sales/guest/form'.
	 * If an exception was thrown, then, the method core/session::addError() will be called and passed in the return value from
	 * calling the method EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception::getMessage(). Then, the method
	 * EbayEnterprise_Order_GuestController::_redirect() will be called and passed in the string literal 'sales/guest/form'.
	 * Finally, the method EbayEnterprise_Order_GuestController::_viewAction() will return null.
	 *
	 * @param bool
	 * @param bool
	 * @dataProvider providerViewAction
	 */
	public function testGuestOrderViewAction($isException, $isValidOrder)
	{
		/** @var bool */
		$isError = ($isException | !$isValidOrder);
		/** @var string */
		$message = 'Exception test message';
		/** @var EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception | null */
		$exception = $isException
			? Mage::exception('EbayEnterprise_Order_Exception_Order_Detail_Notfound', $message) : null;
		/** @var null */
		$default = null;
		/** @var string */
		$orderId = '10000093888341';
		/** @var string */
		$email = 'test@test.example.com';
		/** @var string */
		$zip = '19063';
		/** @var string */
		$lastname = 'Doe';
		/** @var string */
		$guestFormUrl = 'sales/guest/form';
		/** @var string */
		$orderDetailUrl = 'sales/order/romguestview';
		/** @var string */
		$errorMessage = 'Order not found.';
		/** @var string */
		$translatedErrorMessage = $errorMessage;

		/** @var Mock_Zend_Controller_Request_Http */
		$request = $this->getMock('Zend_Controller_Request_Http', ['getPost']);
		$request->expects($this->exactly(4))
			->method('getPost')
			->will($this->returnValueMap([
				['oar_order_id', $default, $orderId],
				['oar_email', $default, $email],
				['oar_zip', $default, $zip],
				['oar_billing_lastname', $default, $lastname],
			]));

		/** @var Mock_Mage_Core_Model_Session */
		$session = $this->getModelMockBuilder('core/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->setMethods(['addError'])
			->getMock();
		$session->expects($isError ? $this->once() : $this->never())
			// Proving that this method only be called when the method ebayenterprise_order/detail::process()
			// throws an EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception exception or the order
			// search was not successful.
			->method('addError')
			->will($this->returnValueMap([
				[$message, $session],
				[$translatedErrorMessage, $session],
			]));

		/** @var EbayEnterprise_Order_Helper_Data */
		$helper = $this->getHelperMock('ebayenterprise_order/data', ['__']);
		$helper->expects($isValidOrder ? $this->never() : $this->once())
			// Proving that this method will only be called when the method ebayenterprise_order/detail::process()
			// do not throw an exception, however, the order search was not valid, otherwise it will never be called.
			->method('__')
			->with($this->identicalTo($errorMessage))
			->will($this->returnValue($translatedErrorMessage));

		/** @var EbayEnterprise_Order_Model_Detail_Process_Response */
		$response = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response')
			->disableOriginalConstructor()
			->getMock();

		/** @var Mock_EbayEnterprise_Order_Model_Detail */
		$orderDetail = $this->getModelMockBuilder('ebayenterprise_order/detail')
			->disableOriginalConstructor()
			->getMock();
		$orderDetail->expects($this->once())
			->method('process')
			->will($isException ? $this->throwException($exception) : $this->returnValue($response));

		/** @var EbayEnterprise_Order_Helper_Factory */
		$factory = $this->getHelperMock('ebayenterprise_order/factory', ['getCoreSessionModel', 'getNewRomOrderDetailModel']);
		$factory->expects($this->once())
			->method('getCoreSessionModel')
			->will($this->returnValue($session));
		$factory->expects($this->once())
			->method('getNewRomOrderDetailModel')
			->with($this->identicalTo($orderId))
			->will($this->returnValue($orderDetail));

		/** @var Mock_EbayEnterprise_Order_GuestController */
		$controller = $this->getMockBuilder('EbayEnterprise_Order_GuestController')
			->setMethods(['getRequest', '_redirect', '_hasValidOrderResult'])
			->setConstructorArgs([$request, $this->_response])
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, [
			'_orderFactory' => $factory,
			'_orderHelper' => $helper,
		]);
		$controller->expects($this->once())
			->method('getRequest')
			->will($this->returnValue($request));
		$controller->expects($this->once())
			->method('_redirect')
			->will($this->returnValueMap([
				[$guestFormUrl, [], $controller],
				[$orderDetailUrl, ['order_id' => $orderId], $controller],
			]));
		$controller->expects($isException ? $this->never() : $this->once())
			// Proving that this method will only be called when the method ebayenterprise_order/detail::process()
			// do not throw an exception, otherwise it will never be called.
			->method('_hasValidOrderResult')
			->with(
				$this->identicalTo($response),
				$this->identicalTo($email),
				$this->identicalTo($zip),
				$this->identicalTo($lastname)
			)
			->will($this->returnValue($isValidOrder));

		$this->assertNull($controller->viewAction());
	}

	/**
	 * @return array
	 */
	public function providerHasValidOrderResult()
	{
		/** @var string */
		$lastname = 'Doe';
		/** @var string */
		$zip = '190733';
		/** @var string */
		$email = 'test@test.example.com';
		/** @var EbayEnterprise_Order_Model_Detail_Process_Response */
		$order = $this->getModelMock('ebayenterprise_order/detail_process_response', ['getBillingAddress'], false, [[
			'response' => $this->_payload,
		]]);
		$order->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue(new Varien_Object([
				'lastname' => $lastname,
				'postal_code' => $zip,
			])));
		$order->setCustomerEmail($email);
		return [
			// No match found
			[Mage::getModel('ebayenterprise_order/detail_process_response', ['response' => $this->_payload]), $email, $zip, $lastname, false],
			// Search order using zip code
			[$order, null, $zip, $lastname, true],
			// Search order using email address
			[$order, $email, null, $lastname, true],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Order_GuestController::_hasValidOrderResult()
	 * is invoked and it will be passed in as first parameter an ebayenterprise_order/detail_process_response object, as second
	 * parameter string literal representing email address, as third parameter a string literal representing
	 * email address, and as fourth parameter the string literal representing last name. Then, if the data
	 * passed to this method match the data in the the passed in ebayenterprise_order/detail_process_response object, then this
	 * method will return true otherwise it will return false.
	 *
	 * @param EbayEnterprise_Order_Model_Detail_Process_IResponse
	 * @param string
	 * @param string
	 * @param string
	 * @param bool
	 * @dataProvider providerHasValidOrderResult
	 */
	public function testHasValidOrderResult(EbayEnterprise_Order_Model_Detail_Process_IResponse $romOrderObject, $orderEmail, $orderZip, $orderLastname, $result)
	{
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Order_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Order_GuestController')
			->setConstructorArgs([$request, $response])
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$controller, '_hasValidOrderResult', [$romOrderObject, $orderEmail, $orderZip, $orderLastname]
		));
	}
}
