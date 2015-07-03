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

class EbayEnterprise_Order_Test_Block_Overrides_Checkout_Multishipping_SuccessTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * @return array
     */
    public function providerGetOrderIds()
    {
        return [
            [['55' => '1000939992828'], ['55' => '1000939992828']],
            [null, false],
        ];
    }

    /**
     * Scenario: Get multi-shipping order Ids from the session
     * Given a session that has an array of order ids.
     * When getting order ids from the session.
     * Then an array with key order entity id mapped to the order increment id will be returned.
     *
     * Given session has no order ids.
     * When getting order ids from the session.
     * Then the boolean value false will be returned.
     *
     * @param array | null
     * @param null | bool
     * @dataProvider providerGetOrderIds
     */
    public function testGetOrderIds($orderIds, $result)
    {
        /** @var Mock_Mage_Core_Model_Session $coreSession */
        $coreSession = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(['getOrderIds'])
            ->getMock();
        $coreSession->expects($this->once())
            ->method('getOrderIds')
            ->will($this->returnValue($orderIds));

        /** @var Mock_EbayEnterprise_Order_Helper_Factory $orderFactory */
        $orderFactory = $this->getHelperMock('ebayenterprise_order/factory', ['getCoreSessionModel']);
        $orderFactory->expects($this->once())
            ->method('getCoreSessionModel')
            ->will($this->returnValue($coreSession));

        /** @var Mock_EbayEnterprise_Order_Overrides_Block_Checkout_Multishipping_Success $success */
        $success = $this->getBlockMock('ebayenterprise_orderoverrides/checkout_multishipping_success', ['foo'], false, [[
            'order_factory' => $orderFactory,
        ]]);

        $this->assertSame($result, $success->getOrderIds());
    }

    /**
     * Scenario: Get the ROM order view URL on Multi-shipping success page
     * Given an order entity id.
     * And a session that has an array with order entity id mapped to an order increment id.
     * When getting ROM order view URL from Multi-shipping success page.
     * Then an absolute URL containing the Path to the ROM order view and order increment id will be returned.
     */
    public function testViewOrderUrl()
    {
        /** @var string $entityId */
        $entityId = '55';
        /** @var string */
        $orderId = '1000009981121';
        /** @var string */
        $path = 'sales/order/romview';
        /** @var string */
        $url = "http://test.example.com/{$path}/order_id/{$orderId}";
        /** @var array $orderIds */
        $orderIds = [$entityId => $orderId];
        /** @var Mock_Mage_Core_Model_Session $coreSession */
        $coreSession = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(['getOrderIds'])
            ->getMock();
        $coreSession->expects($this->once())
            ->method('getOrderIds')
            ->with($this->identicalTo(true))
            ->will($this->returnValue($orderIds));

        /** @var Mock_EbayEnterprise_Order_Helper_Factory $orderFactory */
        $orderFactory = $this->getHelperMock('ebayenterprise_order/factory', ['getCoreSessionModel']);
        $orderFactory->expects($this->once())
            ->method('getCoreSessionModel')
            ->will($this->returnValue($coreSession));

        /** @var Mock_EbayEnterprise_Order_Overrides_Block_Checkout_Multishipping_Success $success */
        $success = $this->getBlockMock('ebayenterprise_orderoverrides/checkout_multishipping_success', ['getOrderIds', 'getUrl'], false, [[
            'order_factory' => $orderFactory,
        ]]);
        $success->expects($this->once())
            ->method('getOrderIds')
            ->will($this->returnValue($orderIds));
        $success->expects($this->once())
            ->method('getUrl')
            ->with($this->identicalTo($path), $this->identicalTo(['order_id' => $orderId]))
            ->will($this->returnValue($url));

        $this->assertSame($url, $success->getViewOrderUrl($entityId));
    }
}
