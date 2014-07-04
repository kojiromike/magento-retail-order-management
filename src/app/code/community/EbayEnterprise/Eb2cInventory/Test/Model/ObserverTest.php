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

class EbayEnterprise_Eb2cInventory_Test_Model_ObserverTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * verify the observer is configured to listen for necessary events.
	 * @dataProvider dataProvider
	 */
	public function testObserverConfiguration($area, $eventName, $method)
	{
		EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined(
			$area,
			$eventName,
			'eb2cinventory/observer',
			$method
		);
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
	 * When a quote's items quantities have changed, inventory quantities and details should
	 * be updated. Test provider should run through all necessary scenarios, providing the expected
	 * changes to the quote, a PHPUnit_Framework_MockObject_Matcher_InvokedCount for how many times
	 * the update quantity method should be called, a PHPUnit_Framework_MockObject_Matcher_InvokedCount
	 * for the number of times details should be updated, and whether or not the check should result
	 * in the add to/update cart process should be blocked.
	 * @param bool $isQtyUpdated     Should inventory quantity be checked
	 * @param bool $isDetailsUpdated Should inventory details be updated
	 * @dataProvider providerCheckInventoryQuantity
	 */
	public function testCheckInventory($isQtyUpdated, $isDetailsUpdated)
	{
		$address = Mage::getModel('sales/quote_address')->addData(array(
			'street' => array('Some Street'),
			'city' => 'some city',
			'region_code' => 'Pa',
			'country_id' => 'US',
			'post_code' => '19406'
		));

		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getShippingAddress'))
			->getMock();

		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('isQuantityUpdateRequired', 'isDetailsUpdateRequired'))
			->getMock();
		$helper = $this->getHelperMock(
			'eb2cinventory/quote',
			array('rollbackAllocation')
		);
		$observer = $this->getModelMock(
			'eb2cinventory/observer',
			array('_initInventoryCheck', '_updateQuantity', '_updateDetails')
		);

		$this->replaceByMock('helper', 'eb2cinventory/quote', $helper);
		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$session
			->expects($this->any())
			->method('isQuantityUpdateRequired')
			->will($this->returnValue($isQtyUpdated));
		$session
			->expects($this->any())
			->method('isDetailsUpdateRequired')
			->will($this->returnValue($isDetailsUpdated));

		// if quantity or details are being checked/updated, rollback any existing allocations
		// as something has changed so the allocation would no longer be any good
		if ($isQtyUpdated || $isDetailsUpdated) {
			$quote->expects($this->once())
				->method('getShippingAddress')
				->will($this->returnValue($address));

			$helperMock = $this->getHelperMockBuilder('eb2cinventory/data')
				->disableOriginalConstructor()
				->setMethods(array('hasRequiredShippingDetail'))
				->getMock();
			$helperMock->expects($this->once())
				->method('hasRequiredShippingDetail')
				->with($this->identicalTo($address))
				->will($this->returnValue(true));
			$this->replaceByMock('helper', 'eb2cinventory', $helperMock);

			$helper
				->expects($this->once())
				->method('rollbackAllocation')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
		} else {
			$quote->expects($this->never())
				->method('getShippingAddress');

			$helper
				->expects($this->never())
				->method('rollbackAllocation');
		}

		if ($isQtyUpdated) {
			$observer
				->expects($this->once())
				->method('_updateQuantity')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
		} else {
			$observer
				->expects($this->never())
				->method('_updateQuantity');
		}
		if ($isDetailsUpdated) {
			$observer
				->expects($this->once())
				->method('_updateDetails')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
		} else {
			$observer
				->expects($this->never())
				->method('_updateDetails');
		}

		$this->assertSame($observer, $observer->checkInventory($this->_mockObserverWithQuote($quote)));
	}
	/**
	 * Data provider for the testUpdateQuantity test. Provides the expected response
	 * from the inventory service - simulate a non-empty and an empty response.
	 * @return array Args array containing a string to simulate a non-empty or empty response.
	 */
	public function providerUpdateResponse()
	{
		return array(array('<MockResponse/>'), array(''));
	}
	/**
	 * Test updating a quote with a quantity request. Method should make the request and update the
	 * quote via the observer's _makeRequestAndUpdate method, passing along the request object,
	 * quote and changes in the quote. If the service call returned a usable response, the session
	 * data for quote quantities should be updated.
	 * @param  string $response Response from the inventory quantity service.
	 * @dataProvider providerUpdateResponse
	 */
	public function testUpdateQuantity($response)
	{
		$quote = $this->getModelMock('sales/quote');
		$qtyRequest = $this->getModelMock('eb2cinventory/quantity');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('updateQuoteInventory', 'resetQuantityUpdateRequired'))
			->getMock();
		$observer = $this->getModelMock('eb2cinventory/observer', array('_makeRequestAndUpdate'));

		$this->replaceByMock('model', 'eb2cinventory/quantity', $qtyRequest);
		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$observer
			->expects($this->once())
			->method('_makeRequestAndUpdate')
			->with($this->identicalTo($qtyRequest), $this->identicalTo($quote))
			->will($this->returnValue($response));

		// when the service response is not empty, update session data
		if ($response) {
			$session
				->expects($this->once())
				->method('updateQuoteInventory')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
			$session
				->expects($this->once())
				->method('resetQuantityUpdateRequired')
				->will($this->returnSelf());
		} else {
			$session->expects($this->never())->method('updateQuoteInventory');
			$session->expects($this->never())->method('resetQuantityUpdateRequired');
		}

		$method = $this->_reflectMethod($observer, '_updateQuantity');
		$this->assertSame($observer, $method->invoke($observer, $quote));
	}
	/**
	 * Test updating a quote with a inventory details request. Method should make the request and update
	 * the quote via the observer's _makeRequestAndUpdate method, passing along the request object,
	 * quote and changes to the quote. If the service returns a usable response, the session data
	 * for quantities and details should be updated.
	 * @param  string $response Response from inventory service
	 * @dataProvider providerUpdateResponse
	 */
	public function testUpdateDetails($response)
	{
		$quote = $this->getModelMock('sales/quote');
		$dtsRequest = $this->getModelMock('eb2cinventory/details');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('updateQuoteInventory', 'resetDetailsUpdateRequired'))
			->getMock();
		$observer = $this->getModelMock('eb2cinventory/observer', array('_makeRequestAndUpdate'));

		$this->replaceByMock('model', 'eb2cinventory/details', $dtsRequest);
		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$observer
			->expects($this->once())
			->method('_makeRequestAndUpdate')
			->with($this->identicalTo($dtsRequest), $this->identicalTo($quote))
			->will($this->returnValue($response));

		// when the service response is not empty, update session data
		if ($response) {
			$session
				->expects($this->once())
				->method('updateQuoteInventory')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
			$session
				->expects($this->once())
				->method('resetDetailsUpdateRequired')
				->will($this->returnSelf());
		} else {
			$session->expects($this->never())->method('updateQuoteInventory');
			$session->expects($this->never())->method('resetDetailsUpdateRequired');
		}

		$method = $this->_reflectMethod($observer, '_updateDetails');
		$this->assertSame($observer, $method->invoke($observer, $quote));
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

		$method = $this->_reflectMethod($observer, '_makeRequestAndUpdate');
		$this->assertSame($response , $method->invoke($observer, $request, $quote, $quoteDiff));
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
}
