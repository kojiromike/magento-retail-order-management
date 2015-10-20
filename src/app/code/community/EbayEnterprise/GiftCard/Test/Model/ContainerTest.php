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

class EbayEnterprise_GiftCard_Test_Model_ContainerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var Mage_Core_Model_Session_Abstract */
    protected $session;

    public function setUp()
    {
        parent::setUp();

        $this->session = $this->getModelMockBuilder('ebayenterprise_giftcard/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    /**
     * Get getting a new gift card when one is requested with a card number and
     * pin that are not already included in the storage
     */
    public function testGetNewGiftCard()
    {
        $number = '1234123412341234';
        $storage = new SplObjectStorage();
        $container = Mage::getModel('ebayenterprise_giftcard/container', ['gift_card_storage' => $storage, 'session' => $this->session]);

        $giftCard = $container->getGiftCard($number);
        $this->assertInstanceOf('EbayEnterprise_GiftCard_Model_IGiftcard', $giftCard);
        $this->assertSame($number, $giftCard->getCardNumber());
    }

    /**
     * If a gift card already exists in the container, requesting a gift card with
     * the same card number and pin should return the object in storage.
     */
    public function testGetExistingGiftCard()
    {
        $number = '1234123412341234';

        $giftCardMemo = Mage::getModel('ebayenterprise_giftcard/giftcard_memo')->setCardNumber($number);
        $storage = new SplObjectStorage();
        $storage->attach($giftCardMemo);

        $container = Mage::getModel('ebayenterprise_giftcard/container', ['gift_card_storage' => $storage, 'session' => $this->session]);

        $giftCard = $container->getGiftCard($number);
        // Container should return gift card based on the memo in storage.
        $this->assertInstanceOf('EbayEnterprise_GiftCard_Model_IGiftcard', $giftCard);
        $this->assertSame($number, $giftCard->getCardNumber());
    }

    /**
     * Test for a gift card to be added to the container.
     */
    public function testAddGiftCard()
    {
        $number = '1234123412341234';
        $pin = '1234';

        $storage = new SplObjectStorage();
        $container = Mage::getModel('ebayenterprise_giftcard/container', ['gift_card_storage' => $storage, 'session' => $this->session]);

        $giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($number)->setPin($pin);
        $container->updateGiftCard($giftCard);
        // Storage should have the gift card memo in storage.
        $this->assertTrue($storage->contains($giftCard->getMemo()));

        // Should be able to get gift card with same data, but not same instance
        // from the container using the number of the card just added.
        $giftCardFromContainer = $container->getGiftCard($number);
        $this->assertSame($giftCard->getCardNumber(), $giftCardFromContainer->getCardNumber());
        $this->assertSame($giftCard->getPin(), $giftCardFromContainer->getPin());
    }

    /**
     * Can only add gift cards that have at least a card number and PIN. Attempting
     * to add one without either of these will result in an exception.
     */
    public function testAddInvalidGiftCard()
    {
        $giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard');
        $container = Mage::getModel('ebayenterprise_giftcard/container', ['session' => $this->session]);

        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');

        $container->updateGiftCard($giftCard);
    }

    /**
     * If a card already in the container is re-added, the card in the store
     * should be replaced by the given card.
     */
    public function testUpdateExistingCardInStorage()
    {
        $cardNumber = '123412341234';
        $newNumber = '0987098709870987';
        $pin = '1234';

        $container = Mage::getModel('ebayenterprise_giftcard/container', ['session' => $this->session]);

        $giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber)->setPin($pin);
        // add the gift card initially
        $container->updateGiftCard($giftCard);
        // modify the card
        $giftCard->setCardNumber($newNumber);
        // re-add the card to update the card in the storage
        $container->updateGiftCard($giftCard);
        // should now be able to get the card by the new card number
        $this->assertSame($newNumber, $container->getGiftCard($newNumber)->getCardNumber());
        // using the old number should no longer return the gift card
        $this->assertNotSame($newNumber, $container->getGiftCard($cardNumber)->getCardNumber());
    }

    /**
     * Test getting all cards in storage that have not been redeemed
     */
    public function testGetUnredeemedGiftCards()
    {
        $redeemedCardNumbers = ['1234', '2345'];
        $unredeemedCardNumbers = ['3456', '4567'];

        $giftCards = [
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber($redeemedCardNumbers[0])->setPin('1234'),
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber($redeemedCardNumbers[1])->setPin('2345'),
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber($unredeemedCardNumbers[0])->setPin('3456'),
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber($unredeemedCardNumbers[1])->setPin('4567'),
        ];
        $container = Mage::getModel('ebayenterprise_giftcard/container', ['session' => $this->session]);
        // add the gift cards to the container
        foreach ($giftCards as $card) {
            $container->updateGiftCard($card);
        }

        $unredeemed = $container->getUnredeemedGiftCards();
        // should only contain the two unredeemed gift cards
        $this->assertSame(2, $unredeemed->count());
        foreach ($unredeemed as $giftCard) {
            // Any cards returned should have a card number in the set of
            // unredeemed card numbers.
            $this->assertContains($giftCard->getCardNumber(), $unredeemedCardNumbers);
        }
    }

    /**
     * Test getting all cards in storage that have been redeemed
     */
    public function testGetRedeemedGiftCards()
    {
        $redeemedCardNumbers = ['1234', '2345'];
        $unredeemedCardNumbers = ['3456', '4567'];

        $giftCards = [
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber($redeemedCardNumbers[0])->setPin('1234'),
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber($redeemedCardNumbers[1])->setPin('2345'),
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber($unredeemedCardNumbers[0])->setPin('3456'),
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber($unredeemedCardNumbers[1])->setPin('4567'),
        ];
        $container = Mage::getModel('ebayenterprise_giftcard/container', ['session' => $this->session]);
        // add the gift cards to the container
        foreach ($giftCards as $card) {
            $container->updateGiftCard($card);
        }

        $redeemed = $container->getRedeemedGiftCards();
        // should only contain the two redeemed gift cards
        $this->assertSame(2, $redeemed->count());
        foreach ($redeemed as $giftCard) {
            // Any cards returned should have a card number in the set of
            // redeemed card numbers.
            $this->assertContains($giftCard->getCardNumber(), $redeemedCardNumbers);
        }
    }

    /**
     * Test getting all cards in the container
     */
    public function testGetAllGiftCards()
    {
        $cardNumbers = ['1234', '2345', '3456', '4567'];

        $giftCards = array_map(
            function ($cardNumber) {
                return Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber)->setPin('1234');
            },
            $cardNumbers
        );

        $container = Mage::getModel('ebayenterprise_giftcard/container', ['session' => $this->session]);

        // add the gift cards to the container
        foreach ($giftCards as $card) {
            $container->updateGiftCard($card);
        }

        $cards = $container->getAllGiftCards();
        // All cards added should be returned
        $this->assertSame(4, $cards->count());
        // Every card returned should have a card number in the expected set
        // of card numbers.
        foreach ($giftCards as $card) {
            $this->assertContains($card->getCardNumber(), $cardNumbers);
        }
    }

    public function provideCardToRemove()
    {
        return [
            ['nonexistent', 2],
            ['3456', 1],
        ];
    }

    /**
     * Test removing a giftcard from the container
     * @dataProvider provideCardToRemove
     */
    public function testRemoveGiftCard($cardNumber, $expectedCount)
    {
        $storage = new SplObjectStorage();
        $giftCards = [
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber('3456')->setPin('3456'),
            Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber('4567')->setPin('4567'),
        ];
        $container = Mage::getModel('ebayenterprise_giftcard/container', ['gift_card_storage' => $storage, 'session' => $this->session]);
        // add the gift cards to the container
        foreach ($giftCards as $card) {
            $container->updateGiftCard($card);
        }
        $this->assertCount(2, $container->getAllGiftCards());
        $card = $container->getGiftCard($cardNumber);
        $container->removeGiftCard($card);
        $this->assertCount($expectedCount, $container->getAllGiftCards());
    }

    /**
     * GIVEN A <giftCard> with <testCardNumber>
     * AND That <giftCard> is in the gift card <container>
     * WHEN removeAllGiftCards is called on the <container>
     * THEN No gift cards should be left in the <container>
     */
    public function testRemoveAllGiftCards()
    {
        $testCardNumber = '123412341234';
        $giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($testCardNumber);

        // Create the gift card container and add the gift card to it.
        $container = Mage::getModel('ebayenterprise_giftcard/container', ['session' => $this->session]);
        $container->updateGiftCard($giftCard);
        // Ensure the gift card was added.
        $this->assertSame($giftCard->getCardNumber(), $container->getGiftCard($testCardNumber)->getCardNumber());

        // Empty all cards from the container
        $container->removeAllGiftCards();

        // Should be no gift cards left in the container.
        $this->assertEmpty($container->getAllGiftCards());
    }
}
