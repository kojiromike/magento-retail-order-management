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

class EbayEnterprise_Eb2cOrder_Test_Helper_Event_ShipmentTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
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
			$item = Mage::getModel('sales/order_item', array($itemColumn => $data[$itemDataKey]));
			$order->addItem($item);
			$item->setItemId($data[$itemIdKey]);
		}
		return $this;
	}
	/**
	 * Testing ‘EbayEnterprise_Eb2cOrder_Helper_Event_Shipment::extractShipmentData’
	 * method, passing a string of XML contents as parameter and expect an array
	 * with shipment data extracted.
	 * @param string $file
	 * @param array $expected
	 * @dataProvider dataProvider
	 */
	public function testExtractShipmentData($file, $expected)
	{
		$xml = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $file, true);
		$actual = Mage::helper('eb2corder/event_shipment')->extractShipmentData($xml);
		$this->assertSame($expected, $actual);
	}
	/**
	 * Testing 'EbayEnterprise_Eb2cOrder_Helper_Event_Shipment::_buildShipmentQtys'
	 * method, passing a known array of shipment data and a known
	 * ‘sales/order_item_collection’ object, then expects it to return an array
	 * containing keys of ‘sales/order_item’ entity id map to the value from the
	 * shipment data array key 'quantity'.
	 * @param array $shipmentData
	 * @param array $itemData
	 * @param array $expected
	 * @dataProvider dataProvider
	 */
	public function testBuildShipmentQtys(array $shipmentData, array $itemData, array $expected)
	{
		$collection = Mage::helper('eb2ccore')->getNewVarienDataCollection();
		foreach ($itemData as $data) {
			$collection->addItem(Mage::getModel('sales/order_item', $data));
		}
		$shpHlpr = Mage::helper('eb2corder/event_shipment');
		$actual = EcomDev_Utils_Reflection::invokeRestrictedMethod($shpHlpr, '_buildShipmentQtys', array($shipmentData, $collection));
		$this->assertSame($expected, $actual);
	}
	/**
	 * Testing 'EbayEnterprise_Eb2cOrder_Helper_Event_Shipment::_addShipmentToOrder'
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
		$shpHlpr = Mage::helper('eb2corder/event_shipment');
		$shipment = EcomDev_Utils_Reflection::invokeRestrictedMethod($shpHlpr, '_addShipmentToOrder', array($qtys, $order));
		$actual = $shipment->getData();
		$this->assertSame($expected, $actual);
	}
	/**
	 * Testing 'EbayEnterprise_Eb2cOrder_Helper_Event_Shipment::_addTrackingToShipment'
	 * method, passing a known array of shipment data as parameter and a known
	 * sales/order_shipment object, then proceed to test data in the collection
	 * of shipment tracking match the expected data.
	 * @param array $shipmentData
	 * @param array $dataForShipment
	 * @param array $expected
	 * @dataProvider dataProvider
	 */
	public function testAddTrackingToShipment(array $shipmentData, array $dataForShipment, array $expected)
	{
		$shipment = Mage::getModel('sales/order_shipment', $dataForShipment);
		$shpHlpr = Mage::helper('eb2corder/event_shipment');
		$this->assertSame($shpHlpr, EcomDev_Utils_Reflection::invokeRestrictedMethod($shpHlpr, '_addTrackingToShipment', array($shipmentData, $shipment)));
		$trackData = $shipment->getTracksCollection()->toArray();
		$actual = $trackData['items'];
		$this->assertSame($expected, $actual);
	}
	/**
	 * Testing 'EbayEnterprise_Eb2cOrder_Helper_Event_Shipment::process' method
	 * taking a sales/order instance and an array of shipment data, then expects
	 * the save method on the 'core/resource_transaction' model to be called once in order
	 * not to save the shipment data to the database.
	 * @param array $shipmentData
	 * @param array $itemData
	 * @param bool $isException Flag for the mock save method to throw or not throw exception
	 * @dataProvider dataProvider
	 */
	public function testProcess(array $shipmentData, array $itemData, $isException)
	{
		$order = Mage::getModel('sales/order');
		$this->_addItemsToOrder($order, $itemData, 'sku', 'sku', 'item_id');
		$exceptionMsg = 'Simulating throwing exception when saving shipment.';
		$transaction = $this->getModelMock('core/resource_transaction', array('save'));
		$transaction->expects($this->once())
			->method('save')
			->will($isException ? $this->throwException(Mage::exception('Mage_Core', $exceptionMsg)) : $this->returnSelf());
		$this->replaceByMock('model', 'core/resource_transaction', $transaction);

		$shpHlpr = Mage::helper('eb2corder/event_shipment');
		$this->assertSame($shpHlpr, $shpHlpr->process($order, $shipmentData));
	}
}
