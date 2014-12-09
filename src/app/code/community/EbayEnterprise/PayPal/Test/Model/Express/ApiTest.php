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

use eBayEnterprise\RetailOrderManagement\Api;
use eBayEnterprise\RetailOrderManagement\Payload;

class EbayEnterprise_PayPal_Test_Model_Express_ApiTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const BIDIRECTIONAL_API = '\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi';
	const SETEXPRESS_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalSetExpressCheckoutRequest';
	const SETEXPRESS_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalSetExpressCheckoutReply';
	const GETEXPRESS_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalGetExpressCheckoutRequest';
	const GETEXPRESS_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalGetExpressCheckoutReply';

	protected $_sdk;
	protected $_coreHelper;
	protected $_helper;
	protected $_checkoutSession;
	protected $_getSdkApiMap;

	public function setUp()
	{
		parent::setUp();
		// disable _construct to prevent excessive stubs
		$this->_coreUrl = $this->getModelMock(
			'core/url', array('_construct', 'getUrl')
		);
		$this->_coreUrl->expects($this->any())
			->method('getUrl')->will(
				$this->returnValueMap(
					array(
						array('ebayenterprise_paypal_express/checkout/return',
						      array(), 'the.return.url'),
						array('ebayenterprise_paypal_express/checkout/cancel',
						      array(), 'the.cancel.url'),
					)
				)
			);
		// stub magento internals
		$this->_checkoutSession = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->getMock();
		// stub sdk
		$this->_sdk = $this->getMock(self::BIDIRECTIONAL_API);
		// stub members
		$this->_getSdkApiMap = array(
			array('payments', 'paypal/setExpress', array(), $this->_sdk),
			array('payments', 'paypal/getExpress', array(), $this->_sdk),
		);
		$this->_coreHelper = $this->getHelperMock(
			'eb2ccore/data', array('getSdkApi')
		);
		$this->_coreHelper->expects($this->any())
			->method('getSdkApi')->will(
				$this->returnValueMap($this->_getSdkApiMap)
			);
		$this->_helper = $this->getHelperMock(
			'ebayenterprise_paypal/data', array('getConfigModel', '__')
		);
		$this->_helper->expects($this->any())->method('__')->will(
			$this->returnArgument(0)
		);
		$this->_quote = $this->getModelMock(
			'sales/quote', array('reserveOrderId', 'getReservedOrderId',
			                     'getAllNonNominalItems', 'getTotals')
		);
		$this->_quote->expects($this->any())
			->method('reserveOrderId')->will($this->returnSelf());
		$this->_quote->expects($this->any())
			->method('getTotals')->will(
				$this->returnValue(
					array(
						'grand_total' => new Varien_Object(
							array('value' => 100)
						),
						'shipping'    => new Varien_Object(
							array('value' => 5.95)
						),
						'tax'         => new Varien_Object(
							array('value' => 2.50)
						),
					)
				)
			);
		$this->_quote->expects($this->any())
			->method('getReservedOrderId')->will($this->returnValue('orderid'));
		$this->_quote->expects($this->any())
			->method('getAllNonNominalItems')->will(
				$this->returnValue(array())
			);
		$this->_quote->setData(
			array(
				'quote_currency_code' => 'USD'
			)
		);
	}

	public function provideSdkExceptions()
	{
		return array(
			array('\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError'),
			array('\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload'),
			array('\eBayEnterprise\RetailOrderManagement\Payload\Exception\UnexpectedResponse'),
			array('\eBayEnterprise\RetailOrderManagement\Payload\Exception\UnsupportedPayload'),
		);
	}

	/**
	 * verify
	 * - for all exceptions, throw EbayEnterprise_PayPal_Exception with a translated message.
	 *
	 * @dataProvider provideSdkExceptions
	 */
	public function testSetExpressCheckoutWithSdkException($exception)
	{
		$this->replaceByMock('model', 'core/url', $this->_coreUrl);
		$config = $this->buildCoreConfigRegistry(
			array(
				'apiOperationSetExpressCheckout' => 'paypal/setExpress',
				'apiService'                     => 'payments',
			)
		);
		$this->_helper->expects($this->any())
			->method('getConfigModel')->will($this->returnValue($config));

		$setExpressRequest = $this->getMock(self::SETEXPRESS_REQUEST_PAYLOAD);
		$setExpressRequest->expects($this->any())
			->method('setOrderId')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setReturnUrl')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setCancelUrl')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setLocaleCode')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setAmount')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setCurrencyCode')->will($this->returnSelf());

		$this->_sdk->expects($this->exactly(2))
			->method('getRequestBody')->will(
				$this->returnValue($setExpressRequest)
			);
		$this->_sdk->expects($this->once())
			->method('setRequestBody')->with(
				$this->isInstanceOf(self::SETEXPRESS_REQUEST_PAYLOAD)
			)
			->will($this->returnSelf());
		$this->_sdk->expects($this->once())
			->method('send')->will($this->throwException(new $exception));
		$this->_sdk->expects($this->never())
			->method('getResponseBody');

		// test setExpressCheckout
		$message
			= EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
		$api = $this->getModelMock(
			'ebayenterprise_paypal/express_api',
			array('_addLineItems', '_addShippingAddress')
		);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		$this->setExpectedException(
			'EbayEnterprise_PayPal_Exception', $message
		);
		$api->setExpressCheckout('*/*/return', '*/*/cancel', $this->_quote);
	}

	/**
	 * verify
	 * - for a failure response to a successful request, throw EbayEnterprise_PayPal_Exception with a translated message.
	 */
	public function testStartWithFailureResponse()
	{
		$this->replaceByMock('model', 'core/url', $this->_coreUrl);
		$config = $this->buildCoreConfigRegistry(
			array(
				'apiOperationSetExpressCheckout' => 'paypal/setExpress',
				'apiService'                     => 'payments',
			)
		);
		$this->_helper->expects($this->any())
			->method('getConfigModel')->will($this->returnValue($config));

		$setExpressRequest = $this->getMock(self::SETEXPRESS_REQUEST_PAYLOAD);
		$setExpressRequest->expects($this->any())
			->method('setOrderId')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setReturnUrl')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setCancelUrl')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setLocaleCode')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setAmount')->will($this->returnSelf());
		$setExpressRequest->expects($this->any())
			->method('setCurrencyCode')->will($this->returnSelf());

		$setExpressRequest = $this->getMock(self::SETEXPRESS_REQUEST_PAYLOAD);
		$setExpressReply = $this->getMock(self::SETEXPRESS_REPLY_PAYLOAD);
		$this->_sdk->expects($this->any())
			->method('getRequestBody')->will($this->returnValue(false));
		$this->_sdk->expects($this->once())
			->method('setRequestBody')->with(self::SETEXPRESS_REQUEST_PAYLOAD)
			->will($this->returnSelf());
		$this->_sdk->expects($this->once())
			->method('send')->will($this->returnSelf());
		$setExpressReply->expects($this->any())
			->method('isSuccess')->will($this->returnValue(false));
		// test setExpressCheckout
		$message
			= EbayEnterprise_PayPal_Model_Method_Express::PAYPAL_CHECKOUT_FAILED_MESSAGE;
		$api = $this->getModelMock(
			'ebayenterprise_paypal/express_api', array('_prepareRequest')
		);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$api, '_helper', $this->_helper
		);
		$this->setExpectedException(
			'EbayEnterprise_PayPal_Exception', $message
		);
		$api->setExpressCheckout($this->_quote);
	}

	/**
	 * verify
	 * - a request payload is acquired and sent to get a response body.
	 */
	public function testSetExpressCheckout()
	{
		$this->replaceByMock('model', 'core/url', $this->_coreUrl);
		$config = $this->buildCoreConfigRegistry(
			array(
				'apiOperationSetExpressCheckout' => 'paypal/setExpress',
				'apiService'                     => 'payments',
			)
		);
		$this->_helper->expects($this->any())
			->method('getConfigModel')->will($this->returnValue($config));

		// mock the request
		$setExpressRequest = $this->getMock(self::SETEXPRESS_REQUEST_PAYLOAD);
		$setExpressRequest->expects($this->once())
			->method('setOrderId')->with($this->identicalTo('orderid'))->will(
				$this->returnSelf()
			);
		$setExpressRequest->expects($this->once())
			->method('setReturnUrl')->with($this->identicalTo('the.return.url'))
			->will($this->returnSelf());
		$setExpressRequest->expects($this->once())
			->method('setCancelUrl')->with($this->identicalTo('the.cancel.url'))
			->will($this->returnSelf());
		$setExpressRequest->expects($this->once())
			->method('setLocaleCode')->with($this->identicalTo('en_US'))->will(
				$this->returnSelf()
			);
		$setExpressRequest->expects($this->once())
			->method('setAmount')->with($this->identicalTo(100))->will(
				$this->returnSelf()
			);
		$setExpressRequest->expects($this->once())
			->method('setCurrencyCode')->with($this->identicalTo('USD'))->will(
				$this->returnSelf()
			);

		// mock the reply
		$setExpressReply = $this->getMock(self::SETEXPRESS_REPLY_PAYLOAD);
		$setExpressReply->expects($this->any())
			->method('isSuccess')->will($this->returnValue(true));
		$setExpressReply->expects($this->any())
			->method('getToken')->will($this->returnValue('theToken'));
		$setExpressReply->expects($this->any())
			->method('getOrderId')->will($this->returnValue('orderid'));

		$this->_sdk->expects($this->once())
			->method('getRequestBody')->will(
				$this->returnValue($setExpressRequest)
			);
		// setup the sdk and populate a request payload.
		$this->_sdk->expects($this->once())
			->method('setRequestBody')->with(
				$this->isInstanceOf(self::SETEXPRESS_REQUEST_PAYLOAD)
			)
			->will($this->returnSelf());
		$this->_sdk->expects($this->once())
			->method('send')->will($this->returnSelf());
		$this->_sdk->expects($this->once())
			->method('getResponseBody')->will(
				$this->returnValue($setExpressReply)
			);
		$setExpressReply->expects($this->any())
			->method('isSuccess')->will($this->returnValue(true));

		// test setExpressCheckout
		$api = $this->getModelMock(
			'ebayenterprise_paypal/express_api', array('_addShippingAddress')
		);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		$result = $api->setExpressCheckout($this->_quote);
		$this->assertEquals(
			array('token' => 'theToken', 'order_id' => 'orderid'), $result
		);
	}

	/**
	 * verify
	 * - get express checkout request is made properly
	 * - verify data is returned as an array
	 */
	public function testGetExpressCheckout()
	{
		$orderId = 'orderid';
		$currencyCode = 'USD';
		$token = 'thetoken';

		$this->replaceByMock('model', 'core/url', $this->_coreUrl);
		$config = $this->buildCoreConfigRegistry(
			array(
				'apiOperationGetExpressCheckout' => 'paypal/getExpress',
				'apiService'                     => 'payments',
			)
		);
		$this->_helper->expects($this->any())
			->method('getConfigModel')->will($this->returnValue($config));

		// mock the request
		$getExpressRequest = $this->getMock(self::GETEXPRESS_REQUEST_PAYLOAD);
		$getExpressRequest->expects($this->once())
			->method('setOrderId')->with($this->identicalTo('orderid'))->will(
				$this->returnSelf()
			);
		$getExpressRequest->expects($this->once())
			->method('setToken')->with($this->identicalTo('thetoken'))->will(
				$this->returnSelf()
			);
		$getExpressRequest->expects($this->once())
			->method('setCurrencyCode')->with($this->identicalTo('USD'))->will(
				$this->returnSelf()
			);

		// mock the reply
		$getExpressReply = $this->getMock(self::GETEXPRESS_REPLY_PAYLOAD);
		$getExpressReply->expects($this->any())
			->method('isSuccess')->will($this->returnValue(true));

		$this->_sdk->expects($this->atLeastOnce())
			->method('getRequestBody')->will(
				$this->returnValue($getExpressRequest)
			);
		// setup the sdk and populate a request payload.
		$this->_sdk->expects($this->once())
			->method('setRequestBody')->with(
				$this->isInstanceOf(self::GETEXPRESS_REQUEST_PAYLOAD)
			)
			->will($this->returnSelf());
		$this->_sdk->expects($this->once())
			->method('send')->will($this->returnSelf());
		$this->_sdk->expects($this->atLeastOnce())
			->method('getResponseBody')->will(
				$this->returnValue($getExpressReply)
			);

		// test setExpressCheckout
		$api = $this->getModelMock('ebayenterprise_paypal/express_api', null);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		$data = $api->getExpressCheckout($orderId, $token, $currencyCode);
		$this->assertSame('array', gettype($data));
		$this->assertNotEmpty($data);
	}
}
