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
        $this->_eventObserver = Mage::getModel('Varien_Event_Observer', array('event' => $this->_event));

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
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $this->_replaceSession('checkout/session');

        $orderId = '5555555555';
        $container = Mage::getModel('ebayenterprise_giftcard/container');
        // set up a mock, unredeemed gift card - just want to ensure it is redeemed
        $cardToRedeem = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('redeem'))
            ->setCardNumber('1234')
            ->setIsRedeemed(false)
            ->setAmountRedeemed(5.00)
            ->setAmountToRedeem(5.00);
        $cardToRedeem->expects($this->once())->method('redeem')->will($this->returnSelf());
        $container->updateGiftCard($cardToRedeem);

        $order = Mage::getModel('sales/order', array('increment_id' => $orderId));

        $this->_event->setOrder($order);
        Mage::getModel('ebayenterprise_giftcard/observer')->redeemGiftCards($this->_eventObserver);
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
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('redeem'));
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem($redeemAmount);

        $observer = Mage::getModel('ebayenterprise_giftcard/observer');
        $this->assertSame(5.00, EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', array($card)));
    }
    /**
     * Network errors should result in an exception but no change to gift cards.
     */
    public function testRedeemCardFailNetwork()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', array('getQuote'));
        $quote = $this->getModelMock('sales/quote', array('collectTotals', 'setTotalsCollectedFlag'));
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
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('redeem'));
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem($redeemAmount);
        $card->expects($this->any())
            ->method('redeem')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception_Network_Exception));

        $observer = Mage::getModel('ebayenterprise_giftcard/observer');
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', array($card));
    }
    /**
     * When the redeem fails with an EbayEnterprise_GiftCard_Exception, the card
     * cannot be redeemed and should be removed from the order.
     */
    public function testRedeemCardFailUnredeemableCard()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', array('getQuote'));
        $quote = $this->getModelMock('sales/quote', array('collectTotals', 'setTotalsCollectedFlag'));
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
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('redeem'));
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem($redeemAmount);
        $card->expects($this->any())
            ->method('redeem')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception));

        $container = $this->getModelMock('ebayenterprise_giftcard/container', array('removeGiftCard'));
        // ensure the card gets removed from the container
        $container->expects($this->once())->method('removeGiftCard')->with($this->identicalTo($card))->will($this->returnSelf());

        $observer = Mage::getModel('ebayenterprise_giftcard/observer', array('gift_card_container' => $container));
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', array($card));
    }
    /**
     * If a card is successfully redeemed but the amount redeemed does not match
     * what was expected to be redeemed, an execption should be thrown
     */
    public function testRedeemCardFailIncorrectAmountRedeemed()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', array('getQuote'));
        $quote = $this->getModelMock('sales/quote', array('collectTotals', 'setTotalsCollectedFlag'));
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
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('redeem'));
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem(10.00)
            ->setBalanceAmount(0.00);

        $observer = Mage::getModel('ebayenterprise_giftcard/observer');
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', array($card));
    }
    /**
     * If a card was successfully redeemed - request was a success - but the card
     * does not actually have a balance to redeem - amount redeemed + balance = 0,
     * the card can no longer be used and should be removed from the container.
     */
    public function testRedeemCardFailEmptyGiftCard()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $session = $this->_replaceSession('checkout/session', array('getQuote'));
        $quote = $this->getModelMock('sales/quote', array('collectTotals', 'setTotalsCollectedFlag'));
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
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('redeem'));
        $card->setAmountRedeemed($redeemAmount)
            ->setAmountToRedeem(10.00)
            ->setBalanceAmount(0.00);

        $container = $this->getModelMock('ebayenterprise_giftcard/container', array('removeGiftCard'));
        // ensure the card gets removed from the container
        $container->expects($this->once())->method('removeGiftCard')->with($this->identicalTo($card))->will($this->returnSelf());

        $observer = Mage::getModel('ebayenterprise_giftcard/observer', array('gift_card_container' => $container));
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_redeemCard', array($card));
    }
    /**
     * Test voiding the redeemed gift cards.
     */
    public function testRedeemVoid()
    {
        // need to replace the checkout session to prevent errors when instantiating the gift card container
        $this->_replaceSession('checkout/session');

        $container = Mage::getModel('ebayenterprise_giftcard/container');

        $exceptionGiftCard = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('void'));
        $exceptionGiftCard->setIsRedeemed(true)->setCardNumber('2222222222222222');
        // the void request may fail but processing should continue
        $exceptionGiftCard->expects($this->once())
            ->method('void')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception));
        $container->updateGiftCard($exceptionGiftCard);

        $giftCard = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('void'));
        $giftCard->setIsRedeemed(true)->setCardNumber('1111111111111111');
        // make sure the card redeem is voided
        $giftCard->expects($this->once())
            ->method('void')
            ->will($this->returnSelf());
        $container->updateGiftCard($giftCard);

        $unredeemedGiftCard = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('void'));
        $unredeemedGiftCard->setIsRedeemed(false)->setCardNumber('2222222222222222');
        // cards that were not redeemed (getIsRedeemed === false) should not be voided
        $unredeemedGiftCard->expects($this->never())
            ->method('void')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception));
        $container->updateGiftCard($unredeemedGiftCard);

        $quote = Mage::getModel('sales/quote');
        $order = Mage::getModel('sales/order');
        $this->_event->setQuote($quote)->setOrder($order);
        $observer = Mage::getModel('ebayenterprise_giftcard/observer', array('gift_card_container' => $container));

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
