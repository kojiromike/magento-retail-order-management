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

/**
 *
 */
class EbayEnterprise_Eb2cFraud_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case_Config
{
	/**
	 * Is this observer first of all defined?
	 * @test
	 */
	public function testEventObserverDefined()
	{
		$areas = array('global');
		foreach($areas as $area) {
			$this->assertEventObserverDefined(
				$area,
				'sales_model_service_quote_submit_before',
				'eb2cfraud/observer',
				'captureOrderContext',
				'capture_order_context'
			);
		}
	}

	/**
	 * Test capturing order context, all context data comes from various other
	 * places so just ensure that the method is attempting to add some data
	 * for each of the data fields we're expecting to have populated.
	 * @test
	 */
	public function testObserverMethod()
	{
		$mockOrder = $this->getModelMock('sales/order', array('addData', 'save'));
		$observer = $this->getModelMock('eb2cfraud/observer', array('_getRequest'));
		$mockRequest = $this->getMock('Mage_Core_Controller_Request_Http');

		$observer->expects($this->any())
			->method('_getRequest')
			->will($this->returnValue($mockRequest));

		$mockOrder->expects($this->once())
			->method('addData')
			->with(array(
				'eb2c_fraud_char_set' => 'charset',
				'eb2c_fraud_content_types' => 'types',
				'eb2c_fraud_encoding' => 'encoding',
				'eb2c_fraud_host_name' => 'h.ost',
				'eb2c_fraud_referrer' => 'http://other.host/order/source',
				'eb2c_fraud_user_agent' => 'user agent',
				'eb2c_fraud_language' => 'english',
				'eb2c_fraud_ip_address' => '1.0.0.1',
				'eb2c_fraud_session_id' => '123456',
				'eb2c_fraud_javascript_data' => 'javascript data',
			))
			->will($this->returnSelf());
		// Saving the order
		$mockOrder->expects($this->never())
			->method('save')
			->will($this->returnSelf());

		{ // mock the http helper (note: in braces for code folding editors)
			$httpHelper = $this->getHelperMock('eb2cfraud/http', array(
				'getHttpAcceptCharset',
				'getHttpAccept',
				'getHttpAcceptEncoding',
				'getRemoteHost',
				'getHttpUserAgent',
				'getHttpAcceptLanguage',
				'getRemoteAddr',
				'getHttpConnection',
			));
			$httpHelper->expects($this->any())
				->method('getHttpAcceptCharset')
				->will($this->returnValue('charset'));
			$httpHelper->expects($this->any())
				->method('getHttpAccept')
				->will($this->returnValue('types'));
			$httpHelper->expects($this->any())
				->method('getHttpAcceptEncoding')
				->will($this->returnValue('encoding'));
			$httpHelper->expects($this->any())
				->method('getHttpUserAgent')
				->will($this->returnValue('user agent'));
			$httpHelper->expects($this->any())
				->method('getHttpAcceptLanguage')
				->will($this->returnValue('english'));
			$httpHelper->expects($this->any())
				->method('getRemoteAddr')
				->will($this->returnValue('1.0.0.1'));
			$httpHelper->expects($this->any())
				->method('getRemoteHost')
				->will($this->returnValue('h.ost'));
			$httpHelper->expects($this->any())
				->method('getHttpConnection')
				->will($this->returnValue('this is usually "close"'));
			$this->replaceByMock('helper', 'eb2cfraud/http', $httpHelper);
		}

		$fraudHelper = $this->getHelperMock('eb2cfraud/data', array(
			'getJavaScriptFraudData',
			'getSessionInfo',
		));
		$fraudHelper->expects($this->any())
			->method('getJavaScriptFraudData')
			->with($this->identicalTo($mockRequest))
			->will($this->returnValue('javascript data'));
		$fraudHelper->expects($this->any())
			->method('getSessionInfo')
			->will($this->returnValue(array('session info')));
		$this->replaceByMock('helper', 'eb2cfraud', $fraudHelper);

		$cookie = $this->getModelMockBuilder('core/cookie')
			->disableOriginalConstructor()
			->setMethods(array('get'))
			->getMock();
		$cookie->expects($this->any())
			->method('get')
			->will($this->returnValue(array('C is for cookie, and cookie is for...')));
		$this->replaceByMock('singleton', 'core/cookie', $cookie);

		$sessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getEncryptedSessionId', 'getOrderSource'))
			->getMock();
		$sessionMock->expects($this->once())
			->method('getEncryptedSessionId')
			->will($this->returnValue('123456'));
		$sessionMock->expects($this->once())
			->method('getOrderSource')
			->will($this->returnValue('http://other.host/order/source'));
		$this->replaceByMock('singleton', 'customer/session', $sessionMock);

		$checkoutMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('addData'))
			->getMock();
		$timestamp = new DateTime();
		$testCase = $this;
		$checkoutMock->expects($this->once())
			->method('addData')
			->will($this->returnCallback(function($data) use ($timestamp, $checkoutMock, $testCase)
				{
					$testCase->assertContains($timestamp->format('Y-m-d\TH:i'), $data['eb2c_fraud_timestamp']);
					$expected = array(
						'eb2c_fraud_cookies' => array('C is for cookie, and cookie is for...'),
						'eb2c_fraud_connection' => 'this is usually "close"',
						'eb2c_fraud_session_info' => array('session info'),
					);
					foreach ($expected as $key => $value) {
						$testCase->assertSame($value, $data[$key]);
					}
					return $checkoutMock;
				}
			));
		$this->replaceByMock('singleton', 'checkout/session', $checkoutMock);

		$observer->captureOrderContext(
			new Varien_Event_Observer(array(
				'event' => new Varien_Event(array('order' => $mockOrder,))
			))
		);
	}

	/**
	 * Returns a mocked object
	 * @todo: Merge this into test base class?
	 * @param a Magento Class Alias
	 * @param array of key / value pairs; key is the method name, value is value returned by that method
	 *
	 * @return mocked-object
	 */
	private function _getFullMocker($classAlias, $mockedMethodSet, $disableConstructor=true)
	{
		$justMethodNames = array_keys($mockedMethodSet);
		$mock = null;

		if( $disableConstructor ) {
			$mock = $this->getModelMockBuilder($classAlias)
				->disableOriginalConstructor()
				->setMethods($justMethodNames)
				->getMock();
		} else {
			$mock = $this->getModelMockBuilder($classAlias)
				->setMethods($justMethodNames)
				->getMock();
		}

		reset($mockedMethodSet);
		foreach($mockedMethodSet as $method => $returnSet ) {
			$mock->expects($this->any())
				->method($method)
				->will($this->returnValue($returnSet));
		}
		return $mock;
	}

	/**
	 * @todo: Merge this into test base class?
	 */
	public function replaceModel($classAlias, $mockedMethodSet, $disableOriginalConstructor=true)
	{
		$mock = $this->_getFullMocker($classAlias, $mockedMethodSet, $disableOriginalConstructor);
		$this->replaceByMock('model', $classAlias, $mock);
		return $mock;
	}

	public function replaceSingleton($classAlias, $mockedMethodSet, $disableOriginalConstructor=true)
	{
		$mock = $this->_getFullMocker($classAlias, $mockedMethodSet, $disableOriginalConstructor);
		$this->replaceByMock('singleton', $classAlias, $mock);
		return $mock;
	}

}
