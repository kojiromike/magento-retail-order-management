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

    protected $_container;
    protected $_giftCard;
    protected $_helper;
    protected $_controller;
    protected $_giftCardNumber = 'somecardnumber';
    protected $_giftCardPin = '12345678';
    protected $_checkoutSession;

    // prepare mocks
    public function setUp()
    {
        $this->_helper = $this->getHelperMock('ebayenterprise_giftcard/data');
        $this->_helper->expects($this->any())
            ->method('__')->with($this->isType('string'))->will($this->returnArgument(0));
        // disable constructor to prevent having to mock dependencies
        $this->_checkoutSession = $this->getModelMockBuilder('checkout/session')->disableOriginalConstructor()
            ->setMethods(array('addError', 'setEbayEnterpriseCurrentGiftCard'))->getMock();
        $this->_giftCard = $this->getMock('EbayEnterprise_GiftCard_Model_IGiftcard');
        $this->_giftCard->expects($this->any())
            ->method('setPin')->with($this->isType('string'))->will($this->returnSelf());
        // disable constructor to avoid mocking dependencies
        $this->_container = $this->getMockBuilder('EbayEnterprise_GiftCard_Model_IContainer')->disableOriginalConstructor()
            ->getMock();
        $this->_container->expects($this->any())
            ->method('getGiftCard')->will($this->returnValue($this->_giftCard));
        // disable constructor to avoid mocking dependencies
        $this->_controller = $this->getMockBuilder('EbayEnterprise_GiftCard_CartController')->disableOriginalConstructor()
            ->setMethods(array('_redirect', '_rewrite', 'setFlag', '_getCardInfoFromRequest'))->getMock();
        $this->_controller->expects($this->once())
            ->method('_getCardInfoFromRequest')->will($this->returnValue(array($this->_giftCardNumber, $this->_giftCardPin)));
        // inject mocked dependencies
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_controller, '_container', $this->_container);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_controller, '_helper', $this->_helper);
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
        $this->_giftCard->expects($this->once())
            ->method('setPin')->with($this->identicalTo($this->_giftCardPin))->will($this->returnSelf());
        $this->_helper->expects($this->once())
            ->method('addGiftCardToOrder')->with($this->identicalTo($this->_giftCard))->will($this->returnSelf());
        $this->_controller->expects($this->once())
            ->method('_redirect')->with($this->identicalTo(self::REDIRECT_PATH))->will($this->returnSelf());

        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->_checkoutSession);
        $this->_controller->addAction();
    }

    /**
     * verify:
     *   - controller uses account number and pin to get a giftcard instance to work with.
     *   - controller will use the container to remove the gift card.
     *   - controller will attempt to redirect back to cart page when done.
     */
    public function testRemoveAction()
    {
        $this->_container->expects($this->once())
            ->method('removeGiftCard')->with($this->isInstanceOf('EbayEnterprise_GiftCard_Model_IGiftcard'))
            ->will($this->returnSelf());
        $this->_controller->expects($this->once())
            ->method('_redirect')->with($this->identicalTo(self::REDIRECT_PATH))->will($this->returnSelf());

        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->_checkoutSession);
        $this->_controller->removeAction();
    }
}
