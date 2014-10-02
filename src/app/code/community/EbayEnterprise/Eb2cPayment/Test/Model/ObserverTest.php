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

class EbayEnterprise_Eb2cPayment_Test_Model_ObserverTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_observer;
	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 * @return void
	 */
	public function getMockCheckoutSessionModel()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('addSuccess', 'addError', 'addException', 'getQuoteId'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('addSuccess')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addError')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addException')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('getQuoteId')
			->will($this->returnValue(1));
		return $sessionMock;
	}

	public function providerRedeemGiftCard()
	{
		$quote = Mage::getModel('sales/quote', array(
			'gift_cards' => serialize(array(array(
				'a' => 50.00, 'ba' => 150.00, 'c' => '4111111ak4idq1111', 'i' => 1,
				'pan' => '4111111ak4idq1111', 'pin' => '5344',
			)))
		));
		$order = Mage::getModel('sales/order', array(
			'increment_id' => '10000101010',
			'gift_cards' => serialize(array(array(
				'a' => 50.00, 'ba' => 150.00, 'c' => '4111111ak4idq1111', 'i' => 1,
				'pan' => '4111111ak4idq1111', 'pin' => '5344',
			))),
		));
		$observer = new Varien_Event_Observer(
			array('event' => new Varien_Event(
				array('quote' => $quote, 'order' => $order)
			))
		);
		return array(
			array($observer)
		);
	}
	/**
	 * testing redeeming gift card observer method - successful redeem response
	 *
	 * @dataProvider providerRedeemGiftCard
	 */
	public function testRedeemGiftCard($observer)
	{
		// mocked data
		$panToken = 'PAN_ACCT_UNIQUE_ID';
		$responseCode = 'SUCCESS';

		$redeemMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			// Constructor requires an order and card data which are not needed for
			// mocking the methods used
			->disableOriginalConstructor()
			->setMethods(array('redeemGiftCard', 'getResponseCode', 'getPaymentAccountUniqueId'))
			->getMock();
		// make sure the redeemGiftCard method is called to make the service call
		// and do the SVC redeem
		$redeemMock->expects($this->once())
			->method('redeemGiftCard')
			// these values all come from data setup in the provider
			->will($this->returnSelf());
		$redeemMock->expects($this->any())
			->method('getResponseCode')
			->will($this->returnValue($responseCode));
		$redeemMock->expects($this->any())
			->method('getPaymentAccountUniqueId')
			->will($this->returnValue($panToken));
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem', $redeemMock);

		Mage::getModel('eb2cpayment/observer', array('checkout_session' => $this->getMockCheckoutSessionModel()))
			->redeemGiftCard($observer);

		// use getData to override mock.
		$allResultGiftCards = unserialize($observer->getEvent()->getQuote()->getGiftCards());
		$firstResultGiftCard = array_shift($allResultGiftCards);
		$this->assertSame($panToken, $firstResultGiftCard['panToken']);
	}
	/**
	 * testing redeemGiftCard unsuccessful redeem response
	 *
	 * @dataProvider providerRedeemGiftCard
	 * @expectedException Mage_Core_Exception
	 */
	public function testRedeemGiftCardFailReponse($observer)
	{
		$redeemMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			// constructor needs an order and card data, not needed to mock the methods
			// used in this case
			->disableOriginalConstructor()
			->setMethods(array('redeemGiftCard', 'getResponseCode'))
			->getMock();

		$redeemMock->expects($this->once())
			->method('redeemGiftCard')
			->will($this->returnSelf());
		$redeemMock->expects($this->any())
			->method('getResponseCode')
			// must match the failure code expected by the observer
			->will($this->returnValue('FAIL'));
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem', $redeemMock);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByCode', 'removeFromCart'))
			->getMock();

		$giftCardAccountMock->expects($this->once())
			->method('loadByCode')
			->will($this->returnSelf());
		$giftCardAccountMock->expects($this->once())
			->method('removeFromCart')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $giftCardAccountMock);

		Mage::getModel('eb2cpayment/observer', array('checkout_session' => $this->getMockCheckoutSessionModel()))
			->redeemGiftCard($observer);
	}
	/**
	 * Provide response data from the redeemvoid service calls and whether the
	 * response should be considered a success (true) or a failure (false).
	 * @return array
	 */
	public function provideRedeemVoidFailureData()
	{
		return array(
			array(array(), false),
			array(array('responseCode' => 'Fail'), false),
			array(array('responseCode' => 'Success'), true),
		);
	}
	/**
	 * Test scenarios of the SVC void request. When the request fails,
	 * a warning should be logged. When it succeeds, nothing should happen.
	 * @param  array $response Response data
	 * @param  bool $isSuccess Was the request successful
	 * @dataProvider provideRedeemVoidFailureData
	 */
	public function testRedeemVoidGiftCard($response, $isSuccess)
	{
		$pan = '123456';
		$pin = '4321';
		$usedAmt = 50.00;
		$orderId = '012340012';
		// set up a order to use
		$order = Mage::getModel('sales/order', array(
			'gift_cards' => serialize(array(array('pan' => $pan, 'pin' => $pin, 'ba' => $usedAmt))),
			'increment_id' => $orderId,
		));

		$voidRequest = $this->getModelMock(
			'eb2cpayment/storedvalue_redeem_void',
			array('voidCardRedemption')
		);
		$voidRequest->expects($this->once())
			->method('voidCardRedemption')
			->with($this->identicalTo($pan), $this->identicalTo($pin), $this->identicalTo($orderId), $this->identicalTo($usedAmt))
			->will($this->returnValue($response));
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem_void', $voidRequest);

		// when the request fails, make sure a WARN message is logged
		$logger = $this->getHelperMock('ebayenterprise_magelog/data', array('logWarn'));
		if (!$isSuccess) {
			$logger->expects($this->once())
				->method('logWarn')
				->will($this->returnSelf());
		}
		$eventObserver = new Varien_Event_Observer(array('event' => new Varien_Event(
			array('order' => $order, 'quote' => Mage::getModel('sales/quote'))
		)));
		Mage::getModel('eb2cpayment/observer', array('log' => $logger, 'checkout_session' => $this->getMockCheckoutSessionModel()))
			->redeemVoidGiftCard($eventObserver);
	}
	/**
	 * Test that when the quote contains invalid gift card data, no attempt to
	 * void the data is made.
	 */
	public function testRedeemVoidInvalidGiftCardData()
	{
		$quote = Mage::getModel(
			'sales/quote',
			// throw some junk data in the gift cards field
			array('gift_cards' => serialize('this is not the data you are looking for'))
		);
		$voidRequest = $this->getModelMock(
			'eb2cpayment/storedvalue_redeem_void',
			array('voidCardRedemption')
		);
		$voidRequest->expects($this->never())
			->method('voidCardRedemption');
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem_void', $voidRequest);

		$eventObserver = new Varien_Event_Observer(
			array('event' => new Varien_Event(
				array('quote' => $quote, 'order' => Mage::getModel('sales/order'))
			))
		);
		Mage::getModel('eb2cpayment/observer', array('checkout_session' => $this->getMockCheckoutSessionModel()))
			->redeemVoidGiftCard($eventObserver);
	}
	/**
	 * Test voiding order payments when the order create fails.
	 * @param bool $canVoid Can the payment be voided
	 * @dataProvider provideTrueFalse
	 */
	public function testVoidPayments($canVoid)
	{
		$observer = Mage::getModel('eb2cpayment/observer', array('checkout_session' => $this->getMockCheckoutSessionModel()));
		$order = $this->getModelMock('sales/order', array('getPayment', 'canVoidPayment'));
		$payment = $this->getModelMock('payment/method_abstract', array('void'));
		$order->expects($this->any())
			->method('getPayment')
			->will($this->returnValue($payment));
		$order->expects($this->once())
			->method('canVoidPayment')
			->will($this->returnValue($canVoid));

		if ($canVoid) {
			$payment->expects($this->once())
				->method('void')
				->with($this->identicalTo($payment))
				->will($this->returnSelf());
		} else {
			$payment->expects($this->never())
				->method('void');
		}

		$observer->voidPayments(new Varien_Event_Observer(
			array('event' => new Varien_Event(array('order' => $order)))
		));
	}
	/**
	 * verify applyExpressPaymentAction is called with the correct parameters
	 * @covers EbayEnterprise_Eb2cPayment_Model_Observer::configurePayPalPaymentAction
	 */
	public function testConfigurePayPalPaymentAction()
	{
		$store = 'a store code or null';
		$website = 'a website code or null';
		// prevent the need to mock unnecessary dependencies
		$observer = $this->getModelMockBuilder('eb2cpayment/observer')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$observerObj = $this->_buildEventObserver(array('store' => $store, 'website' => $website));
		$adminConfig = $this->getModelMock('eb2cpayment/paypal_adminhtml_config', array('applyExpressPaymentAction'));
		$adminConfig->expects($this->once())
			->method('applyExpressPaymentAction')
			->with($this->identicalTo($store), $this->identicalTo($website))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/paypal_adminhtml_config', $adminConfig);
		$observer->configurePayPalPaymentAction($observerObj);
	}
}
