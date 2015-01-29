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
	/** @var Mage_Customer_Model_Session stub */
	protected $_customerSessionStub;
	protected $_coreHelperStub;
	protected $_storeStub;

	public function setUp()
	{
		parent::setUp();
		// stub the customer session
		$this->_customerSessionStub = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(['isLoggedIn', 'getEncryptedSessionId'])
			->getMock();
		$this->_customerSessionStub->expects($this->any())
			->method('getEncryptedSessionId')
			->will($this->returnValue('sessionid'));

		$this->_storeStub = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor() // prevent issues with the session
			->setMethods(['isAdmin'])
			->getMock();

		$this->_coreHelperStub = $this->getHelperMock('eb2ccore/data', ['getCurrentStore']);
		$this->_coreHelperStub->expects($this->any())
			->method('getCurrentStore')
			->will($this->returnValue($this->_storeStub));
		$this->_orderStub = $this->getModelMock('sales/order', ['getId']);
		$this->_payload = Mage::helper('eb2ccore')
			->getSdkApi('orders', 'create')
			->getRequestBody();
	}

	/**
	 * verify an array is returned containing data to populate the fields
	 * in the SessionInfo element
	 * @dataProvider provideTrueFalse
	 */
	public function testGetSessionInfo($isLoggedIn)
	{
		$sessionId = 'somesessionid';

		$visitorLog = $this->getModelMock('log/visitor', ['load']);
		$visitorLog->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$visitorLog->setIdFieldName('visitor_id')
			->setFirstVisitAt('2014-01-01 08:55:01')
			->setLastVisitAt('2014-01-01 09:55:00');

		$customerLog = $this->getModelMock('log/customer', ['load']);
		$customerLog->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$customerLog->setIdFieldName('id')
			->setLoginAt('2014-01-01 09:05:01');

		$this->_customerSessionStub->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue($isLoggedIn));

		$helperMock = $this->getHelperMock('eb2cfraud/data', ['_getCustomerSession']);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($helperMock, [
			'_customerLog' => $customerLog,
			'_visitorLog' => $visitorLog,
			'_coreHelper' => $this->_coreHelperStub,
		]);
		$helperMock->expects($this->any())
			->method('_getCustomerSession')
			->will($this->returnValue($this->_customerSessionStub));
		$result = $helperMock->getSessionInfo();

		$interval = $result['time_spent_on_site'];
		$this->assertNotNull($interval);
		foreach (['y', 'm', 'd', 'h'] as $property) {
			$this->assertSame(0, $interval->$property);
		}
		$this->assertSame(59, $interval->i);
		$this->assertSame(59, $interval->s);

		$lastLogin = $result['last_login'];
		if ($isLoggedIn) {
			$this->assertSame(
				'2014-01-01 09:05:01',
				$lastLogin->format(EbayEnterprise_Eb2cFraud_Helper_Data::MAGE_DATETIME_FORMAT)
			);
		} else {
			$this->assertNull($result['last_login']);
		}
		$this->assertNotEmpty($result['encrypted_session_id']);
		// the session ids should not match because of the hash
		$this->assertNotEquals($sessionId, $result['encrypted_session_id']);
	}
	/**
	 * Test that when customer/visitor logging is disabled, empty values are
	 * returned for time spent on site, last login
	 */
	public function testGetSessionInfoMissingLogData()
	{
		$visitorLog = $this->getModelMock('log/visitor', ['load']);
		$visitorLog->expects($this->any())
			->method('load')
			->will($this->returnValue(null));
		$visitorLog->setIdFieldName('visitor_id');

		$customerLog = $this->getModelMock('log/customer', ['load']);
		$customerLog->expects($this->any())
			->method('load')
			->will($this->returnValue(null));
		$customerLog->setIdFieldName('id');

		$this->_customerSessionStub->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(false));

		$helperMock = $this->getHelperMock('eb2cfraud/data', ['_getCustomerSession']);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($helperMock, [
			'_customerLog' => $customerLog,
			'_visitorLog' => $visitorLog,
			'_coreHelper' => $this->_coreHelperStub,
		]);
		$helperMock->expects($this->any())
			->method('_getCustomerSession')
			->will($this->returnValue($this->_customerSessionStub));
		$result = $helperMock->getSessionInfo();

		$this->assertNull($result['time_spent_on_site']);
		$this->assertNull($result['last_login']);
	}


	public function provideOrderSourceData()
	{
		return [
			[true, 'sessionsource', EbayEnterprise_Eb2cFraud_Helper_Data::BACKEND_ORDER_SOURCE],
			[false, null, EbayEnterprise_Eb2cFraud_Helper_Data::FRONTEND_ORDER_SOURCE],
			[false, 'sessionsource', 'sessionsource'],
		];
	}
	/**
	 * verify
	 * - order source is from the backend when placing an order from
	 *   the admin store.
	 * - order source is frontend when placing the order from the frontend
	 *   and no order source is found in the session
	 * - order source is taken from the session when placing an order from the
	 *   frontend and order_source is set in the session
	 *
	 * @param  bool   $isAdmin
	 * @param  string $isAdmin
	 * @param  string $orderSource
	 * @dataProvider provideOrderSourceData
	 */
	public function testGetOrderSource($isAdmin, $orderSource, $expOrderSource)
	{
		$this->_storeStub->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue($isAdmin));
		$this->_customerSessionStub->setOrderSource($orderSource);

		$visitorLog = $this->getModelMock('log/visitor', ['load']);
		$visitorLog->expects($this->any())
			->method('load')
			->will($this->returnValue(null));
		$visitorLog->setIdFieldName('visitor_id');

		$customerLog = $this->getModelMock('log/customer', ['load']);
		$customerLog->expects($this->any())
			->method('load')
			->will($this->returnValue(null));
		$customerLog->setIdFieldName('id');

		$this->_customerSessionStub->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(false));

		$helperMock = $this->getHelperMock('eb2cfraud/data', ['_getCustomerSession']);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($helperMock, [
			'_customerLog' => $customerLog,
			'_visitorLog' => $visitorLog,
			'_coreHelper' => $this->_coreHelperStub,
		]);
		$helperMock->expects($this->any())
			->method('_getCustomerSession')
			->will($this->returnValue($this->_customerSessionStub));
		$result = $helperMock->getSessionInfo();

		$this->assertSame($expOrderSource, $result['order_source']);
	}
}
