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
    public function testCheckQuoteForChanges()
    {
        $quote = $this->getModelMock('sales/quote');
        $event = $this->getMock('Varien_Event', array('getQuote'));
        $evtObserver = $this->getMock('Varien_Event_Observer', array('getEvent'));
        $evtObserver->expects($this->any())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->any())->method('getQuote')->will($this->returnValue($quote));

        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(array('updateWithQuote'))
            ->getMock();
        $session
            ->expects($this->once())
            ->method('updateWithQuote')
            ->with($this->identicalTo($quote))
            ->will($this->returnSelf());

        $this->replaceByMock('model', 'eb2ccore/session', $session);

        $observer = Mage::getModel('eb2ccore/observer');
        $this->assertSame($observer, $observer->checkQuoteForChanges($evtObserver));
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
            'eb2cinventory/observer',
            $this->getModelMock('eb2cinventory/observer')
        );
        $this->replaceByMock(
            'model',
            'ebayenterprise_giftcard/observer',
            // disable constructor to prevent session from being started
            $this->getModelMockBuilder('ebayenterprise_giftcard/observer')->disableOriginalConstructor()->getMock()
        );

        $quote = $this->getModelMock('sales/quote');
        $order = $this->getModelMock('sales/order', array('getQuote'));
        $order->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $eventObserver = new Varien_Event_Observer(
            array('event' => new Varien_Event(
                array('order' => $order)
            ))
        );

        $observer = Mage::getSingleton('eb2ccore/observer');
        $this->assertSame($observer, $observer->processExchangePlatformOrder($eventObserver));
        $this->assertEventDispatchedExactly('eb2c_allocate_inventory', 1);
        $this->assertEventDispatchedExactly('ebayenterprise_giftcard_redeem', 1);
    }
    /**
     * Test that when the allocation event causes an exception to be thrown,
     * the allocation request fails, the exception should be caught, a flag
     * set in the session, and the exception re-thrown.
     */
    public function testProcessExchangePlatformOrderAllocationFails()
    {
        // Stub out and replace models that should be listening to the events
        // that get dispatched to prevent them from actually making requests.
        $inventoryObserver = $this->getModelMock('eb2cinventory/observer', array('processAllocation'));
        $this->replaceByMock(
            'model',
            'eb2cinventory/observer',
            $inventoryObserver
        );
        // This may be overly tight coupling though the event dispatch but necessary
        // to allow the exception to get thrown.
        $inventoryObserver->expects($this->once())
            ->method('processAllocation')
            ->will($this->throwException(new EbayEnterprise_Eb2cInventory_Model_Allocation_Exception));
        $this->replaceByMock(
            'model',
            'eb2cpayment/observer',
            // disable constructor to prevent session start
            $this->getModelMockBuilder('eb2cpayment/observer')->disableOriginalConstructor()->getMock()
        );

        // When the exception is thrown from inventory, ensure the session gets
        // flagged to retain the allocation and not roll it back later.
        $session = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(array('setRetainAllocation'))
            ->getMock();
        $session->expects($this->once())
            ->method('setRetainAllocation')
            ->with($this->isTrue())
            ->will($this->returnSelf());
        $this->replaceByMock('singleton', 'checkout/session', $session);

        $quote = $this->getModelMock('sales/quote');
        $order = $this->getModelMock('sales/order', array('getQuote'));
        $order->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $eventObserver = new Varien_Event_Observer(
            array('event' => new Varien_Event(
                array('order' => $order)
            ))
        );

        $this->setExpectedException('EbayEnterprise_Eb2cInventory_Model_Allocation_Exception');
        $observer = Mage::getSingleton('eb2ccore/observer');
        $this->assertSame($observer, $observer->processExchangePlatformOrder($eventObserver));
        $this->assertEventDispatchedExactly('eb2c_allocate_inventory', 1);
        $this->assertEventDispatchedExactly('ebayenterprise_giftcard_redeem', 0);
    }
    /**
     * Test triggering the Exchange platform rollbacks.
     * For now, should just dispatch an event with the quote and order
     */
    public function testRollbackExchangePlatformOrder()
    {
        $this->replaceByMock(
            'model',
            'eb2cinventory/observer',
            $this->getModelMock('eb2cinventory/observer')
        );
        $this->replaceByMock(
            'model',
            'ebayenterprise_giftcard/observer',
            // prevent initializing the checkout/session in the constructor
            $this->getModelMockBuilder('ebayenterprise_giftcard/observer')->disableOriginalConstructor()->getMock()
        );
        $this->replaceByMock(
            'model',
            'eb2cpayment/observer',
            // prevent initializing the checkout/session in the constructor
            $this->getModelMockBuilder('eb2cpayment/observer')->disableOriginalConstructor()->getMock()
        );
        $quote = $this->getModelMock('sales/quote');
        $order = $this->getModelMock('sales/order');
        $eventObserver = new Varien_Event_Observer(
            array('event' => new Varien_Event(
                array('quote' => $quote, 'order' => $order)
            ))
        );

        $observer = Mage::getSingleton('eb2ccore/observer');
        $this->assertSame($observer, $observer->rollbackExchangePlatformOrder($eventObserver));
        $this->assertEventDispatchedExactly('eb2c_order_creation_failure', 1);
    }
}
