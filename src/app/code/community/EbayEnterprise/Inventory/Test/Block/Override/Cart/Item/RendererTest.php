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

class EbayEnterprise_Inventory_Test_Block_Override_Cart_Item_RendererTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test that the block method ebayenterprise_inventoryoverride/cart_item_renderer::getEddMessage()
     * when invoked, will return a string content about estimated delivery date for an item in the quote.
     */
    public function testGetEddMessage()
    {
        /** @var string */
        $eddMessage = 'You can expect to receive your items between 06/11/15 and 06/15/15.';
        /** @var int */
        $qty = 2;
        /** @var Mage_Sales_Model_Quote_Item */
        $item = Mage::getModel('sales/quote_item', ['qty' => $qty]);

        /** @var EbayEnterprise_Inventory_Model_Edd */
        $edd = $this->getModelMockBuilder('ebayenterprise_inventory/edd')
            ->disableOriginalConstructor()
            ->setMethods(['getEddMessage'])
            ->getMock();
        $edd->expects($this->once())
            ->method('getEddMessage')
            ->with($this->identicalTo($item))
            ->will($this->returnValue($eddMessage));

        /** @var EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer */
        $renderer = $this->getBlockMock('ebayenterprise_inventoryoverride/cart_item_renderer', ['getItem'], false, [[
            'edd' => $edd,
        ]]);
        $renderer->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($item));

        $this->assertSame($eddMessage, $renderer->getEddMessage());
    }

    /**
     * @return array
     */
    public function providerRemoveKnownKeys()
    {
        $edd = Mage::getModel('ebayenterprise_inventory/edd');
        return [
            [
                ['foo' => 'bar', 'bar' => 'foo', 'edd' => $edd],
                ['foo' => 'bar', 'bar' => 'foo'],
            ],
            [
                ['edd' => $edd],
                [],
            ],
        ];
    }

    /**
     * Test that the block method ebayenterprise_inventoryoverride/cart_item_renderer::removeKnownKeys()
     * when invoked, will be given an array with key value pairs and we expect it to return an array
     * without the key 'edd'.
     *
     * @param array
     * @param array
     * @dataProvider providerRemoveKnownKeys
     */
    public function testRemoveKnownKeys(array $args, array $result)
    {
        /** @var EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer */
        $renderer = $this->getBlockMock('ebayenterprise_inventoryoverride/cart_item_renderer');
        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($renderer, 'removeKnownKeys', [$args]));
    }
}
