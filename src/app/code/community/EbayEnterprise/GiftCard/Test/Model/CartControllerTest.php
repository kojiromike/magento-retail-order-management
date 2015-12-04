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

require_once 'EbayEnterprise/GiftCard/controllers/CartController.php';

class EbayEnterprise_GiftCard_Test_Model_CartControllerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const REDIRECT_PATH = EbayEnterprise_GiftCard_CartController::REDIRECT_PATH;

    protected $container;
    protected $giftCard;
    protected $helper;
    protected $controller;
    protected $giftCardNumber = 'somecardnumber';
    protected $giftCardPin = '12345678';
    protected $checkoutSession;

    // prepare mocks
    public function setUp()
    {
        $this->helper = $this->getHelperMock('ebayenterprise_giftcard/data');
        $this->helper->expects($this->any())
            ->method('__')->with($this->isType('string'))->will($this->returnArgument(0));
        // disable constructor to prevent having to mock dependencies
        $this->checkoutSession = $this->getModelMockBuilder('checkout/session')->disableOriginalConstructor()
            ->setMethods(null)->getMock();
        $this->giftCard = $this->getMock('EbayEnterprise_GiftCard_Model_IGiftcard');
        $this->giftCard->expects($this->any())
            ->method('setPin')->with($this->isType('string'))->will($this->returnSelf());
        // disable constructor to avoid mocking dependencies
        $this->container = $this->getMockBuilder('EbayEnterprise_GiftCard_Model_IContainer')->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->any())
            ->method('getGiftCard')->will($this->returnValue($this->giftCard));
        // disable constructor to avoid mocking dependencies
        $this->controller = $this->getMockBuilder('EbayEnterprise_GiftCard_CartController')->disableOriginalConstructor()
            ->setMethods(['_redirect', '_rewrite', 'setFlag', '_getCardInfoFromRequest'])->getMock();
        $this->controller->expects($this->once())
            ->method('_getCardInfoFromRequest')->will($this->returnValue([$this->giftCardNumber, $this->giftCardPin]));
        // inject mocked dependencies
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->controller, '_container', $this->container);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->controller, '_helper', $this->helper);
    }

    /**
     * verify:
     *   - controller uses account number and pin to get a giftcard instance to work with.
     *   - controller will call the check balance function only once
     *   - controller will add the giftCard back to the container on success.
     *   - controller will add a translated success message to the session.
     *   - controller will attempt to redirect back to cart page when done.
     */
    public function testAddAction()
    {
        $this->giftCard->expects($this->once())
            ->method('setPin')
            ->with($this->identicalTo($this->giftCardPin))
            ->will($this->returnSelf());
        $this->helper
            ->expects($this->once())
            ->method('addGiftCardToOrder')
            ->with($this->identicalTo($this->giftCard), $this->identicalTo($this->container))
            ->will($this->returnSelf());
        $this->controller
            ->expects($this->once())
            ->method('_redirect')
            ->with($this->identicalTo(self::REDIRECT_PATH))
            ->willReturnSelf();

        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->checkoutSession);
        $this->controller->addAction();

        // Successfully adding gift card to order should result in success message
        // being added to checkout session.
        $this->assertCount(1, $this->checkoutSession->getMessages()->getItemsByType(Mage_Core_Model_Message::SUCCESS));
    }

    /**
     * verify:
     * - when gift card cannot be added to the order, an error message is added
     *   to the checkout session
     */
    public function testAddFail()
    {
        $this->giftCard->expects($this->once())
            ->method('setPin')
            ->with($this->identicalTo($this->giftCardPin))
            ->will($this->returnSelf());
        $this->helper
            ->expects($this->once())
            ->method('addGiftCardToOrder')
            ->with($this->identicalTo($this->giftCard), $this->identicalTo($this->container))
            ->willThrowException(new EbayEnterprise_GiftCard_Exception('TEST EXCEPTION: ' . __METHOD__));
        $this->controller
            ->expects($this->once())
            ->method('_redirect')
            ->with($this->identicalTo(self::REDIRECT_PATH))
            ->will($this->returnSelf());

        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->checkoutSession);
        $this->controller->addAction();

        // Failure to add gift card to the order should result in an error message
        // being added to the checkout session.
        $this->assertCount(1, $this->checkoutSession->getMessages()->getErrors());
    }

    /**
     * verify:
     *   - controller uses account number and pin to get a giftcard instance to work with.
     *   - controller will use the container to remove the gift card.
     *   - controller will attempt to redirect back to cart page when done.
     */
    public function testRemoveAction()
    {
        $this->container->expects($this->once())
            ->method('removeGiftCard')->with($this->isInstanceOf('EbayEnterprise_GiftCard_Model_IGiftcard'))
            ->will($this->returnSelf());
        $this->controller->expects($this->once())
            ->method('_redirect')->with($this->identicalTo(self::REDIRECT_PATH))->will($this->returnSelf());

        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->checkoutSession);
        $this->controller->removeAction();
    }
}
