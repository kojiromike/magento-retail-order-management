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

class EbayEnterprise_Inventory_Test_Block_Override_Multishipping_OverviewTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test that the block method ebayenterprise_inventoryoverride/multishipping_overview::renderTotals()
     * when invoked, will render the proper total HTML.
     */
    public function testRenderTotals()
    {
        /** @var string */
        $totalRenderHtml = '<tr/>';
        /** @var string */
        $totals = '';
        /** @var int */
        $colspan = 6;
        /** @var Mage_Checkout_Block_Cart_Totals */
        $totalsBlock = $this->getBlockMock('checkout/cart_totals', ['setTotals', 'renderTotals']);
        $totalsBlock->expects($this->any())
            ->method('setTotals')
            ->with($this->identicalTo($totals))
            ->will($this->returnSelf());
        $totalsBlock->expects($this->any())
            ->method('renderTotals')
            ->will($this->returnValueMap([
                ['', $colspan, ''],
                ['footer', $colspan, $totalRenderHtml],
            ]));

        /** @var Mage_Tax_Helper_Data */
        $tax = $this->getHelperMock('tax', ['displayCartBothPrices']);
        $tax->expects($this->once())
            ->method('displayCartBothPrices')
            ->will($this->returnValue(true));

        /** @var EbayEnterprise_Inventory_Override_Block_Multishipping_Overview */
        $overview = $this->getBlockMock('ebayenterprise_inventoryoverride/multishipping_overview', ['helper', 'getChild']);
        $overview->expects($this->once())
            ->method('helper')
            ->with($this->identicalTo('tax'))
            ->will($this->returnValue($tax));
        $overview->expects($this->any())
            ->method('getChild')
            ->will($this->returnValue($totalsBlock));

        $this->assertSame($totalRenderHtml, $overview->renderTotals($totals));
    }
}
