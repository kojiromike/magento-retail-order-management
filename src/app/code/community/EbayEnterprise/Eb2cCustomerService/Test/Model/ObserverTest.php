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


class EbayEnterprise_Eb2cCustomerService_Test_Model_ObserverTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Get a Varien_Event_Observer to be passed to the observer method. The
	 * observer will have a Varien_Event with a controller with a request
	 * that has the given original path.
	 * @param  string $origPath
	 * @return Varien_Event_Observer
	 */
	protected function _getObserverWithRequest($origPath)
	{
		$request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')
			->disableOriginalConstructor()
			->setMethods(array('getOriginalPathInfo', 'getParam'))
			->getMock();
		$controller = $this->getMockBuilder('Mage_Adminhtml_Controller_Action')
			->disableOriginalConstructor()
			->setMethods(array('getRequest'))
			->getMock();
		$event = new Varien_Event(array('controller_action' => $controller));
		$observer = new Varien_Event_Observer(array('event' => $event));
		$controller->expects($this->any())
			->method('getRequest')
			->will($this->returnValue($request));
		$request->expects($this->any())
			->method('getOriginalPathInfo')
			->will($this->returnValue($origPath));
		return $observer;
	}
	/**
	 * Test the preDispatch token login check. Ensure that when the request
	 * original path matches the csrlogin url path, the current user is logged
	 * out and the token is validated.
	 * @test
	 */
	public function testPreDispatchTokenLogin()
	{
		$origPath = '/admin/csrlogin';
		$token = 'abc-123';

		$observer = $this->_getObserverWithRequest($origPath);
		$request = $observer->getEvent()->getControllerAction()->getRequest();
		$session = $this->getModelMockBuilder('admin/session')
			->disableOriginalConstructor()
			->setMethods(array('getUser', 'isLoggedIn', 'setUser', 'loginCSRWithToken'))
			->getMock();
		$this->replaceByMock('model', 'admin/session', $session);
		$user = $this->getModelMockBuilder('admin/user')
			->disableOriginalConstructor()
			->setMethods(array('unsetData'))
			->getMock();
		$csrObserver = Mage::getModel('eb2ccsr/observer');

		$request->expects($this->once())
			->method('getParam')
			->with($this->identicalTo('token'), $this->identicalTo(''))
			->will($this->returnValue($token));
		$session->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$session->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$session->expects($this->once())
			->method('setUser')
			->with($this->identicalTo($user))
			->will($this->returnSelf());
		$user->expects($this->once())
			->method('unsetData')
			->will($this->returnSelf());
		$session->expects($this->once())
			->method('loginCSRWithToken')
			->with($this->identicalTo($token), $this->identicalTo($request))
			->will($this->returnSelf());

		$this->assertSame($csrObserver, $csrObserver->preDispatchTokenLogin($observer));
	}
	/**
	 * When the original request path wasn't the CSR login path, don't attempt
	 * to login using a token.
	 * @test
	 */
	public function testPreDispatchTokenLoginNonCsrPath()
	{
		$origPath = '/admin/notcsrlogin';
		$observer = $this->_getObserverWithRequest($origPath);
		$session = $this->getModelMockBuilder('admin/session')
			->disableOriginalConstructor()
			->setMethods(array('loginCSRWithToken'))
			->getMock();
		$this->replaceByMock('model', 'admin/session', $session);
		$csrObserver = Mage::getModel('eb2ccsr/observer');

		$session->expects($this->never())
			->method('loginCSRWithToken');

		$this->assertSame(
			$csrObserver,
			$csrObserver->preDispatchTokenLogin($observer)
		);
	}
}