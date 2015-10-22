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

class EbayEnterprise_GiftCard_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var Varien_Event */
    protected $_event;
    /** @var Varien_Event_Observer Contains self::$_event as the event data */
    protected $_eventObserver;
    /**
     * Set up reusable objects for use in tests. Any dependent systems that depend
     * upon the session - e.g. gift card container, gift card observer - cannot
     * be instantiated until the session has been mocked, so not in setUp.
     */
    public function setUp()
    {
        $this->_event = Mage::getModel('Varien_Event');
        $this->_eventObserver = Mage::getModel('Varien_Event_Observer', ['event' => $this->_event]);

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    /**
     * Test redeeming gift cards that have been applied to an order - expecetd to
     * cover full total of order.
     */
    public function testRedeemCard()
    {
        $this->getModelMockBuilder('core/session_abstract')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        // set up a mock, unredeemed gift card - just want to ensure it is redeemed
        $cardToRedeem = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['redeem'])
            ->setAmountRedeemed(5.00)
            ->setAmountToRedeem(5.00);
        $cardToRedeem->expects($this->once())->method('redeem')->will($this->returnSelf());

        // Set up an object storage of unredeemed cards, will include the card
        // that is expected to be redeemed.
        $unredeemedCards = new SplObjectStorage;
        $unredeemedCards->attach($cardToRedeem);

        $orderId = '5555555555';
        $container = $this->getModelMock('ebayenterprise_giftcard/container', ['getUnredeemedGiftCards']);
        $container->method('getUnredeemedGiftCards')
            ->will($this->returnValue($unredeemedCards));

        $order = Mage::getModel('sales/order', ['increment_id' => $orderId]);

        $this->_event->setOrder($order);
        Mage::getModel('ebayenterprise_giftcard/observer', ['gift_card_container' => $container])->redeemGiftCards($this->_eventObserver);
        $this->assertSame($orderId, $cardToRedeem->getOrderId());
    }
    /**
     * Test successfully redeeming a gift card. Should ensure the card has the given
     * order id set to it. Method should return the amount redeemed from the card.
     * @return [type] [description]
     */
    public function testRedeemCardSuccess()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $this->_replaceSession('checkout/session');

        $redeemAmount = 5.00;
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['redeem']);
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem($redeemAmount);

        $observer = Mage::getModel('ebayenterprise_giftcard/observer');
        $this->assertSame(5.00, EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', [$card]));
    }
    /**
     * Network errors should result in an exception but no change to gift cards.
     */
    public function testRedeemCardFailNetwork()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', ['getQuote']);
        $quote = $this->getModelMock('sales/quote', ['collectTotals', 'setTotalsCollectedFlag']);
        $quote->expects($this->once())
            ->method('setTotalsCollectedFlag')
            ->with($this->isFalse())
            ->will($this->returnSelf());
        $quote->expects($this->once())
            ->method('collectTotals')
            ->will($this->returnSelf());
        $session->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $redeemAmount = 5.00;
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['redeem']);
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem($redeemAmount);
        $card->expects($this->any())
            ->method('redeem')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception_Network_Exception));

        $observer = Mage::getModel('ebayenterprise_giftcard/observer');
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', [$card]);
    }
    /**
     * When the redeem fails with an EbayEnterprise_GiftCard_Exception, the card
     * cannot be redeemed and should be removed from the order.
     */
    public function testRedeemCardFailUnredeemableCard()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', ['getQuote']);
        $quote = $this->getModelMock('sales/quote', ['collectTotals', 'setTotalsCollectedFlag']);
        $quote->expects($this->once())
            ->method('setTotalsCollectedFlag')
            ->with($this->isFalse())
            ->will($this->returnSelf());
        $quote->expects($this->once())
            ->method('collectTotals')
            ->will($this->returnSelf());
        $session->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $redeemAmount = 5.00;
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['redeem']);
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem($redeemAmount);
        $card->expects($this->any())
            ->method('redeem')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception));

        $container = $this->getModelMock('ebayenterprise_giftcard/container', ['removeGiftCard']);
        // ensure the card gets removed from the container
        $container->expects($this->once())->method('removeGiftCard')->with($this->identicalTo($card))->will($this->returnSelf());

        $observer = Mage::getModel('ebayenterprise_giftcard/observer', ['gift_card_container' => $container]);
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', [$card]);
    }
    /**
     * If a card is successfully redeemed but the amount redeemed does not match
     * what was expected to be redeemed, an execption should be thrown
     */
    public function testRedeemCardFailIncorrectAmountRedeemed()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', ['getQuote']);
        $quote = $this->getModelMock('sales/quote', ['collectTotals', 'setTotalsCollectedFlag']);
        $quote->expects($this->once())
            ->method('setTotalsCollectedFlag')
            ->with($this->isFalse())
            ->will($this->returnSelf());
        $quote->expects($this->once())
            ->method('collectTotals')
            ->will($this->returnSelf());
        $session->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $redeemAmount = 5.00;
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['redeem']);
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem(10.00)
            ->setBalanceAmount(0.00);

        $observer = Mage::getModel('ebayenterprise_giftcard/observer');
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', [$card]);
    }
    /**
     * If a card was successfully redeemed - request was a success - but the card
     * does not actually have a balance to redeem - amount redeemed + balance = 0,
     * the card can no longer be used and should be removed from the container.
     */
    public function testRedeemCardFailEmptyGiftCard()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', ['getQuote']);
        $quote = $this->getModelMock('sales/quote', ['collectTotals', 'setTotalsCollectedFlag']);
        $quote->expects($this->once())
            ->method('setTotalsCollectedFlag')
            ->with($this->isFalse())
            ->will($this->returnSelf());
        $quote->expects($this->once())
            ->method('collectTotals')
            ->will($this->returnSelf());
        $session->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $redeemAmount = 0.00;
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['redeem']);
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem(10.00)
            ->setBalanceAmount(0.00);

        $container = $this->getModelMock('ebayenterprise_giftcard/container', ['removeGiftCard']);
        // ensure the card gets removed from the container
        $container->expects($this->once())->method('removeGiftCard')->with($this->identicalTo($card))->will($this->returnSelf());

        $observer = Mage::getModel('ebayenterprise_giftcard/observer', ['gift_card_container' => $container]);
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', [$card]);
    }
    /**
     * Test voiding the redeemed gift cards.
     */
    public function testRedeemVoid()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $this->_replaceSession('checkout/session');

        $exceptionGiftCard = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['void']);
        $exceptionGiftCard->setIsRedeemed(true)->setCardNumber('2222222222222222');
        // the void request may fail but processing should continue
        $exceptionGiftCard->expects($this->once())
            ->method('void')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception(__METHOD__ . ': test exception')));

        $giftCard = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['void']);
        $giftCard->setIsRedeemed(true)->setCardNumber('1111111111111111');
        // make sure the card redeem is voided
        $giftCard->expects($this->once())
            ->method('void')
            ->will($this->returnSelf());

        // The set of gift cards in the container that have been redeemed and
        // need to be voided.
        $redeemedGiftCards = new SplObjectStorage;
        $redeemedGiftCards->attach($giftCard);
        $redeemedGiftCards->attach($exceptionGiftCard);

        $container = $this->getModelMock('ebayenterprise_giftcard/container', ['getRedeemedGiftCards']);
        $container->method('getRedeemedGiftCards')->will($this->returnValue($redeemedGiftCards));

        $quote = Mage::getModel('sales/quote');
        $order = Mage::getModel('sales/order');
        $this->_event->setQuote($quote)->setOrder($order);
        $observer = Mage::getModel('ebayenterprise_giftcard/observer', ['gift_card_container' => $container]);

        // invoke the method, should void any redeemed card in the container
        $observer->redeemVoidGiftCards($this->_eventObserver);
    }
    /**
     * Validate expected event configuration.
     *
     * @dataProvider dataProvider
     */
    public function testEventSetup($area, $eventName, $observerClassAlias, $observerMethod)
    {
        $this->_testEventConfig($area, $eventName, $observerClassAlias, $observerMethod);
    }
}
