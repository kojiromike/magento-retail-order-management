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

use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Order_Test_Helper_Event_ShipmentTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var PayloadFactory $_payloadFactory */
	protected $_payloadFactory;
	/** @var OrderEvents\OrderShipped $_payload */
	protected $_payload;
	/** @var EbayEnterprise_Order_Helper_Event_Shipment $_shipmentHelper */
	protected $_shipmentHelper;

	public function setUp()
	{
		parent::setUp();
		$this->_payloadFactory = new PayloadFactory();
		$this->_payload = $this->_payloadFactory->buildPayload('\eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\OrderShipped');
		$this->_payload->deserialize(file_get_contents(__DIR__ . '/ShipmentTest/fixtures/OrderShipped.xml'));

		// suppressing the real session from starting
		$session = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);

		$this->_shipmentHelper = Mage::helper('ebayenterprise_order/event_shipment');
	}

	/**
	 * Add items to a passed in order object.
	 * @param  Mage_Sales_Model_Order $order
	 * @param  array $itemData
	 * @param  string $itemColumn
	 * @param  string $itemDataKey
	 * @param  string $itemIdKey
	 * @return self
	 */
	protected function _addItemsToOrder(Mage_Sales_Model_Order $order, array $itemData, $itemColumn, $itemDataKey, $itemIdKey)
	{
		foreach ($itemData as $data) {
			// see Mage_Sales_Model_Order::addItem method
			// when a sales/order_item::getId exists it won't add the item to
			// the collection.
			$item = Mage::getModel('sales/order_item', [$itemColumn => $data[$itemDataKey]]);
			$order->addItem($item);
			$item->setItemId($data[$itemIdKey]);
		}
		return $this;
	}
	/**
	 * Testing 'EbayEnterprise_Order_Helper_Event_Shipment::_buildShipmentQtys'
	 * method, passing a known array of shipment data and a known
	 * ‘sales/order_item_collection’ object, then expects it to return an array
	 * containing keys of ‘sales/order_item’ entity id map to the value from the
	 * shipment data array key 'quantity'.
	 * @param array $itemData
	 * @param array $expected
	 * @dataProvider dataProvider
	 */
	public function testBuildShipmentQtys(array $itemData, array $expected)
	{
		$collection = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		foreach ($itemData as $data) {
			$collection->addItem(Mage::getModel('sales/order_item', $data));
		}
		$actual = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$this->_shipmentHelper, '_buildShipmentQtys', [$this->_payload->getOrderItems(), $collection]
		);
		$this->assertSame($expected, $actual);
	}
	/**
	 * Testing 'EbayEnterprise_Order_Helper_Event_Shipment::_addShipmentToOrder'
	 * method, passing a known array of shipment data as parameter and expect an
	 * array with shipment tracking data to be returned.
	 * @param array $qtys
	 * @param array $orderData
	 * @param array $expected
	 * @dataProvider dataProvider
	 */
	public function testAddShipmentToOrder(array $qtys, array $orderData, array $expected)
	{
		// This is a hack because yaml is converting float value to string.
		$expected['total_qty'] = (float) $expected['total_qty'];
		$order = Mage::getModel('sales/order', $orderData['order']);
		$this->_addItemsToOrder($order, $orderData['order_items'], 'qty_ordered', 'qty', 'id');
		$shipment = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$this->_shipmentHelper, '_addShipmentToOrder', [$qtys, $order]
		);
		$actual = $shipment->getData();
		$this->assertSame($expected, $actual);
	}
	/**
	 * Testing 'EbayEnterprise_Order_Helper_Event_Shipment::_addTrackingToShipment'
	 * method, passing a known array of shipment data as parameter and a known
	 * sales/order_shipment object, then proceed to test data in the collection
	 * of shipment tracking match the expected data.
	 * @param array $dataForShipment
	 * @param array $expected
	 * @dataProvider dataProvider
	 */
	public function testAddTrackingToShipment(array $dataForShipment, array $expected)
	{
		$shipment = Mage::getModel('sales/order_shipment', $dataForShipment);
		$this->assertSame($this->_shipmentHelper, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$this->_shipmentHelper, '_addTrackingToShipment', [$this->_payload->getOrderItems(), $shipment]
		));
		$trackData = $shipment->getTracksCollection()->toArray();
		$actual = $trackData['items'];
		$this->assertSame($expected, $actual);
	}
	/**
	 * Testing 'EbayEnterprise_Order_Helper_Event_Shipment::process' method
	 * taking a sales/order instance and an array of shipment data, then expects
	 * the save method on the 'core/resource_transaction' model to be called once in order
	 * not to save the shipment data to the database.
	 * @param array $itemData
	 * @param bool $isException Flag for the mock save method to throw or not throw exception
	 * @dataProvider dataProvider
	 */
	public function testProcess(array $itemData, $isException)
	{
		$this->_payload->deserialize(file_get_contents(__DIR__ . '/ShipmentTest/fixtures/Process-OrderShipped.xml'));
		$order = Mage::getModel('sales/order');
		$this->_addItemsToOrder($order, $itemData, 'sku', 'sku', 'item_id');
		$exceptionMsg = 'Simulating throwing exception when saving shipment.';
		$transaction = $this->getModelMock('core/resource_transaction', ['save']);
		$transaction->expects($this->once())
			->method('save')
			->will($isException ? $this->throwException(Mage::exception('Mage_Core', $exceptionMsg)) : $this->returnSelf());
		$this->replaceByMock('model', 'core/resource_transaction', $transaction);

		$this->assertSame($this->_shipmentHelper, $this->_shipmentHelper->process($order, $this->_payload));
	}
}
