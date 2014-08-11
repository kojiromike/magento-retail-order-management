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

class EbayEnterprise_Eb2cOrder_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	// @var Varien_Event
	public $event;
	// @var Varien_Event_Observer Includes the $event
	public $observer;
	// @var EbayEnterprise_Eb2cOrder_Model_Detail_Order order injected w/ OMS data
	public $detailOrder;
	/**
	 * Mock eb2corder/detail model scripted to take an order and return the
	 * order with OMS data injected (self::$detailOrder)
	 * @var EbayEnterprise_Eb2cOrder_Model_Detail (mock)
	 */
	public $orderDetail;
	// @var Mage_Sales_Model_Order
	public $order;
	// @var EbayEnterprise_Eb2cOrder_Model_Resource_Summary_Order_Collection (stub)
	public $orderCollection;
	// @var Varien_Data_Collection
	public $shipmentCollection;
	// @var Mock_EbayEnterprise_Eb2cOrder_Model_Detail_Shipment
	public $shipment;
	public $shipmentId = 'theshipmentid';
	/**
	 * Mock search request scripted to return order summary data
	 * @var EbayEnterprise_Eb2cOrder_Model_Customer_Order_Search (mock)
	 */
	public $orderSearch;
	// @var array Varien_Objects of order summary data
	public $summaryData;
	// @var int Order entity id
	public $orderEntityId = 5;
	// @var string Order increment id
	public $orderIncId = '100000123';
	// @var string Creation date of the order
	public $orderDate = '2014-01-01T12:00:00+05:00';
	// @var string Order status in OMS
	public $omsStatus = 'Scheduled';
	// @var float Order total in OMS
	public $orderTotal = 99.99;
	// @var string Magento order status
	public $orderStatus = 'Processing';
	// @var string Id of the customer the collection is for
	public $customerId = '0001';
	/**
	 * Mock eb2corder/data helper, scripted to translate OMS status => Mangeto status
	 * @var EbayEnterprise_Eb2cOrder_Helper_Data (stub)
	 */
	public $orderHelper;

	// @var Mage_Core_Controller_Request_Http
	public $originalRequest;

	/**
	 * Set up dependent systems for testing
	 */
	public function setUp()
	{
		parent::setUp();

		// create empty event and event observer objects to be populated w/ data
		// and handed off to observer methods
		$this->event = new Varien_Event();
		$this->observer = new Varien_Event_Observer(array('event' => $this->event));

		// it is expected that some order will be in the Mage::registry
		Mage::register('current_order', Mage::getModel('sales/order'));

		// setup a shipment collection for the order so we can get the shipment to register.
		$this->shipmentCollection = new Varien_Data_Collection();
		$this->shipment = $this->getModelMockBuilder('eb2corder/detail_shipment')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'getIdFieldName'))
			->getMock();
		$this->shipment->expects($this->any())->method('getId')->will($this->returnValue($this->shipmentId));
		// $this->shipment->expects($this->any())->method('getIdFieldName')->will($this->returnValue('id'));
		$this->shipmentCollection->addItem($this->shipment);

		// create the detail order, really just need an object of this type to get
		// scripted as the response from the eb2corder/detail model's
		// injectOrderData method
		$this->detailOrder = $this->getModelMock('eb2corder/detail_order', array('getShipmentsCollection'));
		$this->detailOrder->expects($this->any())
			->method('getShipmentsCollection')
			->will($this->returnValue($this->shipmentCollection));

		// mock and script an eb2corder/detail model that, given an order, will
		// return an eb2corder/detail_order
		$this->orderDetail = $this->getModelMock('eb2corder/detail');
		// given the object currently in the Mage::registry as 'current_order',
		// will return a detail order object
		$this->orderDetail->expects($this->any())
			->method('injectOrderDetail')
			->with($this->identicalTo(Mage::registry('current_order')))
			->will($this->returnValue($this->detailOrder));

		// set up an order for the collection
		$this->order = Mage::getModel(
			'sales/order',
			array('entity_id' => $this->orderEntityId, 'increment_id' => $this->orderIncId)
		);
		// Create the mock summary order collection and prevent it from doing any actual DB loads
		$this->orderCollection = $this->getResourceModelMock('eb2corder/summary_order_collection', array('isLoaded'));
		$this->orderCollection->expects($this->any())
			->method('isLoaded')
			->will($this->returnValue(true));
		$this->orderCollection->addItem($this->order);
		$this->orderCollection->setCustomerId($this->customerId);

		// the expected, parsed response from the order summary request
		$this->summaryData = array(
			$this->orderIncId => new Varien_Object(array(
				'id' => 'oms-order-id',
				'status' => $this->omsStatus,
				'order_date' => $this->orderDate,
				'order_total' => $this->orderTotal
			)),
		);
		// mock out and script the customer order search
		$this->orderSearch = $this->getModelMock('eb2corder/customer_order_search', array('getOrderSummaryData'));
		$this->orderSearch->expects($this->any())
			->method('getOrderSummaryData')
			->will($this->returnValueMap(array(
				array($this->customerId, '', $this->summaryData),
			)));

		$this->orderHelper = $this->getHelperMock('eb2corder/data', array('mapEb2cOrderStatusToMage'));
		$this->orderHelper->expects($this->any())
			->method('mapEb2cOrderStatusToMage')
			->will($this->returnValueMap(array(
				array($this->omsStatus, $this->orderStatus),
			)));

		// replace the request to simulate having the shipment id in the url
		$this->originalRequest = EcomDev_Utils_Reflection::getRestrictedPropertyValue(
			Mage::app(),
			'_request'
		);
		$request = clone($this->originalRequest);
		$request->setParam('shipment_id', $this->shipmentId);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(Mage::app(), '_request', $request);
	}
	/**
	 * replace the original request and unregister the current order and current shipment
	 * that were set by the setup or tests.
	 */
	public function tearDown()
	{
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			Mage::app(),
			'_request',
			$this->originalRequest
		);
		Mage::unregister('current_order');
		Mage::unregister('current_shipment');
		parent::tearDown();
	}
	/**
	 * store the order from the detail request in the registry
	 */
	public function testReplaceCurrentOrder()
	{
		// replace the eb2corder/detail model to simulate passing the order through
		// to be injected with OMS data
		$this->replaceByMock('model', 'eb2corder/detail', $this->orderDetail);
		// the observer isn't actually referenced or needed at all, order gets
		// updated in the Mage::registry so the observer and event can be empty
		Mage::getModel('eb2corder/observer')->replaceCurrentOrder($this->observer);
		$order = Mage::registry('current_order');
		$this->assertSame($this->detailOrder, $order);
	}
	/**
	 * Test updating the eb2corder/summary_order_collection orders with summary
	 * data after loading the collection.
	 */
	public function testUpdateOrdersWithSummaryData()
	{
		// prepare the event with the collecton to be processed
		$this->event->setData(array('order_collection' => $this->orderCollection));

		// replace the order search with the mock
		$this->replaceByMock('model', 'eb2corder/customer_order_search', $this->orderSearch);
		// replace the eb2corder helper to mock out translating OMS statuses to Magento statuses
		$this->replaceByMock('helper', 'eb2corder', $this->orderHelper);

		Mage::getModel('eb2corder/observer')->updateOrdersWithSummaryData($this->observer);

		// make sure our order was modified with the summary data
		$this->assertSame($this->orderDate, $this->order->getCreatedAt());
		$this->assertSame($this->orderStatus, $this->order->getStatus());
		$this->assertSame($this->orderTotal, $this->order->getGrandTotal());
	}
	/**
	 * @see self::testReplaceCurrentOrder, this time we are testing for
	 * when an exception is thrown.
	 * store the order from the detail request in the registry
	 */
	public function testReplaceCurrentOrderCatchException()
	{
		$path = 'sales/guest/form';
		$fullUrl = 'http://example.com/' . $path;
		$url = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$url->expects($this->once())
			->method('getUrl')
			->with($this->identicalTo($path), $this->identicalTo(array()))
			->will($this->returnValue($fullUrl));
		$this->replaceByMock('model', 'core/url', $url);

		$message = 'Order not found.';
		$session = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$session->expects($this->once())
			->method('addError')
			->with($this->identicalTo($message))
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'core/session', $session);

		$orderDetail = $this->getModelMock('eb2corder/detail', array('injectOrderDetail'));
		$orderDetail->expects($this->once())
			->method('injectOrderDetail')
			->will($this->throwException(
				new EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound($message)
			));
		$this->replaceByMock('model', 'eb2corder/detail', $orderDetail);

		$observer = new Varien_Event_Observer(array('event' => new Varien_Event(array(
			'name' => 'controller_action_layout_render_before_sales_guest_view'
		))));

		Mage::getModel('eb2corder/observer')->replaceCurrentOrder($observer);
	}
	/**
	 * Test that the method EbayEnterprise_Eb2cOrder_Model_Observer::_getRedirectUrl
	 * will return the base url when the given event name is empty
	 */
	public function testGetRedirectUrl()
	{
		$baseUrl = 'http://example.com/';
		$eventName = '';

		$url = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$url->expects($this->once())
			->method('getUrl')
			->with($this->identicalTo(''), $this->identicalTo(array()))
			->will($this->returnValue($baseUrl));
		$this->replaceByMock('model', 'core/url', $url);

		$observer = Mage::getModel('eb2corder/observer');
		$this->assertSame($baseUrl, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$observer, '_getRedirectUrl', array($eventName)
		));
	}
	/**
	 * verify the shipment is stored in the registry
	 */
	public function testPrepareForPrintShipment()
	{
		$observerModel = $this->getModelMock('eb2corder/observer', array('replaceCurrentOrder'));
		$order = $this->detailOrder;
		// emulate the expected side-effect of the replaceCurrentOrder method.
		$observerModel->expects($this->once())
			->method('replaceCurrentOrder')
			->will($this->returnCallback(
				function () use ($order) {
					Mage::unregister('current_order');
					Mage::register('current_order', $order);
				}));
		$observerModel->prepareForPrintShipment($this->observer);
		// ensure the shipment model we expect has been stored in the registry.
		$this->assertSame($this->shipment, Mage::registry('current_shipment'));
	}
	/**
	 * Test that EbayEnterprise_Eb2cOrder_Model_Observer::updateBackOrderStatus
	 * method when invoked will sets the given order state and status accordingly.
	 * @param int $id
	 * @param bool $isException flag to determine when to mock the sales/order::save
	 *        method to throw an exception
	 * @dataProvider dataProvider
	 */
	public function testUpdateBackOrderStatus($id, $isException)
	{
		$message = file_get_contents(__DIR__ . '/ObserverTest/fixtures/OrderBackordered.xml', true);
		$state = Mage_Sales_Model_Order::STATE_HOLDED;
		$status = 'holded';

		$exceptionMsg = 'Simulate Backordered Status Fail to save order';
		$orderMock = $this->getModelMock('sales/order', array('save'));
		$orderMock->expects($this->any())
			->method('save')
			->will(
				$isException ? $this->throwException(Mage::exception('Mage_Core', $exceptionMsg)) : $this->returnSelf()
			);
		$orderMock->setId($id);

		$collection = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$collection->addItem($orderMock);

		$helperMock = $this->getHelperMock('eb2corder/data', array(
			'getConfigModel', 'getOrderCollectionByIncrementIds'
		));
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'eventOrderStatusBackorder' => $status
			))));
		$helperMock->expects($this->any())
			->method('getOrderCollectionByIncrementIds')
			->will($this->returnValue($collection));
		$this->replaceByMock('helper', 'eb2corder', $helperMock);

		$eventObserver = new Varien_Event_Observer(
			array('event' => new Varien_Event(
				array('message' => $message)
			))
		);

		$observer = Mage::getModel('eb2corder/observer');
		$this->assertSame($observer, $observer->updateBackOrderStatus($eventObserver));

		if (!$isException && $id) {
			// Asserting that the order state has been set to holded.
			$this->assertSame($state, $orderMock->getState());
			// Asserting that the order status has been set to backordered status configured value.
			$this->assertSame($status, $orderMock->getStatus());
		}
	}
	/**
	 * Test that EbayEnterprise_Eb2cOrder_Model_Observer::updateRejectedStatus
	 * method when invoked will sets the given order state and status accordingly.
	 * @param int $id
	 * @param bool $isException flag to determine when to mock the sales/order::save
	 *        method to throw an exception
	 * @dataProvider dataProvider
	 */
	public function testUpdateRejectedStatus($id, $isException)
	{
		$message = file_get_contents(__DIR__ . '/ObserverTest/fixtures/OrderRejected.xml', true);
		$state = Mage_Sales_Model_Order::STATE_CANCELED;
		$status = 'canceled';

		$exceptionMsg = 'Simulate Rejected Status Fail to save order';
		$orderMock = $this->getModelMock('sales/order', array('save'));
		$orderMock->expects($this->any())
			->method('save')
			->will(
				$isException ? $this->throwException(Mage::exception('Mage_Core', $exceptionMsg)) : $this->returnSelf()
			);
		$orderMock->setId($id);

		$collection = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		$collection->addItem($orderMock);

		$helperMock = $this->getHelperMock('eb2corder/data', array(
			'getConfigModel', 'getOrderCollectionByIncrementIds'
		));
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'eventOrderStatusRejected' => $status
			))));
		$helperMock->expects($this->any())
			->method('getOrderCollectionByIncrementIds')
			->will($this->returnValue($collection));
		$this->replaceByMock('helper', 'eb2corder', $helperMock);

		$eventObserver = new Varien_Event_Observer(
			array('event' => new Varien_Event(
				array('message' => $message)
			))
		);

		$observer = Mage::getModel('eb2corder/observer');
		$this->assertSame($observer, $observer->updateRejectedStatus($eventObserver));

		if (!$isException && $id) {
			// Asserting that the order state has been set to canceled.
			$this->assertSame($state, $orderMock->getState());
			// Asserting that the order status has been set to rejected status configured value.
			$this->assertSame($status, $orderMock->getStatus());
		}
	}
	/**
	 * Test that the event 'ebayenterprise_order_event_back_order' is defined.
	 */
	public function testBackOrderEventIsDefined()
	{
		EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined(
			'global',
			'ebayenterprise_order_event_back_order',
			'eb2corder/observer',
			'updateBackOrderStatus'
		);
	}
	/**
	 * Test that the event 'ebayenterprise_order_event_rejected' is defined.
	 */
	public function testRejectedEventIsDefined()
	{
		EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined(
			'global',
			'ebayenterprise_order_event_rejected',
			'eb2corder/observer',
			'updateRejectedStatus'
		);
	}
}
