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

class EbayEnterprise_GiftCard_Test_Model_SessionTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_GiftCard_Model_Session */
    protected $session;

    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $this->session = $this->getModelMockBuilder('ebayenterprise_giftcard/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    /**
     * GIVEN A <giftCard> with <cardNumber>
     * WHEN The <giftCard> is set in the session
     * THEN A <sessionGiftCard> with the same data should be retrievable from the session
     */
    public function testCurrentGiftCard()
    {
        $cardNumber = '1234123412341234';
        $giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard')->setCardNumber($cardNumber);
        $this->session->setEbayEnterpriseCurrentGiftCard($giftCard);

        $sessionGiftCard = $this->session->getEbayEnterpriseCurrentGiftCard(true);

        // Gift card and returned card should have same data
        $this->assertSame($giftCard->getMemo(), $sessionGiftCard->getMemo());
        // Trying to get gift card again (retrieved with clear = true) should
        // return null, value was cleared.
        $this->assertNull($this->session->getEbayEnterpriseCurrentGiftCard());
    }

    /**
     * Provide expected, default and existing object storage data in the session.
     *
     * @return array
     */
    public function provideContainerStorage()
    {
        $splStorage = new SplObjectStorage;
        $altStorage = new SplObjectStorage;
        return [
            [$splStorage, $splStorage, null,],
            [$splStorage, $altStorage, $splStorage],
        ];
    }

    /**
     * GIVEN A <session> instance with <existing> set as the container storage
     * AND A <default> storage provided
     * WHEN Getting the container storage from the <session>
     * THEN The <expected> storage should be retrieved
     * AND The <expected> storage should be in the session
     *
     * @dataProvider provideContainerStorage
     * @param SplObjectStorage
     * @param SplObjectStorage
     * @param SplObjectStorage|null
     */
    public function testGetContainerStorageSetDefault(
        SplObjectStorage $expected,
        SplObjectStorage $default,
        SplObjectStorage $existing = null
    ) {
        $existing = $existing;
        $default = $default;
        $expected = $expected;
        $this->session->setData(EbayEnterprise_GiftCard_Model_Session::CONTAINER_STORAGE_KEY, $existing);

        $this->session->getContainerStorageSetDefault($default);

        $this->assertSame($expected, $this->session->getContainerStorageSetDefault($default));
        $this->assertSame($expected, $this->session->getData(EbayEnterprise_GiftCard_Model_Session::CONTAINER_STORAGE_KEY));
    }
}
