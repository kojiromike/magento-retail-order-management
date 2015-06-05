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

class EbayEnterprise_GiftCard_Test_Model_Total_QuoteTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * When collecting gift card totals, each gift card to be redeemed should have
     * the amount expected to be redeemed set as the amount to redeem, the order
     * address grand total should be decremented by the amount being redeemed from
     * all gift cards and data should be set on the order address indicating the
     * total amount expected to be redeemed from gift cards.
     * @param float $addressTotal Grand total of the order before gift cards
     * @param array $giftCardAmounts Expected gift card amounts by card number - balance and redeem amount
     * @param float $remainingTotal Expected address total after applying gift cards
     * @param float $redeemTotal Expected gift card amount to redeem
     * @dataProvider dataProvider
     */
    public function testCollect($addressTotal, $giftCardAmounts, $remainingTotal, $redeemTotal)
    {
        // replace checkout session to prevent headers already sent error
        $this->_replaceSession('checkout/session');
        // float cast on yaml provided value to ensure it is actually a float
        $addressTotal = (float) $addressTotal;
        $remainingTotal = (float) $remainingTotal;
        $redeemTotal = (float) $redeemTotal;

        // set of gift cards that have been applied to the order
        $giftCards = new SplObjectStorage;
        foreach ($giftCardAmounts as $cardNumber => $amts) {
            $gc = Mage::getModel('ebayenterprise_giftcard/giftcard')
                ->setCardNumber($cardNumber)
                // float cast on yaml provided value to ensure it is actually a float
                ->setBalanceAmount((float) $amts['balance'])
                ->setIsRedeemed(false);
            $giftCards->attach($gc);
        }
        $container = Mage::getModel('ebayenterprise_giftcard/container', array('gift_card_storage' => $giftCards));

        $address = Mage::getModel('sales/quote_address', array('base_grand_total' => $addressTotal, 'grand_total' => $addressTotal));

        $totalCollector = Mage::getModel('ebayenterprise_giftcard/total_quote', array('gift_card_container' => $container));
        $totalCollector->collect($address);

        // float casts on yaml provided values to ensure they are actually floats
        $this->assertSame($remainingTotal, $address->getGrandTotal());
        $this->assertSame($redeemTotal, $address->getEbayEnterpriseGiftCardBaseAppliedAmount());

        foreach ($giftCards as $card) {
            // float cast on yaml provided value to ensure it is actually a float
            $this->assertSame((float) $giftCardAmounts[$card->getCardNumber()]['redeem'], $card->getAmountToRedeem());
        }
    }
}
