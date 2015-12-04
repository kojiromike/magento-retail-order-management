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

class EbayEnterprise_GiftCard_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_GiftCard_Helper_Data $giftCardHelper */
    protected $giftCardHelper;
    /** @var EbayEnterprise_GiftCard_Model_Session (mock) */
    protected $session;
    /** @var SplObjectStorage */
    protected $giftCardStorage;
    /** @var EbayEnterprise_Giftcard_Model_Container */
    protected $giftCardContainer;

    public function setUp()
    {
        parent::setUp();

        $this->giftCardStorage = new SplObjectStorage;

        $this->session = $this->getModelMockBuilder('ebayenterprise_giftcard/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->giftCardContainer = Mage::getModel(
            'ebayenterprise_giftcard/container',
            [
                'gift_card_storage' => $this->giftCardStorage,
                'session' => $this->session
            ]
        );

        $this->giftCardHelper = Mage::helper('ebayenterprise_giftcard');
    }

    /**
     * Test adding a gift card to the container. Success path should result in
     * the card being in the container after having the card's balance checked.
     */
    public function testAddGiftCardToOrder()
    {
        // replace session used by the gift card container - prevents headers already sent error from session
        $this->_replaceSession('ebayenterprise_giftcard/session');

        $cardNumber = '1111222233334444';
        $pin = '1234';
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('checkBalance'));
        $card->setBalanceAmount(25.00)->setCardNumber($cardNumber)->setPin($pin);
        $card->expects($this->once())
            ->method('checkBalance')
            ->will($this->returnSelf());

        // attempt to add the gift card to the order
        $this->giftCardHelper->addGiftCardToOrder($card, $this->giftCardContainer);

        // matching card (has same data) should be returned from the container
        $this->assertSame(
            $pin,
            $this->giftCardContainer->getGiftCard($cardNumber)->getPin()
        );
    }

    /**
     * Test that gift cards with zero balance should not be added to the cart/container.
     */
    public function testAddGiftCardToOrderZeroBalance()
    {
        // replace session used by the gift card container - prevents headers already sent error from session
        $this->_replaceSession('ebayenterprise_giftcard/session');

        $cardNumber = '1111222233334444';
        $pin = '1234';
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('checkBalance'));
        $card->setBalanceAmount(0.00)->setCardNumber($cardNumber)->setPin($pin);
        $card->expects($this->once())
            ->method('checkBalance')
            ->will($this->returnSelf());

        // Need custom exception testing: want to make sure that an expected
        // exception is thrown but also need to test some post conditions,
        // specifically that the gift cards wasn't added to the container. Catch
        // the exception thrown and validate that the expected exception was
        // thrown and caught in the test.
        $thrownException = null;
        try {
            // attempt to add the gift card to the order
            $this->giftCardHelper->addGiftCardToOrder($card, $this->giftCardContainer);
        } catch (EbayEnterprise_GiftCard_Exception $e) {
            $thrownException = $e;
        }

        $this->assertInstanceOf('EbayEnterprise_GiftCard_Exception', $thrownException);

        // card should not have been added to the container - get gift card with same number will return new gift card instance
        $this->assertNotSame(
            $card->getPin(),
            $this->giftCardContainer->getGiftCard($cardNumber)->getPin()
        );
    }

    /**
     * Test that when a balance check fails, gift cards are not added to the cart/container.
     */
    public function testAddGiftCardToOrderBalanceCheckFails()
    {
        // replace session used by the gift card container - prevents headers already sent error from session
        $this->_replaceSession('ebayenterprise_giftcard/session');

        $cardNumber = '1111222233334444';
        $pin = '1234';
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('checkBalance'));
        $card->setBalanceAmount(50.00)->setCardNumber($cardNumber)->setPin($pin);
        $card->expects($this->once())
            ->method('checkBalance')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception));

        // Need custom exception testing: want to make sure that an expected
        // exception is thrown but also need to test some post conditions,
        // specifically that the gift cards wasn't added to the container. Catch
        // the exception thrown and validate that the expected exception was
        // thrown and caught in the test.
        $thrownException = null;
        try {
            // attempt to add the gift card to the order
            $this->giftCardHelper->addGiftCardToOrder($card, $this->giftCardContainer);
        } catch (EbayEnterprise_GiftCard_Exception $e) {
            $thrownException = $e;
        }

        $this->assertInstanceOf('EbayEnterprise_GiftCard_Exception', $thrownException);

        // card should not have been added to the container - get gift card with same number will return new gift card instance
        $this->assertNotSame(
            $card->getPin(),
            $this->giftCardContainer->getGiftCard($cardNumber)->getPin()
        );
    }
}
