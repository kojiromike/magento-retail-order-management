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

class EbayEnterprise_Order_Test_Block_Overrides_Checkout_Onepage_SuccessTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Scenario: Get the ROM order view URL on one page success page
     * Given an order increment id.
     * When getting ROM order view URL in one page success page.
     * Then an absolute URL containing the Path to the ROM order view and order increment id will be returned.
     */
    public function testViewOrderUrl()
    {
        /** @var string */
        $orderId = '1000009981121';
        /** @var string */
        $path = 'sales/order/romview';
        /** @var string */
        $url = "http://test.example.com/{$path}/order_id/{$orderId}";

        /** @var Mock_EbayEnterprise_Order_Overrides_Block_Checkout_Onepage_Success $success */
        $success = $this->getBlockMock('ebayenterprise_orderoverrides/checkout_onepage_success', ['getOrderId', 'getUrl']);
        $success->expects($this->once())
            ->method('getOrderId')
            ->will($this->returnValue($orderId));
        $success->expects($this->once())
            ->method('getUrl')
            ->with($this->identicalTo($path), $this->identicalTo(['order_id' => $orderId]))
            ->will($this->returnValue($url));

        $this->assertSame($url, $success->getViewOrderUrl());
    }
}
