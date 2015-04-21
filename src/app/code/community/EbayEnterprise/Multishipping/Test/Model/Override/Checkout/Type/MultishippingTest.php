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

class EbayEnterprise_Multishipping_Test_Model_Override_Checkout_Type_MultishippingTest extends EcomDev_PHPUnit_Test_Case
{
	/** @var Mage_Sales_Model_Quote */
	protected $_quote;
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var Mage_Sales_Model_Service_Quote */
	protected $_quoteService;
	/** @var EbayEnterprise_Multishipping_Helper_Factory */
	protected $_multishippingFactory;
	/** @var Mage_Checkout_Model_Session */
	protected $_checkoutSession;
	/** @var Mage_Core_Model_Session */
	protected $_coreSession;
	/** @var EbayEnterprise_Multishipping_Override_Model_Checkout_Type_Multishipping */
	protected $_multishippingCheckout;

	protected function setUp()
	{
		// Stub log context builder to prevent session hits while collecting
		// log context meta-data.
		$logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
		$logContext->method('getMetaData')->will($this->returnValue([]));

		$this->_quote = $this->getModelMock(
			'sales/quote',
			['getId', 'save']
		);
		$this->_order = $this->getModelMock(
			'sales/order',
			['getCanSendNewEmailFlag', 'queueNewOrderEmail', 'getId', 'getIncrementId']
		);
		$this->_quoteService = $this->getModelMockBuilder('sales/service_quote')
			->disableOriginalConstructor()
			->setMethods(['submitOrder', 'setCheckoutDispatcher'])
			->getMock();
		$this->_multishippingFactory = $this->getHelperMock(
			'ebayenterprise_multishipping/factory',
			['createQuoteService']
		);
		$this->_multishippingFactory->method('createQuoteService')
			->with($this->identicalTo($this->_quote), $this->isTrue())
			->will($this->returnValue($this->_quoteService));
		$this->_checkoutSession = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(['setLastQuoteId'])
			->getMock();
		$this->_coreSession = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(['setOrderIds'])
			->getMock();

		// Create the multishipping checkout type model to test - mocked to
		// prevent the need for complex mocking for inherited behaviors.
		$builder = $this->getModelMockBuilder('checkout/type_multishipping')
			->setMethods(['_validate', '_init', 'getQuote'])
			->setConstructorArgs([[
				'multishipping_factory' => $this->_multishippingFactory,
				'log_context' => $logContext,
				'core_session' => $this->_coreSession,
				'checkout_session' => $this->_checkoutSession,
			]]);
		$this->_multishippingCheckout = $builder->getMock();

		$this->_multishippingCheckout->method('getQuote')
			->will($this->returnValue($this->_quote));

		// Disable event dispatch to prevent event handling from causing
		// any unexpected side-effects during the test.
		Mage::app()->disableEvents();
	}

	protected function tearDown()
	{
		// Re-enable event dispatch to restore normal Magento behavior.
		Mage::app()->enableEvents();
	}

	/**
	 * When successfully creating a new order, the current quote should be
	 * saved to persist changes triggered by creating the order.
	 */
	public function testCreateOrdersSuccess()
	{
		// Stubbing dependencies to get through successfully creating the order.
		$this->_order->method('getCanSendNewEmailFlag')->will($this->returnValue(false));
		$this->_coreSession->method('setOrderIds')->will($this->returnSelf());
		$this->_checkoutSession->method('setLastQuoteId')->will($this->returnSelf());
		$this->_order->method('getId')->will($this->returnValue(1));
		$this->_order->method('getIncrementId')->will($this->returnValue(1));
		$this->_quote->method('getId')->will($this->returnValue(3));

		// When the order is created successfully, the quote service will return
		// the newly created order.
		$this->_quoteService->method('submitOrder')->will($this->returnValue($this->_order));

		// Side-effect test: ensure current quote is saved.
		$this->_quote->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$this->assertSame(
			$this->_multishippingCheckout,
			$this->_multishippingCheckout->createOrders()
		);
	}

	/**
	 * When an order has not been submitted successfully, changes to the quote
	 * caused by attempting to submit the order should not be saved.
	 */
	public function testCreateOrdersFailure()
	{
		// When an order is not created, the quote service may return null when
		// trying to submit the new order - although more likely is that the
		// service will throw an exception, which is tested in
		// self::testCreateOrdersFailExceptions.
		$this->_quoteService->method('submitOrder')->will($this->returnValue(null));

		// Side-effect test: if the order was not created, do not save quote
		// changes caused by creating the order.
		$this->_quote->expects($this->never())
			->method('save');

		$this->assertSame(
			$this->_multishippingCheckout,
			$this->_multishippingCheckout->createOrders()
		);
	}

