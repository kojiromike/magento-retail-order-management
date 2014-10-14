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

class EbayEnterprise_GiftCard_Test_Model_ContainerTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Get getting a new gift card when one is requested with a card number and
	 * pin that are not already included in the storage
	 */
	public function testGetNewGiftCard()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$number = '1234123412341234';
		$storage = new SplObjectStorage();
		$container = Mage::getModel('ebayenterprise_giftcard/container', array('gift_card_storage' => $storage));

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
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$number = '1234123412341234';

		$giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($number);
		$storage = new SplObjectStorage();
		$storage->attach($giftCard);

		$container = Mage::getModel('ebayenterprise_giftcard/container', array('gift_card_storage' => $storage));

		$returned = $container->getGiftCard($number);
		// should return same instance of object in storage
		$this->assertSame($giftCard, $returned);
	}
	/**
	 * Test for a gift card to be added to the container.
	 */
	public function testAddGiftCard()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$number = '1234123412341234';
		$pin = '1234';

		$storage = new SplObjectStorage();
		$container = Mage::getModel('ebayenterprise_giftcard/container', array('gift_card_storage' => $storage));

		$giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($number)->setPin($pin);
		$container->updateGiftCard($giftCard);
		$this->assertTrue($storage->contains($giftCard));
		$this->assertSame($giftCard, $container->getGiftCard($number));
	}
	/**
	 * Can only add gift cards that have at least a card number and PIN. Attempting
	 * to add one without either of these will result in an exception.
	 */
	public function testAddInvalidGiftCard()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard');
		$container = Mage::getModel('ebayenterprise_giftcard/container');

		$this->setExpectedException('EbayEnterprise_GiftCard_Exception');
		$container->updateGiftCard($giftCard);
	}
	/**
	 * If a card already in the container is re-added, the card in the store
	 * should be replaced by the given card.
	 */
	public function testUpdateExistingCardInStorage()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$cardNumber = '123412341234';
		$newNumber = '0987098709870987';
		$pin = '1234';

		$container = Mage::getModel('ebayenterprise_giftcard/container');

		$giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber)->setPin($pin);
		// add the gift card initially
		$container->updateGiftCard($giftCard);
		// modify the card
		$giftCard->setCardNumber($newNumber);
		// re-add the card to update the card in the storage
		$container->updateGiftCard($giftCard);
		// should now be able to get the card by the new card number
		$this->assertSame($giftCard, $container->getGiftCard($newNumber));
		// using the old number should no longer return the gift card
		$this->assertNotSame($giftCard, $container->getGiftCard($cardNumber));
	}
	/**
	 * Test getting all cards in storage that have not been redeemed
	 */
	public function testGetUnredeemedGiftCards()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$giftCards = array(
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber('1234')->setPin('1234'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber('2345')->setPin('2345'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber('3456')->setPin('3456'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber('4567')->setPin('4567'),
		);
		$container = Mage::getModel('ebayenterprise_giftcard/container');
		// add the gift cards to the container
		foreach ($giftCards as $card) {
			$container->updateGiftCard($card);
		}

		$unredeemed = $container->getUnredeemedGiftCards();
		// should only contain the two unredeemed gift cards
		$this->assertSame(2, $unredeemed->count());
		// should not contain redeemed gift cards
		$this->assertFalse($unredeemed->contains($giftCards[0]));
		$this->assertFalse($unredeemed->contains($giftCards[1]));
		// should contain unredeemed gift cards
		$this->assertTrue($unredeemed->contains($giftCards[2]));
		$this->assertTrue($unredeemed->contains($giftCards[3]));
	}
	/**
	 * Test getting all cards in storage that have been redeemed
	 */
	public function testGetRedeemedGiftCards()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$giftCards = array(
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber('1234')->setPin('1234'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber('2345')->setPin('2345'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber('3456')->setPin('3456'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber('4567')->setPin('4567'),
		);
		$container = Mage::getModel('ebayenterprise_giftcard/container');
		// add the gift cards to the container
		foreach ($giftCards as $card) {
			$container->updateGiftCard($card);
		}

		$redeemed = $container->getRedeemedGiftCards();
		// should only contain the two redeemed gift cards
		$this->assertSame(2, $redeemed->count());
		// should contain redeemed gift cards
		$this->assertTrue($redeemed->contains($giftCards[0]));
		$this->assertTrue($redeemed->contains($giftCards[1]));
		// should not contain unredeemed gift cards
		$this->assertFalse($redeemed->contains($giftCards[2]));
		$this->assertFalse($redeemed->contains($giftCards[3]));
	}
	/**
	 * Test getting all cards in the container
	 */
	public function testGetAllGiftCards()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$storage = new SplObjectStorage();
		$giftCards = array(
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber('1234')->setPin('1234'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(true)->setCardNumber('2345')->setPin('2345'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber('3456')->setPin('3456'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setIsRedeemed(false)->setCardNumber('4567')->setPin('4567'),
		);
		$container = Mage::getModel('ebayenterprise_giftcard/container', array('gift_card_storage' => $storage));
		// add the gift cards to the container
		foreach ($giftCards as $card) {
			$container->updateGiftCard($card);
		}

		$cards = $container->getAllGiftCards();
		// add cards added should be returned
		$this->assertSame(4, $cards->count());
		// should *not* be the same SplObjectStorage used by the container internally
		$this->assertNotSame($storage, $cards);
		foreach ($giftCards as $card) {
			$this->assertTrue($cards->contains($card));
		}
	}
	public function provideCardToRemove()
	{
		return array(
			array('nonexistent', 2),
			array('3456', 1),
		);
	}
	/**
	 * Test removing a giftcard from the container
	 * @dataProvider provideCardToRemove
	 */
	public function testRemoveGiftCard($cardNumber, $expectedCount)
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$storage = new SplObjectStorage();
		$giftCards = array(
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber('3456')->setPin('3456'),
			Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber('4567')->setPin('4567'),
		);
		$container = Mage::getModel('ebayenterprise_giftcard/container', array('gift_card_storage' => $storage));
		// add the gift cards to the container
		foreach ($giftCards as $card) {
			$container->updateGiftCard($card);
		}
		$this->assertCount(2, $container->getAllGiftCards());
		$card = $container->getGiftCard($cardNumber);
		$container->removeGiftCard($card);
		$this->assertCount($expectedCount, $container->getAllGiftCards());
	}
	public function testRemoveAllGiftCards()
	{
		// replace session to prevent headers already sent error
		$this->_replaceSession('checkout/session');

		$testCardNumber = '123412341234';
		$gc = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($testCardNumber);

		$container = Mage::getModel('ebayenterprise_giftcard/container');
		$container->updateGiftCard($gc);
		// test card should be in the container
		$this->assertSame($gc, $container->getGiftCard($testCardNumber));
		// empty all cards from the container
		$container->removeAllGiftCards();
		// test card should no longer be in the container
		$this->assertNotSame($gc, $container->getGiftCard($testCardNumber));
	}
}
