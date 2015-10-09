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


class EbayEnterprise_Eb2cCore_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Eb2cCore_Model_Observer */
    protected $observer;

    public function setUp()
    {
        $this->observer = Mage::getModel('eb2ccore/observer');
    }

    public function testCheckQuoteForChanges()
    {
        $quote = $this->getModelMock('sales/quote');
        $event = $this->getMock('Varien_Event', ['getQuote']);
        $evtObserver = $this->getMock('Varien_Event_Observer', ['getEvent']);
        $evtObserver->expects($this->any())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->any())->method('getQuote')->will($this->returnValue($quote));

        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['updateWithQuote'])
            ->getMock();
        $session
            ->expects($this->once())
            ->method('updateWithQuote')
            ->with($this->identicalTo($quote))
            ->will($this->returnSelf());

        $this->replaceByMock('model', 'eb2ccore/session', $session);

        $this->assertSame($this->observer, $this->observer->checkQuoteForChanges($evtObserver));
    }
    /**
     * Test processing the Exchange Platform order - should dispatch an event
     * to cause an inventory allocation and an event to trigger SVC redemption.
     */
    public function testProcessExchangePlatformOrder()
    {
        // Stub out and replace models that should be listening to the events
        // that get dispatched to prevent them from actually making requests.
        $this->replaceByMock(
            'model',
            'ebayenterprise_giftcard/observer',
            // disable constructor to prevent session from being started
            $this->getModelMockBuilder('ebayenterprise_giftcard/observer')->disableOriginalConstructor()->getMock()
        );

        $quote = $this->getModelMock('sales/quote');
        $order = $this->getModelMock('sales/order', ['getQuote']);
        $order->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $eventObserver = new Varien_Event_Observer(
            ['event' => new Varien_Event(
                ['order' => $order]
            )]
        );

        $this->assertSame($this->observer, $this->observer->processExchangePlatformOrder($eventObserver));
        $this->assertEventDispatchedExactly('ebayenterprise_giftcard_redeem', 1);
    }
    /**
     * Test triggering the Exchange platform rollbacks.
     * For now, should just dispatch an event with the quote and order
     */
    public function testRollbackExchangePlatformOrder()
    {
        $this->replaceByMock(
            'model',
            'ebayenterprise_giftcard/observer',
            // prevent initializing the checkout/session in the constructor
            $this->getModelMockBuilder('ebayenterprise_giftcard/observer')->disableOriginalConstructor()->getMock()
        );
        $quote = $this->getModelMock('sales/quote');
        $order = $this->getModelMock('sales/order');
        $eventObserver = new Varien_Event_Observer(
            ['event' => new Varien_Event(
                ['quote' => $quote, 'order' => $order]
            )]
        );

        $this->assertSame($this->observer, $this->observer->rollbackExchangePlatformOrder($eventObserver));
        $this->assertEventDispatchedExactly('eb2c_order_creation_failure', 1);
    }

    /**
     * Validate expected event configuration.
     *
     * @dataProvider dataProvider
     */
    public function testEventSetup($area, $eventName, $observerClassAlias, $observerMethod)
    {
        $this->_testEventConfig($area, $eventName, $observerClassAlias, $observerMethod);
    }

    /**
     * @return array
     */
    public function providerCalculateDiscountAmount()
    {
        return [
            [
                Mage::getModel('sales/quote_item', ['base_row_total' => 88.00,]),
                new Varien_Object(['base_discount_amount' => 250.00,]),
                [['amount' => 8.80]],
                79.20
            ],
            [
                Mage::getModel('sales/quote_item', ['base_row_total' => 88.00,]),
                new Varien_Object(['base_discount_amount' => 100.00,]),
                [['amount' => 8.80], ['amount' => 79.20]],
                0.00
            ],
            [
                Mage::getModel('sales/quote_item', ['base_row_total' => 88.00,]),
                new Varien_Object(['base_discount_amount' => 3.96,]),
                [['amount' => 8.80]],
                3.96
            ],
            [
                Mage::getModel('sales/quote_item', ['base_row_total' => 88.00,]),
                new Varien_Object(['base_discount_amount' => 11.88,]),
                [['amount' => 8.80]],
                11.88
            ],
        ];
    }

    /**
     * Scenario: Calculate current discount amount base on previously applied discounts
     * Given a quote item with a base row total, a Varien Object with current base discount amount and an array of previously applied discounts
     * When calculating the current discount amount base on previously applied discounts.
     * Then sum up all previously applied discount, and subtract it from the item row total.
     * Then, when the item row total with applied previous discount is less then the current discount return
     * the row total with previous applied discount. Otherwise, return the current discount.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @param Varien_Object
     * @param array
     * @param float
     * @dataProvider providerCalculateDiscountAmount
     */
    public function testCalculateDiscountAmount(Mage_Sales_Model_Quote_Item $item, Varien_Object $result, array $data, $discount)
    {
        $this->assertSame($discount, EcomDev_Utils_Reflection::invokeRestrictedMethod($this->observer, 'calculateDiscountAmount', [$item, $result, $data]));
    }
}
