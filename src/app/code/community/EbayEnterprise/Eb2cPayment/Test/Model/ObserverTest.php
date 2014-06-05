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
	public function replaceByMockCheckoutSessionModel()
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
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
	}

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_observer = Mage::getModel('eb2cpayment/observer');
		$this->replaceByMockCheckoutSessionModel();
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
	 * testing redeeming gifcard observer method - successful redeem response
	 *
	 * @test
	 * @dataProvider providerRedeemGiftCard
	 */
	public function testRedeemGiftCard($observer)
	{
		$redeemMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->setMethods(array('getRedeem', 'parseResponse'))
			->getMock();
		$redeemReply = '<foo></foo>';
		$redeemMock->expects($this->any())
			->method('getRedeem')
			// these values all come from data setup in the provider
			->with($this->identicalTo('4111111ak4idq1111'), $this->identicalTo('5344'), $this->identicalTo('10000101010'))
			->will($this->returnValue($redeemReply));
		$expectedPanToken = 'panToken123';
		$redeemMock->expects($this->any())
			->method('parseResponse')
			->with($this->identicalTo($redeemReply))
			->will($this->returnValue(array('responseCode' => 'Success', 'pan' => '4111111ak4idq1111', 'pin' => '5344', 'paymentAccountUniqueId' => $expectedPanToken)));
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem', $redeemMock);

		$this->_observer->redeemGiftCard($observer);

		// use getData to override mock.
		$allResultGiftCards = unserialize($observer->getEvent()->getQuote()->getGiftCards());
		$firstResultGiftCard = array_shift($allResultGiftCards);
		$this->assertSame($expectedPanToken, $firstResultGiftCard['panToken']);
	}
	/**
	 * testing redeemGiftCard unsucessful redeem response
	 *
	 * @test
	 * @dataProvider providerRedeemGiftCard
	 * @expectedException Mage_Core_Exception
	 */
	public function testRedeemGiftCardFailReponse($observer)
	{
		$redeemMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->setMethods(array('getRedeem', 'parseResponse'))
			->getMock();

		$redeemMock->expects($this->any())
			->method('getRedeem')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'fail', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem', $redeemMock);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByPanPin', 'removeFromCart'))
			->getMock();

		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('removeFromCart')
			->will($this->returnSelf()
			);

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $giftCardAccountMock);

		$this->assertNull(
			$this->_observer->redeemGiftCard($observer)
		);
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
	 * @param  boolean $isSuccess Was the request successful
	 * @test
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
		if (!$isSuccess) {
			$logger = $this->getHelperMock('ebayenterprise_magelog/data', array('logWarn'));
			$logger->expects($this->once())
				->method('logWarn')
				->will($this->returnSelf());
			$this->replaceByMock('helper', 'ebayenterprise_magelog', $logger);
		}

		Mage::getSingleton('eb2cpayment/observer')->redeemVoidGiftCard(
			new Varien_Event_Observer(
				array('event' => new Varien_Event(
					array('order' => $order, 'quote' => Mage::getModel('sales/quote'))
				))
			)
		);
	}
	/**
	 * Test that when the quote contains invalid gift card data, no attempt to
	 * void the data is made.
	 * @test
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

		Mage::getSingleton('eb2cpayment/observer')->redeemVoidGiftCard(
			new Varien_Event_Observer(
				array('event' => new Varien_Event(
					array('quote' => $quote, 'order' => Mage::getModel('sales/order'))
				))
			)
		);
	}
	/**
	 * Test EbayEnterprise_Eb2cPayment_Model_Observer::suppressPaymentModule method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cPayment_Model_Observer::suppressPaymentModule will be invoked by this
	 *                test given a mocked Varien_Event_Observer object in which the method
	 *                Varien_Event_Observer::getEvent will be invoked and return a mocked Varien_Event object
	 *                in which the mehods Varien_Event::getStore, getWebsite will be invoked once and return null
	 *                so that the methods EbayEnterprise_Eb2cCore_Helper_Data::getDefaultStore, and getDefaultWebsite will
	 *                will return mocked Mage_Core_Model_Store and Mage_Core_Model_Website respectively
	 * Expectation 2: the method EbayEnterprise_Eb2cPayment_Helper_Data::getConfigModel will be invoked given the mocked
	 *                Mage_Core_Model_Store object in which it will return the mocked EbayEnterprise_Eb2cCore_Model_Config_Registry
	 *                object with the magic property 'isPaymentEnabled' set to true, which will allowed the following methods
	 *                to be invoked EbayEnterprise_Eb2cPayment_Model_Suppression::disableNonEb2cPaymentMethods, and saveEb2cPaymentMethods
	 *                given the value 1
	 */
	public function testSuppressPaymentModule()
	{
		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getStore', 'getWebsite'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getStore')
			->will($this->returnValue(null));
		$eventMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue(null));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultStore', 'getDefaultWebsite'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getDefaultStore')
			->will($this->returnValue($storeMock));
		$coreHelperMock->expects($this->once())
			->method('getDefaultWebsite')
			->will($this->returnValue($websiteMock));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo($storeMock))
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'isPaymentEnabled' => true
			))));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$suppressionMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->disableOriginalConstructor()
			->setMethods(array('disableNonEb2cPaymentMethods', 'saveEb2cPaymentMethods'))
			->getMock();
		$suppressionMock->expects($this->once())
			->method('disableNonEb2cPaymentMethods')
			->will($this->returnSelf());
		$suppressionMock->expects($this->once())
			->method('saveEb2cPaymentMethods')
			->with($this->identicalTo(1))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppressionMock);

		$oMock = $this->getModelMockBuilder('eb2cpayment/observer')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($oMock, $oMock->suppressPaymentModule($observerMock));
	}

	/**
	 * @see self::testSuppressPaymentModule except this time we are testing when the payment is disabled
	 *      and when the Varien_Event::getstore, and getWebsite methods return a mock Mage_Core_Model_Store object
	 *      and a mock Mage_Core_Model_Website object respectively.
	 */
	public function testSuppressPaymentModuleWhenPaymentIsDisabled()
	{
		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getStore', 'getWebsite'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getStore')
			->will($this->returnValue($storeMock));
		$eventMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue($websiteMock));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo($storeMock))
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'isPaymentEnabled' => false
			))));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$suppressionMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->disableOriginalConstructor()
			->setMethods(array('disableNonEb2cPaymentMethods', 'saveEb2cPaymentMethods'))
			->getMock();
		$suppressionMock->expects($this->once())
			->method('disableNonEb2cPaymentMethods')
			->will($this->returnSelf());
		$suppressionMock->expects($this->once())
			->method('saveEb2cPaymentMethods')
			->with($this->identicalTo(0))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppressionMock);

		$oMock = $this->getModelMockBuilder('eb2cpayment/observer')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($oMock, $oMock->suppressPaymentModule($observerMock));
	}
	/**
	 * Test voiding order payments when the order create fails.
	 * @param  PHPUnit_Framework_MockObject_Stub $voidResult Stub results of the void call
	 * @test
	 * @dataProvider provideTrueFalse
	 */
	public function testVoidPayments($canVoid)
	{
		$observer = Mage::getSingleton('eb2cpayment/observer');
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
}
