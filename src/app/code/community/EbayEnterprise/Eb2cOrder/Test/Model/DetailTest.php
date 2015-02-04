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

class EbayEnterprise_Eb2cOrder_Test_Model_DetailTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Testing that the method EbayEnterprise_Eb2cOrder_Model_Detail::injectOrderDetail
	 * will return a clone of sales/order as eb2corder/detail_order with
	 * data inject from order detail response.
	 * @dataProvider dataProvider
	 */
	public function testInjectOrderDetail($orderDetailReply)
	{
		$sku = '45-MB04-BK-0';
		$productId = 16;
		$productMock = $this->getModelMock('catalog/product', array('getIdBySku'));
		$productMock->expects($this->any())
			->method('getIdBySku')
			->with($this->identicalTo($sku))
			->will($this->returnValue($productId));
		$this->replaceByMock('model', 'catalog/product', $productMock);

		$orderId = '1007800093822';
		$cacheResponse = '';

		$detailHelper = $this->getHelperMock('eb2corder/detail', array('getCachedOrderDetailResponse'));
		$detailHelper->expects($this->once())
			->method('getCachedOrderDetailResponse')
			->with($this->identicalTo($orderId))
			->will($this->returnValue($cacheResponse));
		$this->replaceByMock('helper', 'eb2corder/detail', $detailHelper);

		$order = Mage::getModel('sales/order', array('real_order_id' => $orderId));

		$api = $this->getModelMock('eb2ccore/api', array('request'));
		$api->expects($this->once())
			->method('request')
			->will($this->returnValue($orderDetailReply));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$cloneOrder = Mage::getModel('eb2corder/detail')->injectOrderDetail($order);

		$orderData = array(
			'real_order_id' => '1007800093822',
			'customer_email' => 'rgabriel@ebay.com',
			'customer_lastname' => 'Gabriel',
			'customer_firstname' => 'Reginald',
			'created_at' => '2014-07-11T17:04:31+00:00',
			'order_currency_code' => 'USD',
			'locale' => 'en_US',
			'status' => 'new',
			'tax_amount' => 2.28,
			'subtotal' => 32.99,
			'shipping_amount' => 5.0,
			'gift_cards' => 'a:0:{}',
		);
		$shippingAddressData = array(
			'id' => 'dest_1',
			'lastname' => 'Gabriel',
			'firstname' => 'Reginald',
			'street1' => '630 Allendale Rd',
			'city' => 'King of Prussia',
			'region' => 'PA',
			'country' => 'US',
			'postcode' => '19406-1418',
			'street' => '630 Allendale Rd',
			'name' => 'Reginald Gabriel',
			'address_type' => 'shipping',
			'charge_type' => 'FLATRATE',
		);

		$billingAddressData = array(
			'id' => 'billing_1',
			'lastname' => 'Gabriel',
			'firstname' => 'Reginald',
			'street1' => '630 Allendale Rd',
			'city' => 'King of Prussia',
			'region' => 'PA',
			'country' => 'US',
			'postcode' => '19406-1418',
			'street' => '630 Allendale Rd',
			'name' => 'Reginald Gabriel',
			'address_type' => 'billing',
		);

		$items = array(
			array(
				'sku' => $sku,
				'ref_id' => 'item2014071117045157060772',
				'qty_ordered' => 1.0,
				'qty_shipped' => 1.0,
				'name' => 'Shoulder Pack',
				'price' => 32.99,
				'row_total' => 32.99,
				'product_id' => $productId,
				'order_id' => null
			)
		);

		$payments = array(
			array(
				'tender_type' => 'VC',
				'account_unique_id' => '411111To9dIa1111',
				'account_id_is_token' => true,
				'amount' => 0.0,
				'payment_type_name' => 'CreditCard',
				'method' => 'pbridge_eb2cpayment_cc',
				'cc_last4' => '1111',
				'cc_type' => 'VC',
			)
		);

		$shippedItems = array(
			array(
				'sku' => $sku,
				'ref_id' => 'item2014071117045157060772',
				'qty_ordered' => 1.0,
				'qty_shipped' => 1.0,
				'name' => 'Shoulder Pack',
				'price' => 32.99,
				'row_total' => 32.99,
				'product_id' => $productId,
				'order_id' => null,
				'qty' => 1.0
			)
		);

		$tracks = array(
			array(
				'number' => '1998833',
				'track_number' => '1998833'
			)
		);

		// Asserting the proper order data are extracted
		$data = $cloneOrder->getData();
		$orderData['created_at'] = $data['created_at'];
		$this->assertSame($orderData, $data);

		// Asserting the proper shipping data are extracted
		$this->assertSame($shippingAddressData, $cloneOrder->getShippingAddress()->getData());

		// Asserting the proper billing data are extracted
		$this->assertSame($billingAddressData, $cloneOrder->getBillingAddress()->getData());

		// Asserting the proper order item data are extracted
		foreach ($cloneOrder->getAllItems() as $index => $item) {
			$this->assertSame($items[$index], $item->getData());
		}

		// Asserting the proper payment data are extracted
		foreach ($cloneOrder->getAllPayments() as $index => $payment) {
			$this->assertSame($payments[$index], $payment->getData());
		}

		// Asserting the proper shipped items data are extracted
		foreach ($cloneOrder->getShipmentsCollection() as $shipment) {
			foreach ($shipment->getItemsCollection() as $itemIndex => $shipmentItem) {
				$this->assertSame($shippedItems[$itemIndex], $shipmentItem->getData());
			}
			foreach ($shipment->getTracksCollection() as $trackIndex => $shipmentTrack) {
				$this->assertSame($tracks[$trackIndex], $shipmentTrack->getData());
			}
		}

		// Asserting that there's no order history data
		$this->assertSame(0, $cloneOrder->getStatusHistoryCollection()->count());
	}
	/**
	 * @see self::testInjectOrderDetail, however, the turn response will
	 * be empty and expect an exception to be thrown
	 * @dataProvider dataProvider
	 * @expectedException EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound
	 */
	public function testInjectOrderDetailEmptyResponseThrowException($orderDetailReply)
	{
		$orderId = '1007800093822';
		$cacheResponse = '';

		$detailHelper = $this->getHelperMock('eb2corder/detail', array('getCachedOrderDetailResponse'));
		$detailHelper->expects($this->once())
			->method('getCachedOrderDetailResponse')
			->with($this->identicalTo($orderId))
			->will($this->returnValue($cacheResponse));
		$this->replaceByMock('helper', 'eb2corder/detail', $detailHelper);

		$order = Mage::getModel('sales/order', array('real_order_id' => $orderId));

		$api = $this->getModelMock('eb2ccore/api', array('request'));
		$api->expects($this->once())
			->method('request')
			->will($this->returnValue($orderDetailReply));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		Mage::getModel('eb2corder/detail')->injectOrderDetail($order);
	}
	/**
	 * Testing that when the method EbayEnterprise_Eb2cOrder_Model_Detail::_requestOrderDetail
	 * and find a cache response base on the passed in order id it will return the cache
	 * response.
	 */
	public function testRequestOrderDetail()
	{
		$orderId = '00007300083871';
		$cacheResponse = '<OrderDetailResponse />';

		$detailHelper = $this->getHelperMock('eb2corder/detail', array('getCachedOrderDetailResponse'));
		$detailHelper->expects($this->once())
			->method('getCachedOrderDetailResponse')
			->with($this->identicalTo($orderId))
			->will($this->returnValue($cacheResponse));
		$this->replaceByMock('helper', 'eb2corder/detail', $detailHelper);

		$detail = Mage::getModel('eb2corder/detail');
		$this->assertSame($cacheResponse, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$detail, '_requestOrderDetail', array($orderId)
		));
	}
	/**
	 * Testing the method EbayEnterprise_Eb2cOrder_Model_Detail::_determineShippingAddress
	 * when a billing address is found it will be skip.
	 * response.
	 */
	public function testDetermineShippingAddress()
	{
		$destinationId = 'dest_1';
		$addressData = array(
			'id' => $destinationId,
			'address_type' => Mage_Customer_Model_Address_Abstract::TYPE_BILLING
		);
		$cloneOrder = Mage::getModel('eb2corder/detail_order');
		$cloneOrder->getAddressesCollection()
			->addItem(Mage::getModel('eb2corder/detail_address', $addressData)
			->setOrder($cloneOrder));

		$xmlns = 'http://example.com/schema/1.0';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			'<OrderDetailResponse xmlns="' . $xmlns . '">
				<Order>
					<ShipGroups>
						<ShipGroup id="shipGroup_1" chargeType="FLATRATE">
							<DestinationTarget ref="' . $destinationId . '"/>
							<OrderItems>
								<Item ref="item2014071117045157060772"/>
							</OrderItems>
						</ShipGroup>
					</ShipGroups>
				</Order>
			</OrderDetailResponse>'
		);
		$xpath = Mage::helper('eb2ccore')->getNewDomXPath($doc);
		$xpath->registerNamespace('a', $xmlns);

		$detail = Mage::getModel('eb2corder/detail');
		$this->assertSame($detail, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$detail, '_determineShippingAddress', array($xpath, $cloneOrder)
		));
	}
}
