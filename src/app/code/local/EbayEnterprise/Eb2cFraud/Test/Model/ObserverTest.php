<?php
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
		$areas = array( 'frontend' );
		foreach( $areas as $area ) {
			$this->assertEventObserverDefined(
				$area,
				'eb2c_onepage_save_order_before',
				'eb2cfraud/observer',
				'captureOrderContext',
				'capture_order_context'
			);
		}
	}

	/**
	 * @test
	 */
	public function testObserverMethod()
	{
		// Get a phony quote ...
		$mockQuote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save'))
			->getMock();

		$mockQuote->expects($this->any())
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

		$mockQuote->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		// Get a phony request ...
		$mockRequest = $this->getModelMockBuilder('varien/object')
			->disableOriginalConstructor()
			->setMethods(array('getPost'))
			->getMock();

		// From request, we'll need a getPost Method:
		$mockRequest->expects($this->any())
			->method('getPost')
			->will($this->returnValue('sample_js_data'));

		{ // mock the http helper (note: in braces for code folding editors)
			$httpHelper = $this->getHelperMock('eb2cfraud/http', array(
				'getHttpAcceptCharset',
				'getHttpAccept',
				'getHttpAcceptEncoding',
				'getHttpRemoteHost',
				'getHttpUserAgent',
				'getHttpAcceptLanguage',
				'getRemoteAddr',
				'getRemoteHost',
				'getHttpCookies',
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
			'getCookies',
		));
		$fraudHelper->expects($this->any())
			->method('getJavaScriptFraudData')
			->with($this->identicalTo($mockRequest))
			->will($this->returnValue('javascript data'));
		$fraudHelper->expects($this->any())
			->method('getCookies')
			->will($this->returnValue(array('C is for cookie, and cookie is for...')));
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

		Mage::getModel('eb2cfraud/observer')->captureOrderContext(
			$this->replaceModel(
				'varien/event_observer',
				array(
					'getEvent' =>
					$this->replaceModel(
						'varien/event',
						array(
							'getQuote' => $mockQuote,
							'getRequest' => $mockRequest
						)
					)
				)
			)
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

	public function provideActionNames()
	{
		return array(
			array('save'),
			array('foo'),
		);
	}
	/**
	 * when the action is 'save' run the method to capture the fraud data.
	 * @test
	 * @dataProvider provideActionNames
	 */
	public function testCaptureAdminOrderContext($actionName)
	{
		$observer = $this->getModelMock('eb2cfraud/observer', array('captureOrderContext', '_getRequest'));
		$request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')
			->disableOriginalConstructor()
			->setMethods(array('getActionName'))
			->getMock();
		$adminOrderCreate = $this->getModelMockBuilder('adminhtml/sales_order_create')
			->disableOriginalConstructor()
			->setMethods(array('getQuote'))
			->getMock();
		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->getMock();
		$eventObserver = new Varien_Event_Observer(array('event' => new Varien_event(array(
			'order_create_model' => $adminOrderCreate,
			'request' => $request,
		))));

		$request->expects($this->any())
			->method('getActionName')
			->will($this->returnValue($actionName));
		$adminOrderCreate->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$observer->expects($this->any())
			->method('_getRequest')
			->will($this->returnValue($request));

		$testCase = $this;
		if ($actionName == 'save') {
			$observer->expects($this->once())
				->method('captureOrderContext')
				->with($this->callback(
					function($e) use ($testCase, $quote, $request) {
						$testCase->assertSame($quote, $e->getEvent()->getQuote());
						$testCase->assertSame($request, $e->getEvent()->getRequest());
						return true;
					}
				))
				->will($this->returnSelf());
		} else {
			$observer->expects($this->never())
				->method('captureOrderContext');
		}

		$observer->captureAdminOrderContext($eventObserver);
	}
}
