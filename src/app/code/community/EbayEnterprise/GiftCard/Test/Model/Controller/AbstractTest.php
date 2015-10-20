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

class EbayEnterprise_GiftCard_Test_Model_Controller_AbstractTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const CARDNUMBER_FIELD = EbayEnterprise_GiftCard_Controller_Abstract::CARDNUMBER_FIELD;
    const PIN_FIELD = EbayEnterprise_GiftCard_Controller_Abstract::PIN_FIELD;
    const REDIRECT_PATH = EbayEnterprise_GiftCard_Controller_Abstract::REDIRECT_PATH;
    const FLAG_NO_DISPATCH = Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH;

    protected $_container;
    protected $_giftCard;
    protected $_helper;
    protected $_controller;
    protected $_request;
    protected $_store;
    protected $_formBlock;
    protected $_getGiftCardValueMap = array();
    protected $_giftCardNumber = 'somecardnumber';
    protected $_giftCardPin = '12345678';
    protected $_checkoutSession;
    protected $_giftCardSession;

    // prepare mocks
    public function setUp()
    {
        $this->_helper = $this->getHelperMock('ebayenterprise_giftcard/data');
        $this->_helper->expects($this->any())
            ->method('__')->with($this->isType('string'))->will($this->returnArgument(0));
        // disable constructor to prevent having to mock dependencies
        $this->_checkoutSession = $this->getModelMockBuilder('checkout/session')->disableOriginalConstructor()
            ->setMethods(array('addError'))->getMock();
        // disable constructor to prevent having to mock dependencies
        $this->_giftCardSession = $this->getModelMockBuilder('ebayenterprise_giftcard/session')->disableOriginalConstructor()
            ->setMethods(array('setEbayEnterpriseCurrentGiftCard'))->getMock();
        // disable constructor to avoid mocking dependencies
        $this->_request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')->disableOriginalConstructor()->getMock();
        $this->_request->expects($this->any())
            ->method('getParam')->will($this->returnValueMap(array(
                array(self::CARDNUMBER_FIELD, '', $this->_giftCardNumber),
                array(self::PIN_FIELD, '', $this->_giftCardPin),
            )));
        $this->_giftCard = $this->getMock('EbayEnterprise_GiftCard_Model_IGiftcard');
        $this->_giftCard->expects($this->any())
            ->method('setPin')->with($this->isType('string'))->will($this->returnSelf());
        // disable constructor to avoid mocking dependencies
        $this->_container = $this->getMockBuilder('EbayEnterprise_GiftCard_Model_IContainer')->disableOriginalConstructor()
            ->getMock();
        // use value map so that if anything other than the giftcard number and pin are passed in,
        // the function will return null. so tests should fail if a change breaks assumptions about
        // getGiftCard's arguments.
        $this->_container->expects($this->any())
            ->method('getGiftCard')->will($this->returnValueMap(array(
                array($this->_giftCardNumber, $this->_giftCard),
            )));
        // disable constructor to avoid mocking dependencies
        $this->_controller = $this->getMockBuilder('EbayEnterprise_GiftCard_Controller_Abstract')->disableOriginalConstructor()
            ->setMethods(array('_redirect', 'loadLayout', 'renderLayout', 'setFlag'))->getMock();
        // inject mocked dependencies
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_controller, '_container', $this->_container);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_controller, '_helper', $this->_helper);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_controller, '_request', $this->_request);
    }

    /**
     * verify:
     *   - controller uses account number and pin to get a giftcard instance to work with.
     *   - controller will attempt a balance check only once.
     *   - controller will not store the giftcard in the session.
     *   - controller will add a translated error message to the session.
     *   - controller will attempt to redirect back to previous page when done.
     */
    public function testBalanceActionError()
    {
        $this->_request->expects($this->any())->method('isAjax')->will($this->returnValue(true));
        $this->_giftCard->expects($this->once())
            ->method('checkBalance')->will($this->throwException(Mage::exception('EbayEnterprise_GiftCard')));
        $this->_giftCardSession->expects($this->never())->method('setEbayEnterpriseCurrentGiftCard');
        $this->_checkoutSession->expects($this->once())
            ->method('addError')->with($this->isType('string'))->will($this->returnSelf());
        $this->_controller->expects($this->once())
            ->method('loadLayout')->will($this->returnSelf());
        $this->_controller->expects($this->once())
            ->method('renderLayout')->will($this->returnSelf());

        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->_checkoutSession);
        $this->replaceByMock('singleton', 'ebayenterprise_giftcard/session', $this->_giftCardSession);
        $this->_controller->balanceAction();
    }

    /**
     * verify:
     *   - controller uses account number and pin to get a giftcard instance to work with.
     *   - controller will attempt a balance check only once.
     *   - controller will store the giftcard data into the session.
     *   - controller will attempt to redirect back to previous page when done.
     */
    public function testBalanceAction()
    {
        $this->_request->expects($this->any())->method('isAjax')->will($this->returnValue(true));
        $this->_giftCard->expects($this->once())
            ->method('checkBalance')->will($this->returnSelf());
        $this->_giftCardSession->expects($this->once())
            ->method('setEbayEnterpriseCurrentGiftCard')->with($this->isInstanceOf('EbayEnterprise_GiftCard_Model_IGiftcard'))
            ->will($this->returnSelf());
        $this->_controller->expects($this->once())
            ->method('loadLayout')->will($this->returnSelf());
        $this->_controller->expects($this->once())
            ->method('renderLayout')->will($this->returnSelf());

        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->_checkoutSession);
        $this->replaceByMock('singleton', 'ebayenterprise_giftcard/session', $this->_giftCardSession);
        $this->_controller->balanceAction();
    }
    /**
     * When making balance check as a non-ajax request, should redirect instead
     * of just rendering the page
     */
    public function testBalanceActionNoAjax()
    {
        $this->_request->expects($this->any())->method('isAjax')->will($this->returnValue(false));
        $this->_giftCard->expects($this->any())
            ->method('checkBalance')->will($this->returnSelf());
        // make sure the layout is not rendered - should be redirecting instead
        $this->_controller->expects($this->never())->method('renderLayout');
        // make sure the redirect happens
        $this->_controller->expects($this->once())->method('_redirect')->will($this->returnSelf());
        // inject the session mock
        $this->replaceByMock('singleton', 'checkout/session', $this->_checkoutSession);
        $this->replaceByMock('singleton', 'ebayenterprise_giftcard/session', $this->_giftCardSession);
        $this->_controller->balanceAction();
    }
}
