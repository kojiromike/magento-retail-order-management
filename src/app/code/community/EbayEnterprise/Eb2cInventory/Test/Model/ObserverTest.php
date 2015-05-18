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

class EbayEnterprise_Eb2cInventory_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }

    /**
     * Mock a Varien_Event_Observer with a Varien_Event with a 'getQuote' method
     * that will return the given quote
     * @param  Mage_Sales_Model_Quote $quote Quote the Varien_Event contains
     * @return Mock_Varien_Event_Observer Mocked Varien_Event_Observer wrapping a mocked Varien_Event wrapping the quote object
     */
    protected function _mockObserverWithQuote(Mage_Sales_Model_Quote $quote)
    {
        $event = $this->getMock('Varien_Event', array('getQuote'));
        $observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
        $observer
            ->expects($this->any())
            ->method('getEvent')
            ->will($this->returnValue($event));
        $event
            ->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        return $observer;
    }

    public function providerCheckInventoryQuantity()
    {
        return array(
            //    updateQty updateDetails
            array(true,     true,),
            array(false,    true,),
            array(false,    false,),
        );
    }

    /**
     * Test the abstract method for making an inventory service request and updating
     * the quote with the results.
     */
    public function testMakeRequestAndUpdate()
    {
        $response = '<MockResponse/>';
        $observer = Mage::getModel('eb2cinventory/observer');
        $quote = $this->getModelMock('sales/quote');
        $quoteDiff = array('skus' => array('45-123' => 4), 'shipping' => array(array('method' => 'flatrate')));
        $request = $this->getModelMock(
            'eb2cinventory/request_abstract',
            array('makeRequestForQuote', 'updateQuoteWithResponse', '_buildRequestMessage')
        );
        $request
            ->expects($this->once())
            ->method('makeRequestForQuote')
            ->with($this->identicalTo($quote))
            ->will($this->returnValue($response));
        $request
            ->expects($this->once())
            ->method('updateQuoteWithResponse')
            ->with($this->identicalTo($quote), $this->identicalTo($response));

        $this->assertSame($response, EcomDev_Utils_Reflection::invokeRestrictedMethod($observer, '_makeRequestAndUpdate', array($request, $quote, $quoteDiff)));
    }

    /**
     * When a quote does not have any items that have managed stock, no allocation
     * request should be made.
     * @param  Varien_Event_Observer $observer
     */
    public function testNoAllocationRequestWhenNotRequired()
    {
        $mockQuote = $this->getModelMock('sales/quote');
        $eventObserver = new Varien_Event_Observer(
            array('event' => new Varien_Event(
                array('quote' => $mockQuote)
            ))
        );
        $allocationMock = $this->getModelMock('eb2cinventory/allocation', array('requiresAllocation', 'allocateQuoteItems'));
        // make requiresAllocation fail
        $allocationMock->expects($this->once())
            ->method('requiresAllocation')
            ->with($this->identicalTo($mockQuote))
            ->will($this->returnValue(false));
        // ensure allocateQuoteItems is not called
        $allocationMock->expects($this->never())
            ->method('allocateQuoteItems');
        $this->replaceByMock('model', 'eb2cinventory/allocation', $allocationMock);
        Mage::getModel('eb2cinventory/observer')->processAllocation($eventObserver);
    }

    /**
     * AllocateQuoteItems may return an empty string or false.
     * Fail silently in these cases and log a warning
     *
     * @param string|bool|null $message
     * @dataProvider dataProvider
     */
    public function testNoAllocationRequestMessageLogsWarning($message)
    {
        // Sanity check
        if ($message) {
            $this->fail("Expected a falsy message. Instead, got '$message'.");
        }

        // `warning` should be called once if `$message` is falsy.
        $logger = $this->getHelperMock('ebayenterprise_magelog', array('warning'));
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringStartsWith('Allocation response '), $this->isType('array'));
        $this->replaceByMock('helper', 'ebayenterprise_magelog', $logger);

        // Stubs
        $allocationMock = $this->getModelMock('eb2cinventory/allocation', array('requiresAllocation', 'allocateQuoteItems'));
        $allocationMock->expects($this->any())
            ->method('requiresAllocation')
            ->will($this->returnValue(true));
        $allocationMock->expects($this->any())
            ->method('allocateQuoteItems')
            ->will($this->returnValue($message));
        $this->replaceByMock('model', 'eb2cinventory/allocation', $allocationMock);
        $eventObserver = new Varien_Event_Observer(
            array('event' => new Varien_Event(
                array('quote' => $this->getModelMock('sales/quote'))
            ))
        );

        Mage::getModel('eb2cinventory/observer')->processAllocation($eventObserver);
    }

    /**
     * Test rolling back an allocation - should retrieve the quote the allocation
     * was made from from the event observer and pass it through to the
     * eb2cinventory/quote helper's rollbackAllocation method.
     */
    public function testRollbackAllocation()
    {
        $session = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $session);
        $quote = Mage::getModel('sales/quote');
        $helper = $this->getHelperMock('eb2cinventory/quote', array('rollbackAllocation'));
        $helper->expects($this->once())
            ->method('rollbackAllocation')
            ->with($this->identicalTo($quote))
            ->will($this->returnSelf());
        $this->replaceByMock('helper', 'eb2cinventory/quote', $helper);
        $observer = Mage::getModel('eb2cinventory/observer');
        $this->assertSame(
            $observer,
            $observer->rollbackAllocation(
                new Varien_Event_Observer(array('event' => new Varien_Event(array('quote' => $quote))))
            )
        );
    }

    /**
     * When the session contains a flag indicating the allocation should not be
     * rolled back, such as in the case that a quote could not be fully allocated,
     * do cause the allocation rollback request to be made.
     */
    public function testRetainAllocationNoRollback()
    {
        $session = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(array('getRetainAllocation'))
            ->getMock();
        // Get the flag value from the session and ensure it gets cleared
        $session->expects($this->once())
            ->method('getRetainAllocation')
            ->with($this->isTrue())
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'checkout/session', $session);

        $helper = $this->getHelperMock('eb2cinventory/quote', array('rollbackAllocation'));
        // When the session indicates we should retain the allocation, don't
        // roll it back.
        $helper->expects($this->never())
            ->method('rollbackAllocation');
        $this->replaceByMock('helper', 'eb2cinventory/quote', $helper);

        $observer = Mage::getModel('eb2cinventory/observer');
        $this->assertSame(
            $observer,
            $observer->rollbackAllocation(
                new Varien_Event_Observer(
                    array('event' => new Varien_Event(
                        array('quote' => Mage::getModel('sales/quote'))
                    ))
                )
            )
        );
    }
    /**
     * Test that the method 'EbayEnterprise_Eb2cInventory_Model_Observer::extractQuoteErrorMessage'
     * when invoked and passed a sales/quote instance with known error message it will extract it and passed along
     * to be added to the checkout session.
     */
    public function testExtractQuoteErrorMessage()
    {
        $message = 'This is an error message';
        $error = Mage::getModel('core/message')->error($message);
        $quote = $this->getModelMock('sales/quote', array('getErrors'));
        $quote->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue(array($error)));

        $session = $this->getModelMockBuilder('checkout/session')
            // Disabling the checkout session in order to prevent the following
            // exception from being thrown: "Exception: Warning: session_start(): Cannot send session cookie:
            ->disableOriginalConstructor()
            ->setMethods(array('addMessage'))
            ->getMock();
        $session->expects($this->once())
            ->method('addMessage')
            ->with($this->identicalTo($error))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'checkout/session', $session);

        $observer = Mage::getModel('eb2cinventory/observer');
        $this->assertSame($observer, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $observer,
            '_extractQuoteErrorMessage',
            array($quote)
        ));
    }
}
