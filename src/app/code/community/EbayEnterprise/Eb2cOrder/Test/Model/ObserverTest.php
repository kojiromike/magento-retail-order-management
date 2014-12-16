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
	// @var EbayEnterprise_Eb2cOrder_Model_Resource_Summary_Order_Collection (stub)
	public $orderCollection2;
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

		// set up an order for the collection
		$this->order = $this->getModelMock('sales/order',
			array('save', 'cancel', 'setState', 'getState'),
			false,
			array(array('entity_id' => $this->orderEntityId, 'increment_id' => $this->orderIncId))
		);
		// Create the mock summary order collection and prevent it from doing any actual DB loads
		$this->orderCollection2 = $this->getResourceModelMock('sales/order_collection', array('isLoaded'));
		$this->orderCollection2->expects($this->any())
			->method('isLoaded')
			->will($this->returnValue(true));
		$this->orderCollection2->addItem($this->order);

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
		$this->orderSearch
			->expects($this->any())
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
	 * Test that the event 'ebayenterprise_amqp_message_order_rejected' is defined.
	 */
	public function testRejectedEventIsDefined()
	{
		EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined(
			'global',
			'ebayenterprise_amqp_message_order_rejected',
			'eb2corder/observer',
			'processAmqpMessageOrderRejected'
		);
	}
	/**
	 * Verify the cancel event is configured.
	 */
	public function testCancelEventIsDefined()
	{
		EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined(
			'global',
			'ebayenterprise_order_event_cancel',
			'eb2corder/observer',
			'updateCanceledStatus'
		);
	}
}
