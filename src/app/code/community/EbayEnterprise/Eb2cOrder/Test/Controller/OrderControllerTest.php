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

require_once 'EbayEnterprise/Eb2cOrder/controllers/OrderController.php';

class EbayEnterprise_Eb2cOrder_Test_Controller_OrderControllerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	// @var Mock_EbayEnterprise_Eb2cOrder_GuestController
	protected $_controller;
	// @var Mock_Mage_Customer_Model_Session
	protected $_session;
	// @var Mock_Mage_Core_Controller_Request_Http
	protected $_request;

	/**
	 * mock the request, customer session, and controller instance to test with.
	 */
	public function setUp()
	{
		$this->_session = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('isLoggedIn'))
			->getMock();

		$this->_request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')
			->disableOriginalConstructor()
			->setMethods(array('getParam'))
			->getMock();

		$this->_controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->disableOriginalConstructor()
			->setMethods(array('_loadValidOrder', '_canViewOrder', 'loadLayout', 'renderLayout', 'getRequest', '_redirect'))
			->getMock();
		$this->_controller->expects($this->any())
			->method('getRequest')
			->will($this->returnValue($this->_request));
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
	 * provide datasets so the printOrderShipmentAction method does not attempt
	 * to render any content.
	 * @return array()
	 */
	public function providePrintOrderFailData()
	{
		return array(
			array(false, true, 2, true),
			array(true, false, '2', false),
			array(true, true, null, true),
		);
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
	public function testPrintOrderShipmentActionFailure($loaded, $viewable, $shipId, $isLoggedIn)
	{
		$this->replaceByMock('singleton', 'customer/session', $this->_session);
		$this->_request->expects($this->any())
			->method('getParam')
			->will($this->returnValue($shipId));
		$this->_controller->expects($this->any())
			->method('_loadValidOrder')
			->will($this->returnValue($loaded));
		$this->_controller->expects($this->any())
			->method('_canViewOrder')
			->will($this->returnValue($viewable));
		$this->_controller->expects($this->once())
			->method('_redirect')
			->with($this->isType('string'));
		$this->_session->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue($isLoggedIn));
		$this->_controller->printOrderShipmentAction();
	}

	/**
	 * @return array
	 */
	public function providerViewAction()
	{
		return [
			[false, null],
			[true, new EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound()],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_viewAction()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::getRequest()
	 * which will return an instance of an object that extends Zend_Controller_Request_Abstract class.
	 * Then, the method Zend_Controller_Request_Abstract::getParam() will be invoked, passed in
	 * the string literal 'order_id' as its parameter and then it will return the order id.
	 * Then, the method eb2corder/factory::getNewRomOrderDetailModel() will be invoked and
	 * return an object of type eb2corder/detail. Then, the method eb2corder/detail::requestOrderDetail() will be
	 * called passing in the return value from calling the method Zend_Controller_Request_Abstract::getParam().
	 * If no exception is thrown the method EbayEnterprise_Eb2cOrder_OrderController::_handleOrderDetailException().
	 * Finally, the method EbayEnterprise_Eb2cOrder_OrderController::_viewAction() will return null.
	 *
	 * @param bool
	 * @param EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound | null
	 * @dataProvider providerViewAction
	 */
	public function testViewAction($isException, $exception)
	{
		/** @var string */
		$param = 'order_id';
		/** @var string */
		$orderId = '10000093888341';

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract', [], '', true, true, true, ['getParam']);
		$request->expects($this->once())
			->method('getParam')
			->with($this->identicalTo($param))
			->will($this->returnValue($orderId));

		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var EbayEnterprise_Eb2cOrder_Model_Detail_Order */
		$detail = Mage::getModel('eb2corder/detail_order');

		/** @var Mock_EbayEnterprise_Eb2cOrder_Model_Detail */
		$orderDetail = $this->getModelMock('eb2corder/detail', ['requestOrderDetail']);
		$orderDetail->expects($this->once())
			->method('requestOrderDetail')
			->with($this->identicalTo($orderId))
			->will($isException ? $this->throwException($exception) : $this->returnValue($detail));

		/** @var EbayEnterprise_Eb2cOrder_Helper_Factory */
		$factory = $this->getHelperMock('eb2corder/factory', ['getNewRomOrderDetailModel']);
		$factory->expects($this->once())
			->method('getNewRomOrderDetailModel')
			->will($this->returnValue($orderDetail));
		$this->replaceByMock('helper', 'eb2corder/factory', $factory);

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['getRequest', '_handleOrderDetailException'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('getRequest')
			->will($this->returnValue($request));
		$controller->expects($isException ? $this->once() : $this->never())
			// Proving that this method will never be called if the method eb2corder/detail::requestOrderDetail()
			// don't throw an exception of type bayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound.
			// However, when exception of type bayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound is thrown
			// the this method expected to be invoked once.
			->method('_handleOrderDetailException')
			->with($this->identicalTo($exception))
			->will($this->returnSelf());

		$this->assertNull(EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_viewAction', []));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_handleOrderDetailException()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::_getCustomerSession(),
	 * which will return an instance of type customer/session. Then, the method customer/session::addError()
	 * will be called and passed as its parameter the value from calling the method
	 * EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound::getMessage() from the passed in exception object.
	 * Then, the method EbayEnterprise_Eb2cOrder_OrderController::_redirect() is invoked, and passed in the return
	 * value from calling the method EbayEnterprise_Eb2cOrder_OrderController::_getOrderDetailReturnPath().
	 * Finally, the controller method EbayEnterprise_Eb2cOrder_OrderController::_handleOrderDetailException() will
	 * return itself.
	 */
	public function testHandleOrderDetailException()
	{
		/** @var string */
		$path = EbayEnterprise_Eb2cOrder_OrderController::LOGGED_IN_ORDER_HISTORY_PATH;
		/** @var string */
		$message = 'Exception test message';
		/** @var EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound */
		$exception = new EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound($message);
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');
		/** @var Mock_Mage_Customer_Model_Session */
		$session = $this->getModelMockBuilder('customer/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->setMethods(['addError'])
			->getMock();
		$session->expects($this->once())
			->method('addError')
			->with($this->identicalTo($message))
			->will($this->returnSelf());

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_getCustomerSession', '_getOrderDetailReturnPath', '_redirect'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_getCustomerSession')
			->will($this->returnValue($session));
		$controller->expects($this->once())
			->method('_getOrderDetailReturnPath')
			->with($this->identicalTo($session))
			->will($this->returnValue($path));
		$controller->expects($this->once())
			->method('_redirect')
			->with($this->identicalTo($path))
			->will($this->returnSelf());

		$this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_handleOrderDetailException', [$exception]));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_getCustomerSession()
	 * is invoked and it will instantiate an object of type customer/session and return this object.
	 */
	public function testGetCustomerSession()
	{
		/** @var Mock_Mage_Customer_Model_Session */
		$session = $this->getModelMockBuilder('customer/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->getMock();
		$this->replaceByMock('singleton', 'customer/session', $session);

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setConstructorArgs([$request, $response])
			->getMock();

		$this->assertSame($session, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getCustomerSession', []));
	}

	/**
	 * @return array
	 */
	public function providerGetOrderDetailReturnPath()
	{
		return [
			[true, EbayEnterprise_Eb2cOrder_OrderController::LOGGED_IN_ORDER_HISTORY_PATH],
			[false, EbayEnterprise_Eb2cOrder_OrderController::GUEST_ORDER_FORM_PATH],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_getOrderDetailReturnPath()
	 * is invoked and it will call the method customer/session::isLoggedIn() and if it returns the boolean value
	 * true then the controller method EbayEnterprise_Eb2cOrder_OrderController::_getOrderDetailReturnPath() will
	 * return the class constant EbayEnterprise_Eb2cOrder_OrderController::LOGGED_IN_ORDER_HISTORY_PATH.
	 * Otherwise, it will return class constant value EbayEnterprise_Eb2cOrder_OrderController::GUEST_ORDER_FORM_PATH.
	 *
	 * @param bool
	 * @param string
	 * @dataProvider providerGetOrderDetailReturnPath
	 */
	public function testGetOrderDetailReturnPath($isLoggedIn, $path)
	{
		/** @var Mock_Mage_Customer_Model_Session */
		$session = $this->getModelMockBuilder('customer/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->setMethods(['isLoggedIn'])
			->getMock();
		$session->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue($isLoggedIn));

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setConstructorArgs([$request, $response])
			->getMock();

		$this->assertSame($path, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getOrderDetailReturnPath', [$session]));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::romViewAction()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::_viewAction().
	 * Then, the method EbayEnterprise_Eb2cOrder_OrderController::loadLayout() will be invoked. Then,
	 * the method EbayEnterprise_Eb2cOrder_OrderController::_initLayoutMessages() will be called and passed
	 * in as parameter the string literal 'catalog/session'. Then, the method EbayEnterprise_Eb2cOrder_OrderController::getLayout()
	 * will be called and return an instance of the class Mage_Core_Model_Layout. Then,
	 * the method Mage_Core_Model_Layout::getBlock() will be called and passed in as parameter the string
	 * literal 'customer_account_navigation' and it will return an instance object that extend the abstract class
	 * Mage_Core_Block_Abstract. Then, the method Mage_Core_Block_Abstract::setActive() will be called. Then, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::renderLayout() will be called. Finally, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::romViewAction() will return null.
	 */
	public function testRomViewAction()
	{
		/** @var string */
		$messages = 'catalog/session';
		/** @var string */
		$blockName = 'customer_account_navigation';
		/** @var string */
		$path = 'sales/order/history';

		/** @var Mage_Core_Block_Abstract */
		$block = $this->getMockForAbstractClass('Mage_Core_Block_Abstract', [], '', true, true, true, ['setActive']);
		$block->expects($this->once())
			->method('setActive')
			->with($this->identicalTo($path))
			->will($this->returnSelf());

		/** @var Mage_Core_Model_Layout */
		$layout = $this->getModelMock('core/layout', ['getBlock']);
		$layout->expects($this->once())
			->method('getBlock')
			->with($this->identicalTo($blockName))
			->will($this->returnValue($block));

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_viewAction', 'loadLayout', '_initLayoutMessages', 'getLayout', 'renderLayout'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_viewAction')
			->will($this->returnValue(null));
		$controller->expects($this->once())
			->method('loadLayout')
			->will($this->returnSelf());
		$controller->expects($this->once())
			->method('_initLayoutMessages')
			->with($this->identicalTo($messages))
			->will($this->returnSelf());
		$controller->expects($this->once())
			->method('getLayout')
			->will($this->returnValue($layout));
		$controller->expects($this->once())
			->method('renderLayout')
			->will($this->returnSelf());

		$this->assertNull($controller->romViewAction());
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::romGuestViewAction()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::_viewAction().
	 * Then, the method EbayEnterprise_Eb2cOrder_OrderController::loadLayout() will be invoked. Then,
	 * the method EbayEnterprise_Eb2cOrder_OrderController::_initLayoutMessages() will be called and passed
	 * in as parameter the string literal 'catalog/session'. Then, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::renderLayout() will be called. Finally, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::romGuestViewAction() will return null.
	 */
	public function testRomGuestViewAction()
	{
		/** @var string */
		$messages = 'catalog/session';

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_viewAction', 'loadLayout', '_initLayoutMessages', 'renderLayout'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_viewAction')
			->will($this->returnValue(null));
		$controller->expects($this->once())
			->method('loadLayout')
			->will($this->returnSelf());
		$controller->expects($this->once())
			->method('_initLayoutMessages')
			->with($this->identicalTo($messages))
			->will($this->returnSelf());
		$controller->expects($this->once())
			->method('renderLayout')
			->will($this->returnSelf());

		$this->assertNull($controller->romGuestViewAction());
	}

	/**
	 * @return array
	 */
	public function providerRomCancelAction()
	{
		return [
			[true],
			[false],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::romCancelAction()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::_canShowOrderCancelForm()
	 * and if it returns the boolean value true then these controller methods
	 * EbayEnterprise_Eb2cOrder_OrderController::_setRefererUrlInSession() and
	 * EbayEnterprise_Eb2cOrder_OrderController::_showOrderCancelPage() will be called. However,
	 * the method EbayEnterprise_Eb2cOrder_OrderController::_processOrderCancelAction() will never be called.
	 * Otherwise, if the method EbayEnterprise_Eb2cOrder_OrderController::_canShowOrderCancelForm() return the boolean
	 * value false, then these controller methods EbayEnterprise_Eb2cOrder_OrderController::_setRefererUrlInSession() and
	 * EbayEnterprise_Eb2cOrder_OrderController::_showOrderCancelPage() will never be called. However, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_processOrderCancelAction() will be called once.
	 * Finally, the method EbayEnterprise_Eb2cOrder_OrderController::romCancelAction() will return null.
	 *
	 * @param bool
	 * @dataProvider providerRomCancelAction
	 */
	public function testRomCancelAction($canShow)
	{
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_canShowOrderCancelForm', '_setRefererUrlInSession', '_showOrderCancelPage', '_processOrderCancelAction'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_canShowOrderCancelForm')
			->will($this->returnValue($canShow));
		$controller->expects($canShow ? $this->once() : $this->never())
			// when the method EbayEnterprise_Eb2cOrder_OrderController::_canShowOrderCancelForm() return true
			// this method will be invoked once, otherwise it will never be called.
			->method('_setRefererUrlInSession')
			->will($this->returnSelf());
		$controller->expects($canShow ? $this->once() : $this->never())
			// when the method EbayEnterprise_Eb2cOrder_OrderController::_canShowOrderCancelForm() return true
			// this method will be invoked once, otherwise it will never be called.
			->method('_showOrderCancelPage')
			->will($this->returnSelf());
		$controller->expects($canShow ? $this->never() : $this->once())
			// when the method EbayEnterprise_Eb2cOrder_OrderController::_canShowOrderCancelForm() return true
			// this method will never be invoked, otherwise it will be called once.
			->method('_processOrderCancelAction')
			->will($this->returnSelf());

		$this->assertNull($controller->romCancelAction());
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::romGuestCancelAction()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::romCancelAction().
	 * Finally, the method EbayEnterprise_Eb2cOrder_OrderController::romGuestCancelAction() will return null.
	 */
	public function testromGuestCancelAction()
	{
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['romCancelAction'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('romCancelAction')
			->will($this->returnValue(null));

		$this->assertNull($controller->romGuestCancelAction());
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_setRefererUrlInSession()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::_getRomCancelSession(),
	 * which will return an instance of type core/session. Then, the method core/session::setCancelActionRefererUrl()
	 * will be called and passed as its parameter the return value from calling the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_getRefererUrl(). finally, the controller method
	 * EbayEnterprise_Eb2cOrder_OrderController::_setRefererUrlInSession() will return itself.
	 */
	public function testSetRefererUrlInSession()
	{
		/** @var string */
		$path = EbayEnterprise_Eb2cOrder_OrderController::LOGGED_IN_ORDER_HISTORY_PATH;
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_Mage_Core_Model_Session */
		$session = $this->getModelMockBuilder('core/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->setMethods(['setCancelActionRefererUrl'])
			->getMock();
		$session->expects($this->once())
			->method('setCancelActionRefererUrl')
			->will($this->returnSelf());

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_getRomCancelSession', '_getRefererUrl'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_getRomCancelSession')
			->will($this->returnValue($session));
		$controller->expects($this->once())
			->method('_getRefererUrl')
			->will($this->returnValue($path));

		$this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_setRefererUrlInSession', []));
	}

	/**
	 * @return array
	 */
	public function providerProcessOrderCancelAction()
	{
		return [
			[false, false, null],
			[true, true, Mage::exception('Mage_Core')],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_processOrderCancelAction()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::_getRomOrderToBeCanceled(),
	 * which will return an instance of type sales/order. Then, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_getRomOrderCancelModel() will be called and passed as its parameter the
	 * sales/order object, which in term will return an instance of type ebayenterprise_order/cancel.
	 * Then, the method EbayEnterprise_Eb2cOrder_OrderController::_getRomCancelSession() will be invoked
	 * and return an instance of type core/session. Then, the method core/session::getCancelActionRefererUrl()
	 * will be called and if it return a non-empty string value then, the method EbayEnterprise_Eb2cOrder_OrderController::_getRefererUrl().
	 * However, if the return value from core/session::getCancelActionRefererUrl() is null, then the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_getRefererUrl() will be invoked once. Then, the method
	 * ebayenterprise_order/cancel::process() will be invoked and if it doesn't throw an exception the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_handleRomCancelResponse() invoked once passed in as first parameter
	 * a sales/order object, as second parameter core/session object and as turn parameter a string literal representing
	 * the redirect URL. However, if the method ebayenterprise_order/cancel::process() throw an exception, then the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_handleRomCancelResponse() will never be invoked, but instead the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_handleRomCancelException() will be invoked and passed in as first parameter
	 * and object of type Exception, as second parameter an object of type core/session, and as third parameter string literal
	 * representing the redirect URL. Finally, the method EbayEnterprise_Eb2cOrder_OrderController::_processOrderCancelAction()
	 * will return itself.
	 *
	 * @param bool
	 * @param bool
	 * @param Exception | null
	 * @dataProvider providerProcessOrderCancelAction
	 */
	public function testProcessOrderCancelAction($isException, $isRedirectSession, $exception)
	{
		/** @var string */
		$redirect = 'http://test.example.com/sales/order/romview/order_id/10008832231/';
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');

		/** @var Mock_EbayEnterprise_Order_Model_Cancel */
		$cancel = $this->getModelMock('ebayenterprise_order/cancel', ['process'], false, [[
			// This key is required
			'order' => $order,
		]]);
		$cancel->expects($this->once())
			->method('process')
			->will($isException ? $this->throwException($exception) : $this->returnSelf());

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_Mage_Core_Model_Session */
		$session = $this->getModelMockBuilder('core/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->setMethods(['getCancelActionRefererUrl'])
			->getMock();
		$session->expects($this->once())
			->method('getCancelActionRefererUrl')
			->will($this->returnValue($isRedirectSession ? $redirect : null));

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_getRomOrderToBeCanceled', '_getRomOrderCancelModel', '_getRomCancelSession', '_getRefererUrl', '_handleRomCancelException', '_handleRomCancelResponse'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_getRomOrderToBeCanceled')
			->will($this->returnValue($order));
		$controller->expects($this->once())
			->method('_getRomOrderCancelModel')
			->with($this->identicalTo($order))
			->will($this->returnValue($cancel));
		$controller->expects($this->once())
			->method('_getRomCancelSession')
			->will($this->returnValue($session));
		$controller->expects($isRedirectSession? $this->never() : $this->once())
			// Proving that this method will never be called when the varien magic method
			// core/session::getCancelActionRefererUrl() returns a non-empty string.
			// When the method core/session::getCancelActionRefererUrl() return null
			// this method will be invoked once.
			->method('_getRefererUrl')
			->will($this->returnValue($redirect));
		$controller->expects($isException ? $this->once() : $this->never())
			// Proving that this method will never be invoked unless the method
			// ebayenterprise_order/cancel::process() thrown an exception, then it will be invoked once.
			->method('_handleRomCancelException')
			->with($this->identicalTo($exception), $this->identicalTo($session), $this->identicalTo($redirect))
			->will($this->returnSelf());
		$controller->expects($isException ? $this->never() : $this->once())
			// Proving that this method will be invoked once unless the method
			// ebayenterprise_order/cancel::process() thrown an exception, then it will never be invoked.
			->method('_handleRomCancelResponse')
			->with($this->identicalTo($order), $this->identicalTo($session), $this->identicalTo($redirect))
			->will($this->returnSelf());

		$this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_processOrderCancelAction', []));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_showOrderCancelPage()
	 * is invoked and it will call the method EbayEnterprise_Eb2cOrder_OrderController::_viewAction().
	 * Then, the method EbayEnterprise_Eb2cOrder_OrderController::loadLayout() will be invoked. Then,
	 * the method EbayEnterprise_Eb2cOrder_OrderController::_initLayoutMessages() will be called and passed
	 * in as parameter the string literal 'catalog/session'. Then, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::renderLayout() will be called. Finally, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_showOrderCancelPage() will return itself.
	 */
	public function testShowOrderCancelPage()
	{
		/** @var string */
		$messages = 'catalog/session';

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_viewAction', 'loadLayout', '_initLayoutMessages', 'renderLayout'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_viewAction')
			->will($this->returnValue(null));
		$controller->expects($this->once())
			->method('loadLayout')
			->will($this->returnSelf());
		$controller->expects($this->once())
			->method('_initLayoutMessages')
			->with($this->identicalTo($messages))
			->will($this->returnSelf());
		$controller->expects($this->once())
			->method('renderLayout')
			->will($this->returnSelf());

		$this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_showOrderCancelPage', []));
	}

	/**
	 * @return array
	 */
	public function providerCanShowOrderCancelForm()
	{
		return [
			[true, true, false],
			[false, true, true],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_canShowOrderCancelForm()
	 * is invoked and when the method ebayenterprise_order/data::hasOrderCancelReason() is called and return true
	 * and the method EbayEnterprise_Eb2cOrder_OrderController::_isOrderCancelPostAction() and return false will
	 * the method EbayEnterprise_Eb2cOrder_OrderController::_canShowOrderCancelForm() return true. Otherwise, it
	 * will return false.
	 *
	 * @param bool
	 * @param bool
	 * @param string
	 * @dataProvider providerCanShowOrderCancelForm
	 */
	public function testCanShowOrderCancelForm($isPost, $hasReason, $result)
	{
		/** @var EbayEnterprise_Order_Helper_Data */
		$orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['hasOrderCancelReason']);
		$orderHelper->expects($this->once())
			->method('hasOrderCancelReason')
			->will($this->returnValue($hasReason));

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_isOrderCancelPostAction'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_isOrderCancelPostAction')
			->will($this->returnValue($isPost));

		// Replacing the protected class property EbayEnterprise_Eb2cOrder_OrderController::$_orderHelper with a mock.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($controller, '_orderHelper', $orderHelper);

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_canShowOrderCancelForm', []));
	}

	/**
	 * @return array
	 */
	public function providerIsOrderCancelPostAction()
	{
		return [
			[true, true, true],
			[true, false, false],
			[false, true, false],
			[false, false, false],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_isOrderCancelPostAction()
	 * is invoked and the method EbayEnterprise_Eb2cOrder_OrderController::getRequest() will be called and return
	 * an instance object that extend the class Zend_Controller_Request_Abstract. Then, the controller method
	 * EbayEnterprise_Eb2cOrder_OrderController::_isOrderCancelPostAction() will only return true when both
	 * Zend_Controller_Request_Abstract::isPost() and Zend_Controller_Request_Abstract::getPost() passing in a string
	 * literal 'cancel_action' return boolean true. Otherwise, the EbayEnterprise_Eb2cOrder_OrderController::_isOrderCancelPostAction()
	 * method will always return false. Also, when the method Zend_Controller_Request_Abstract::isPost() return false
	 * the method Zend_Controller_Request_Abstract::getPost() will never be called.
	 *
	 * @param bool
	 * @param bool
	 * @param bool
	 * @dataProvider providerIsOrderCancelPostAction
	 */
	public function testIsOrderCancelPostAction($isPost, $cancelAction, $result)
	{
		/** @var string */
		$param = 'cancel_action';

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract', [], '', true, true, true, ['isPost', 'getPost']);
		$request->expects($this->once())
			->method('isPost')
			->will($this->returnValue($isPost));
		$request->expects($isPost? $this->once() : $this->never())
			// Proving that once the method Zend_Controller_Request_Abstract::isPost() return
			// false the method Zend_Controller_Request_Abstract::getPost() will never be invoked
			// since we are using logical AND operation for the condition.
			->method('getPost')
			->with($this->identicalTo($param))
			->will($this->returnValue($cancelAction));

		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['getRequest'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('getRequest')
			->will($this->returnValue($request));

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_isOrderCancelPostAction', []));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_getRomOrderToBeCanceled()
	 * is invoked and the method EbayEnterprise_Eb2cOrder_OrderController::getRequest() will be called and return
	 * an instance object that extend the class Zend_Controller_Request_Abstract. Then, an instance of type sales/order
	 * will be instantiated. Then, the method Zend_Controller_Request_Abstract::getParam() will be called, passed in the string literal 'order_id'
	 * and return the order id. This order id will be passed in sales/order::loadByIncrementId() method. Then, the method
	 * Zend_Controller_Request_Abstract::getParam() will be called, passed in string the literal 'cancel_reason'
	 * to the method sales/order::setCancelReasonCode(). Finally, the method EbayEnterprise_Eb2cOrder_OrderController::_getRomOrderToBeCanceled()
	 * will return the sales/order object.
	 */
	public function testGetRomOrderToBeCanceled()
	{
		/** @var null */
		$default = null;
		/** @var string */
		$orderId = '1000000083787831';
		/** @var string */
		$cancelReason = 'reason_code_001';

		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract', [], '', true, true, true, ['getParam']);
		$request->expects($this->exactly(2))
			->method('getParam')
			->will($this->returnValueMap([
				['order_id', $default, $orderId],
				['cancel_reason', $default, $cancelReason],
			]));

		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_Mage_Sales_Model_Order */
		$order = $this->getModelMock('sales/order', ['loadByIncrementId', 'setCancelReasonCode']);
		$order->expects($this->once())
			->method('loadByIncrementId')
			->with($this->identicalTo($orderId))
			->will($this->returnSelf());
		$order->expects($this->once())
			->method('setCancelReasonCode')
			->with($this->identicalTo($cancelReason))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'sales/order', $order);

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['getRequest'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('getRequest')
			->will($this->returnValue($request));

		$this->assertSame($order, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getRomOrderToBeCanceled', []));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_getRomOrderCancelModel()
	 * is invoked, it will be passed an instance of type sales/order and it will instantiate an instance of
	 * type ebayenterprise_order/cancel, passed in the required array with key 'order' mapped to a sales/order
	 * object to the constructor method and then return the ebayenterprise_order/cancel object.
	 */
	public function testGetRomOrderCancelModel()
	{
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_EbayEnterprise_Order_Model_Cancel */
		$cancel = $this->getModelMock('ebayenterprise_order/cancel', [], false, [[
			// This key is required
			'order' => $order,
		]]);
		$this->replaceByMock('model', 'ebayenterprise_order/cancel', $cancel);

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setConstructorArgs([$request, $response])
			->getMock();

		$this->assertSame($cancel, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getRomOrderCancelModel', [$order]));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_getRomCancelSession()
	 * is invoked, it will instantiate an instance of type core/session and then return the core/session object.
	 */
	public function testGetRomCancelSession()
	{
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var Mock_Mage_Core_Model_Session */
		$session = $this->getModelMockBuilder('core/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setConstructorArgs([$request, $response])
			->getMock();

		$this->assertSame($session, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getRomCancelSession', []));
	}

	/**
	 * @return array
	 */
	public function providerHandleRomCancelResponse()
	{
		return [
			[Mage_Sales_Model_Order::STATE_CANCELED],
			[null],
		];
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_handleRomCancelResponse()
	 * is invoked, it will be passed as first parameter a sales/order object, as second parameter core/session
	 * object, and as third parameter a string literal representing the redirect URL. Then, it will check if the
	 * sales/order:getState() matched the class constant Mage_Sales_Model_Order::STATE_CANCELED, if it does, then
	 * the method core/session::addSuccess() will be called passing in the translated and sprintf return value from
	 * calling the method ebayenterprise_order/data::__() with class constant EbayEnterprise_Eb2cOrder_OrderController::CANCEL_SUCCESS_MESSAGE
	 * pass its parameter. However, if the method sales/order:getState() doesn't matched the class constant
	 * Mage_Sales_Model_Order::STATE_CANCELED, then, the method core/session::addError() will be called and passed in the translated
	 * and sprintf return value from calling the method ebayenterprise_order/data::__() with class constant
	 * EbayEnterprise_Eb2cOrder_OrderController::CANCEL_FAIL_MESSAGE. Then, the method
	 * EbayEnterprise_Eb2cOrder_OrderController::_redirectUrl() will be invoked and passed in the parameter string
	 * literal representing the redirect URL. Finally, the method EbayEnterprise_Eb2cOrder_OrderController::_handleRomCancelResponse()
	 * will return itself.
	 *
	 *
	 * @param string | null
	 * @dataProvider providerHandleRomCancelResponse
	 */
	public function testHandleRomCancelResponse($state)
	{
		/** @var bool */
		$isSuccess = !is_null($state);
		/** @var string */
		$incrementId = '11999383838111991';
		/** @var string */
		$translatedSuccessMessage = EbayEnterprise_Eb2cOrder_OrderController::CANCEL_SUCCESS_MESSAGE;
		/** @var string */
		$succesMessage = sprintf($translatedSuccessMessage, $incrementId);
		/** @var string */
		$translatedFailMessage = EbayEnterprise_Eb2cOrder_OrderController::CANCEL_FAIL_MESSAGE;
		/** @var string */
		$failMessage = sprintf($translatedFailMessage, $incrementId);
		/** @var string */
		$redirectUrl = "http://test.example.com/sales/order/order_id/{$incrementId}";
		/** @var Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order', [
			'increment_id' => $incrementId,
			'state' => $state,
		]);
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var EbayEnterprise_Order_Helper_Data */
		$orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['__']);
		$orderHelper->expects($this->once())
			->method('__')
			->will($this->returnValueMap([
				[EbayEnterprise_Eb2cOrder_OrderController::CANCEL_SUCCESS_MESSAGE, $translatedSuccessMessage],
				[EbayEnterprise_Eb2cOrder_OrderController::CANCEL_FAIL_MESSAGE, $translatedFailMessage]
			]));

		/** @var Mock_Mage_Core_Model_Session */
		$session = $this->getModelMockBuilder('core/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->setMethods(['addSuccess', 'addError'])
			->getMock();
		$session->expects($isSuccess ? $this->once() : $this->never())
			->method('addSuccess')
			->with($this->identicalTo($succesMessage))
			->will($this->returnSelf());
		$session->expects($isSuccess ? $this->never() : $this->once())
			->method('addError')
			->with($this->identicalTo($failMessage))
			->will($this->returnSelf());

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_redirectUrl'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_redirectUrl')
			->with($this->identicalTo($redirectUrl))
			->will($this->returnSelf());
		// Replacing the protected class property EbayEnterprise_Eb2cOrder_OrderController::$_orderHelper with a mock.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($controller, '_orderHelper', $orderHelper);

		$this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_handleRomCancelResponse', [$order, $session, $redirectUrl]));
	}

	/**
	 * Test that the controller method EbayEnterprise_Eb2cOrder_OrderController::_handleRomCancelException()
	 * is invoked, it will be passed as first parameter an object of type Exception, as second parameter core/session
	 * object, and as third parameter a string literal representing the redirect URL. Then, the method core/session::addError()
	 * will be invoked and passed in as its parameter a return value from calling the method
	 * ebayenterprise_order/data::__(), this method will be passed in the return value from calling the Exception::getMessage()
	 * method. Then, the method EbayEnterprise_Eb2cOrder_OrderController::_redirectUrl() will be invoked and passed in the
	 * parameter string literal representing the redirect URL. Finally, the method EbayEnterprise_Eb2cOrder_OrderController::_handleRomCancelResponse()
	 * will return itself.
	 */
	public function testHandleRomCancelException()
	{
		/** @var string */
		$message = 'Exception test message.';
		/** @var string */
		$translatedMessage = $message;
		/** @var Mage_Core_Exception */
		$exception = Mage::exception('Mage_Core', $message);
		/** @var string */
		$redirectUrl = "http://test.example.com/sales/order/order_id/1000001";
		/** @var Mock_Zend_Controller_Request_Abstract */
		$request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		/** @var Mock_Zend_Controller_Response_Abstract */
		$response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		/** @var EbayEnterprise_Order_Helper_Data */
		$orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['__']);
		$orderHelper->expects($this->once())
			->method('__')
			->with($this->identicalTo($message))
			->will($this->returnValue($translatedMessage));

		/** @var Mock_Mage_Core_Model_Session */
		$session = $this->getModelMockBuilder('core/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->setMethods(['addError'])
			->getMock();
		$session->expects($this->once())
			->method('addError')
			->with($this->identicalTo($translatedMessage))
			->will($this->returnSelf());

		/** @var Mock_EbayEnterprise_Eb2cOrder_OrderController */
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cOrder_OrderController')
			->setMethods(['_redirectUrl'])
			->setConstructorArgs([$request, $response])
			->getMock();
		$controller->expects($this->once())
			->method('_redirectUrl')
			->with($this->identicalTo($redirectUrl))
			->will($this->returnSelf());
		// Replacing the protected class property EbayEnterprise_Eb2cOrder_OrderController::$_orderHelper with a mock.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($controller, '_orderHelper', $orderHelper);

		$this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_handleRomCancelException', [$exception, $session, $redirectUrl]));
	}
}
