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

class EbayEnterprise_Inventory_Test_Block_Override_Cart_Item_Renderer_GroupedTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test that the block method ebayenterprise_inventoryoverride/cart_item_renderer_grouped::getEddMessage()
     * when invoked, will return a string content about estimated delivery date for the grouped item in the quote.
     */
    public function testGetEddMessage()
    {
        /** @var string */
        $eddMessage = 'You can expect to receive your items between 06/11/15 and 06/15/15.';
        /** @var int */
        $qty = 2;
        /** @var Mage_Sales_Model_Quote_Item */
        $item = Mage::getModel('sales/quote_item', ['qty' => $qty]);

        /** @var Mage_Core_Block_Template $block */
        $block = $this->getBlockMock('core/template', ['setItem', 'getEddMessage']);
        $block->expects($this->once())
            ->method('setItem')
            ->with($this->identicalTo($item))
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('getEddMessage')
            ->will($this->returnValue($eddMessage));

        /** @var Mage_Core_Model_Layout $layout */
        $layout = $this->getModelMock('core/layout', ['createBlock']);
        $layout->expects($this->once())
            ->method('createBlock')
            ->with($this->identicalTo('checkout/cart_item_renderer'))
            ->will($this->returnValue($block));

        /** @var EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer_grouped */
        $groupedRenderer = $this->getBlockMock('ebayenterprise_inventoryoverride/cart_item_renderer_grouped', ['getItem', 'getLayout']);
        $groupedRenderer->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($item));
        $groupedRenderer->expects($this->once())
            ->method('getLayout')
            ->will($this->returnValue($layout));

        $this->assertSame($eddMessage, $groupedRenderer->getEddMessage());
    }
}