	/**
	 * When creating a new order fails with an exception, the exception should
	 * be thrown from the multishipping checkout type's createOrders and
	 * any changes to the quote should not be saved.
	 */
	public function testCreateOrdersFailureWithExceptions()
	{
		$exceptionMessage = 'Some exception thrown while submitting order.';
		$orderSubmitException = new Exception($exceptionMessage);
		// If the order cannot be created, an exception may be thrown when
		// submitting the order. Any such exceptions should not be caught and
		// should prevent the quote from being saved.
		$this->_quoteService->method('submitOrder')->will($this->throwException($orderSubmitException));

		// Side-effect test: if the order was not created, do not save quote
		// changes caused by creating the order.
		$this->_quote->expects($this->never())
			->method('save');

		// Expect that the exception thrown by submitOrder are not caught.
		$this->setExpectedException('Exception', $exceptionMessage);
		$this->_multishippingCheckout->createOrders();
	}

	/**
	 * When submitting an order, the order can be properly submitted by using
	 * the quote service. The newly created order object should be returned.
	 */
	public function testSubmitOrder()
	{
		$this->_quoteService->expects($this->once())
			->method('submitOrder')
			->will($this->returnValue($this->_order));
		$this->assertSame(
			$this->_order,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_multishippingCheckout,
				'_submitOrder')
		);
	}

	/**
	 * When queuing a new order email, if a new email can be sent, one should
	 * be queued for the order.
	 */
	public function testQueueNewOrderEmailSuccess()
	{
		$this->_order->method('getCanSendNewEmailFlag')
			->will($this->returnValue(true));
		$this->_order->expects($this->once())
			->method('queueNewOrderEmail')
			->will($this->returnSelf());

		$this->assertSame(
			$this->_multishippingCheckout,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_multishippingCheckout,
				'_queueNewOrderEmail',
				[$this->_order]
			)
		);
	}

	/**
	 * When queuing a new order email, if a new email cannot be sent, no emails
	 * should be queued to be sent.
	 */
	public function testQueueNewOrderEmailUnsendable()
	{
		$this->_order->method('getCanSendNewEmailFlag')
			->will($this->returnValue(false));
		$this->_order->expects($this->never())
			->method('queueNewOrderEmail');

		$this->assertSame(
			$this->_multishippingCheckout,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_multishippingCheckout,
				'_queueNewOrderEmail',
				[$this->_order]
			)
		);
	}

	/**
	 * When queuing a new order email, any errors encountered queuing the
	 * new order email should be ignored.
	 */
	public function testQueueNewOrderEmailExceptions()
	{
		$this->_order->method('getCanSendNewEmailFlag')
			->will($this->returnValue(true));
		// queueNewOrderEmail may throw some form of exception - unclear which
		// exactly but all will be caught, logged and ignored.
		$this->_order->expects($this->once())
			->method('queueNewOrderEmail')
			->will($this->throwException(new Exception));

		$this->assertSame(
			$this->_multishippingCheckout,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_multishippingCheckout,
				'_queueNewOrderEmail',
				[$this->_order]
			)
		);
	}

	/**
	 * After an order has been placed, surrounding Magento system should be
	 * made aware of the new order - session data should be updated and an
	 * event should be dispatched.
	 */
	public function testSignalOrderSuccess()
	{
		// Setup quote and orders with relevant data.
		$orderId = 1;
		$incrementId = '005012345';
		$quoteId = 3;
		$this->_order->method('getId')->will($this->returnValue($orderId));
		$this->_order->method('getIncrementId')->will($this->returnValue($incrementId));
		$this->_quote->method('getId')->will($this->returnValue($quoteId));

		// Side-effect tests: ensure session data is updated with quote and
		// order data.
		$this->_coreSession->expects($this->once())
			->method('setOrderIds')
			->with($this->identicalTo([$orderId => $incrementId]))
			->will($this->returnSelf());
		$this->_checkoutSession->expects($this->once())
			->method('setLastQuoteId')
			->with($this->identicalTo($quoteId))
			->will($this->returnSelf());

		$this->assertSame(
			$this->_multishippingCheckout,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_multishippingCheckout,
				'_signalOrderSuccess',
				[$this->_order]
			)
		);
		// Ensure the event was dispatched once for the new order.
		$this->assertEventDispatchedExactly('checkout_submit_all_after', 1);
	}
}
