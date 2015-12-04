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

/**
 * Unit tests for @see EbayEnterprise_GiftCard_Model_Adminhtml_Observer
 */
class EbayEnterprise_GiftCard_Test_Model_Adminhtml_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_GiftCard_Model_IContainer (mock) */
    protected $container;
    /** @var EbayEnterprise_GiftCard_Model_Giftcard (mock) */
    protected $giftCard;
    /** @var EbayEnterprise_GiftCard_Helper_Data (mock) */
    protected $helper;
    /** @var EbayEnterprise_GiftCard_Model_Adminhtml_Observer */
    protected $observer;

    public function setUp()
    {
        $this->container = $this->getMockForAbstractClass('EbayEnterprise_GiftCard_Model_IContainer');

        $this->giftCard = $this->getModelMock('ebayenterprise_giftcard/giftcard', ['setPin']);

        $this->helper = $this->getHelperMock('ebayenterprise_giftcard', ['addGiftCardToOrder', '__']);
        $this->helper->method('__')->willReturnArgument(0);

        $this->session = $this->getModelMockBuilder('adminhtml/session_quote')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $logContext->method('getMetaData')->willReturn([]);

        $this->observer = Mage::getModel(
            'ebayenterprise_giftcard/adminhtml_observer',
            [
                'container' => $this->container,
                'helper' => $this->helper,
                'log_context' => $logContext,
                'session' => $this->session
            ]
        );
    }

    /**
     * When adding a gift card to the order, the card should be retrieved from
     * the container by card number, have the PIN set on the card and finally
     * be added to the order.
     *
     * @param bool
     * @dataProvider provideTrueFalse
     */
    public function testAddGiftCard($success)
    {
        $cardNumber = '1111111111111111';
        $pin = '1234';

        $this->container
            ->method('getGiftCard')
            ->with($cardNumber)
            ->willReturn($this->giftCard);
        // Pin must be set on the gift card to be added.
        $this->giftCard->expects($this->once())
            ->method('setPin')
            ->with($this->identicalTo($pin))
            ->willReturnSelf();

        // Helper should be used to add the gift card to the order. When gift
        // card is successfully added, it should return self. Otherwise, it will
        // thrown an exception.
        $addGiftCardInvoker = $this->helper->expects($this->once())
            ->method('addGiftCardToOrder')
            ->with($this->identicalTo($this->giftCard), $this->identicalTo($this->container));

        if ($success) {
            $addGiftCardInvoker->willReturnSelf();
        } else {
            $addGiftCardInvoker->willThrowException(new EbayEnterprise_GiftCard_Exception('TEST EXCEPTION: ' . __METHOD__));
        }

        $this->assertSame(
            $this->observer,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($this->observer, 'addGiftCard', [$cardNumber, $pin])
        );

        // When gift card added successfully, expect a success message to have
        // been added to the session. When unsuccessful, should add an error
        // message to the session.
        $expectMessageType = $success ? Mage_Core_Model_Message::SUCCESS : Mage_Core_Model_Message::ERROR;
        $this->assertCount(1, $this->session->getMessages()->getItemsByType($expectMessageType));
    }
}
