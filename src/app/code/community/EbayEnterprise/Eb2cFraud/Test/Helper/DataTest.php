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

class EbayEnterprise_Eb2cFraud_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_helper;
	protected $_jsModuleName;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = new EbayEnterprise_Eb2cFraud_Helper_Data();
		$this->_jsModuleName = EbayEnterprise_Eb2cFraud_Helper_Data::JSC_JS_PATH;
	}

	/**
	 * Get back sensible URL
	 * @test
	 */
	public function testGetJscUrl()
	{
		$url = $this->_helper->getJscUrl();
		$this->assertStringEndsWith($this->_jsModuleName, $url);
	}
	public function testGetJavaScriptFraudData()
	{
		$request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')
			->disableOriginalConstructor()
			->setMethods(array('getPost'))
			->getMock();
		$request->expects($this->exactly(2))
			->method('getPost')
			->will($this->returnValueMap(array(
				array('eb2cszyvl', '', 'random_field_name'),
				array('random_field_name', '', 'javascript_data'),
			)));
		$this->assertSame(
			'javascript_data',
			Mage::helper('eb2cfraud')->getJavaScriptFraudData($request)
		);
	}

	/**
	 * verify an array is returned containing data to populate the fields
	 * in the SessionInfo element
	 * @test
	 * @dataProvider provideTrueFalse
	 */
	public function testGetSessionInfo($isLoggedIn)
	{
		$session = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer', 'isLoggedIn', 'getEncryptedSessionId'))
			->getMock();
		$customer = $this->getModelMockBuilder('customer/customer')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'decryptPassword', 'getPassword'))
			->getmock();
		$visitorLog = $this->getModelMock('log/visitor', array('load', 'getFirstVisitAt', 'getLastVisitAt', 'getId'));
		$customerLog = $this->getModelMock('log/customer', array('load', 'getLoginAt'));
		$helper = $this->_helper;

		$this->replaceByMock('singleton', 'customer/session', $session);
		$this->replaceByMock('model', 'log/visitor', $visitorLog);
		$this->replaceByMock('model', 'log/customer', $customerLog);

		$expect = array(
			'TimeSpentOnSite' => '1:00:00',
			'LastLogin' => $isLoggedIn ? '2014-01-01T09:05:01+00:00' : '',
			'UserPassword' => $isLoggedIn ? 'password' : '',
			'TimeOnFile' => '',
			'RTCTransactionResponseCode' => '',
			'RTCReasonCodes' => '',
		);
		$sessionId = 'somesessionid';
		$visitorId = 10;

		$session->expects($this->any())
			->method('getCustomer')
			->will($this->returnValue($customer));
		$session->expects($this->any())
			->method('getEncryptedSessionId')
			->will($this->returnValue($sessionId));
		$session->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue($isLoggedIn));

		$visitorLog->expects($this->any())
			->method('load')
			->with($this->identicalTo($sessionId), $this->identicalTo('session_id'))
			->will($this->returnSelf());
		$visitorLog->expects($this->any())
			->method('getId')
			->will($this->returnValue($visitorId));
		$visitorLog->expects($this->any())
			->method('getFirstVisitAt')
			->will($this->returnValue('2014-01-01 08:55:01'));
		$visitorLog->expects($this->any())
			->method('getLastVisitAt')
			->will($this->returnValue('2014-01-01 09:55:01'));

		$customer->expects($this->any())
			->method('decryptPassword')
			->will($this->returnValue('password'));

		$customerLog->expects($this->any())
			->method('load')
			->with($this->identicalTo($visitorId), $this->identicalTo('visitor_id'))
			->will($this->returnSelf());
		$customerLog->expects($this->any())
			->method('getLoginAt')
			->will($this->returnValue('2014-01-01 09:05:01'));

		$this->assertSame($expect, $helper->getSessionInfo());
	}
}
