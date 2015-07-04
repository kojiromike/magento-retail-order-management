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
    /** @var EbayEnterprise_Eb2cCore_Helper_Data $coreHelper */
    protected $coreHelper;
    /** @var EbayEnterprise_GiftCard_Helper_Data $giftCardHelper */
    protected $giftCardHelper;

    public function setUp()
    {
        parent::setUp();
        $this->coreHelper = Mage::helper('eb2ccore');
        $this->giftCardHelper = Mage::helper('ebayenterprise_giftcard');
    }

    /**
     * Test looking up a card's tender type based on a set of configured card
     * number ranges.
     * @dataProvider dataProvider
     */
    public function testLookupTenderTypeForCard($cardNumber, $tenderType, $ranges)
    {
        $config = $this->buildCoreConfigRegistry(array('binRanges' => $ranges));

        $helper = $this->getHelperMock('ebayenterprise_giftcard/data', array('getConfigModel'));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));

        $giftcard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber);
        $this->assertSame($tenderType, $helper->lookupTenderTypeForCard($giftcard));
    }
    /**
     * If a card number is not found to be within any bin range, an exception
     * should be thrown.
     * @dataProvider dataProvider
     */
    public function testLookupTenderTypeForCardNotFound($cardNumber, $ranges)
    {
        $config = $this->buildCoreConfigRegistry(array('binRanges' => $ranges));
        $helper = $this->getHelperMock('ebayenterprise_giftcard/data', array('getConfigModel'));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));

        $giftcard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber);
        $this->setExpectedException('EbayEnterprise_GiftCard_Exception_InvalidCardNumber_Exception');
        $helper->lookupTenderTypeForCard($giftcard);
    }
    /**
     * Test adding a gift card to the container. Success path should result in
     * the card being in the container after having the card's balance checked.
     */
    public function testAddGiftCardToOrder()
    {
        // replace session used by the gift card container - prevents headers already sent error from session
        $this->_replaceSession('checkout/session');

        $cardNumber = '1111222233334444';
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('checkBalance'));
        $card->setBalanceAmount(25.00)->setCardNumber($cardNumber);
        $card->expects($this->once())
            ->method('checkBalance')
            ->will($this->returnSelf());

        // attempt to add the gift card to the order
        $this->giftCardHelper->addGiftCardToOrder($card);

        // card should be in the container
        $this->assertSame(
            $card,
            Mage::getModel('ebayenterprise_giftcard/container')->getGiftCard($cardNumber)
        );
    }
    /**
     * Test that gift cards with zero balance should not be added to the cart/container.
     */
    public function testAddGiftCardToOrderZeroBalance()
    {
        // replace session used by the gift card container - prevents headers already sent error from session
        $session = $this->_replaceSession('checkout/session');

        $cardNumber = '1111222233334444';
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('checkBalance'));
        $card->setBalanceAmount(0.00)->setCardNumber($cardNumber);
        $card->expects($this->once())
            ->method('checkBalance')
            ->will($this->returnSelf());

        // attempt to add the gift card to the order
        $this->giftCardHelper->addGiftCardToOrder($card);

        // zero balance gift card should result in new error message in the checkout session
        $this->assertCount(1, $session->getMessages()->getErrors());
        // card should not have been added to the container - get gift card with same number will return new gift card instance
        $this->assertNotSame(
            $card,
            Mage::getModel('ebayenterprise_giftcard/container')->getGiftCard($cardNumber)
        );
    }
    /**
     * Test that when a balance check fails, gift cards are not added to the cart/container.
     */
    public function testAddGiftCardToOrderBalanceCheckFails()
    {
        // replace session used by the gift card container - prevents headers already sent error from session
        $session = $this->_replaceSession('checkout/session');

        $cardNumber = '1111222233334444';
        $card = $this->getModelMock('ebayenterprise_giftcard/giftcard', array('checkBalance'));
        $card->setBalanceAmount(50.00)->setCardNumber($cardNumber);
        $card->expects($this->once())
            ->method('checkBalance')
            ->will($this->throwException(new EbayEnterprise_GiftCard_Exception));

        // attempt to add the gift card to the order
        $this->giftCardHelper->addGiftCardToOrder($card);

        // gift card balance check failures should result in new error message
        $this->assertCount(1, $session->getMessages()->getErrors());
        // card should not have been added to the container - get gift card with same number will return new gift card instance
        $this->assertNotSame(
            $card,
            Mage::getModel('ebayenterprise_giftcard/container')->getGiftCard($cardNumber)
        );
    }
}
