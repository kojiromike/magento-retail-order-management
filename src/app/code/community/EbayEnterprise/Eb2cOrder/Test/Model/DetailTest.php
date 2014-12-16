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

class EbayEnterprise_Eb2cOrder_Test_Model_DetailTest extends EbayEnterprise_Eb2cCore_Test_Base
{
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
