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

class EbayEnterprise_Order_Test_Block_Overrides_Order_RecentTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var EbayEnterprise_Order_Overrides_Block_Order_Recent */
	protected $_recent;

	public function setUp()
	{
		$this->_recent = $this->getBlockMockBuilder('ebayenterprise_orderoverrides/order_recent')
			->disableOriginalConstructor()
			->setMethods(['getUrl'])
			->getMock();
	}

	/**
	 * Create a block instance
	 *
	 * @param  string
	 * @return Mage_Core_Block_Abstract
	 */
	protected function _createBlock($class)
	{
		return Mage::app()->getLayout()->createBlock($class);
	}

	/**
	 * Test that the block method ebayenterprise_orderoverrides/order_recent::getOrders()
	 * is invoked, and it will first get the ROM order collection from the current registry
	 * if the ROM order collection is not found, it will begin to invoked the following method
	 * ebayenterprise_order/data::getCurCustomerOrders(), which will return an instance of type
	 * ebayenterprise_order/search_process_response_collection. This instance will then
	 * be registered in the registry. Finally, the block method
	 * ebayenterprise_orderoverrides/order_recent::getOrders() will return this instance of type
	 * ebayenterprise_order/search_process_response_collection.
	 */
	public function testGetOrders()
	{
		// Remove the ROM order collection from the registry.
		Mage::unregister('rom_order_collection');
		/** @var EbayEnterprise_Order_Model_Search_Process_Response_Collection */
		$collection = Mage::getModel('ebayenterprise_order/search_process_response_collection');

		/** @var EbayEnterprise_Order_Helper_Data */
		$orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['getCurCustomerOrders']);
		$orderHelper->expects($this->once())
			->method('getCurCustomerOrders')
			->will($this->returnValue($collection));

		EcomDev_Utils_Reflection::setRestrictedPropertyValues($this->_recent, [
			'_orderHelper' => $orderHelper,
		]);

		$this->assertSame($collection, $this->_recent->getOrders());
	}

	/**
	 * Test that the block method ebayenterprise_orderoverrides/order_recent::getMaxOrdersToShow()
	 * is invoked, and it will return its class constant EbayEnterprise_Order_Overrides_Block_Order_Recent::ORDERS_TO_SHOW
	 */
	public function testGetMaxOrdersToShow()
	{
		$this->assertSame(EbayEnterprise_Order_Overrides_Block_Order_Recent::ORDERS_TO_SHOW, $this->_recent->getMaxOrdersToShow());
	}

	/**
	 * Test that the block method ebayenterprise_orderoverrides/order_recent::getViewUrl()
	 * is invoked, and it will called the method ebayenterprise_orderoverrides/order_recent::getUrl()
	 * passing as first parameter the string literal path, and passing as second parameter an array
	 * with key 'order_id' mapped to string literal order id. The method
	 * ebayenterprise_orderoverrides/order_recent::getUrl() will return string URL with
	 * the passing path and the passing array key/value as path. Finally, the method
	 * ebayenterprise_orderoverrides/order_recent::getViewUrl() will return this URL string.
	 */
	public function testGetViewUrl()
	{
		/** @var string */
		$orderId = '1000009981121';
		/** @var string */
		$path = 'sales/order/romview';
		/** @var string */
		$url = "http://test.example.com/{$path}/order_id/{$orderId}";

		$this->_recent->expects($this->once())
			->method('getUrl')
			->with($this->identicalTo($path), $this->identicalTo(['order_id' => $orderId]))
			->will($this->returnValue($url));

		$this->assertSame($url, $this->_recent->getViewUrl($orderId));
	}

	/**
	 * Test that the block method ebayenterprise_orderoverrides/order_recent::getHelper()
	 * is invoked, and it will return the instance ebayenterprise_order/data that is set
	 * in the class property ebayenterprise_orderoverrides/order_recent::$_orderHelper.
	 */
	public function testGetHelper()
	{
		/** @var string */
		$helperClass = 'ebayenterprise_order';
		/** @var EbayEnterprise_Order_Helper_Data */
		$orderHelper = Mage::helper('ebayenterprise_order');

		EcomDev_Utils_Reflection::setRestrictedPropertyValues($this->_recent, [
			'_orderHelper' => $orderHelper,
		]);

		$this->assertSame($orderHelper, $this->_recent->getHelper($helperClass));
	}

	/**
	 * Test that the block method ebayenterprise_orderoverrides/order_recent::getCancelUrl()
	 * is invoked, and it will called the method ebayenterprise_orderoverrides/order_recent::getUrl()
	 * passing as first parameter the string literal path, and passing as second parameter an array
	 * with key 'order_id' mapped to string literal order id. The method
	 * ebayenterprise_orderoverrides/order_recent::getUrl() will return string URL with
	 * the passing path and the passing array key/value as path. Finally, the method
	 * ebayenterprise_orderoverrides/order_recent::getCancelUrl() will return this URL string.
	 */
	public function testGetCancelUrl()
	{
		/** @var string */
		$orderId = '1000009981121';
		/** @var string */
		$path = 'sales/order/romcancel';
		/** @var string */
		$url = "http://test.example.com/{$path}/order_id/{$orderId}";

		/** @var Mock_EbayEnterprise_Order_Overrides_Block_Order_Recent */
		$this->_recent->expects($this->once())
			->method('getUrl')
			->with($this->identicalTo($path), $this->identicalTo(['order_id' => $orderId]))
			->will($this->returnValue($url));

		$this->assertSame($url, $this->_recent->getCancelUrl($orderId));
	}

	/**
	 * @return array
	 */
	public function providerFormatPrice()
	{
		return [
			[10.99999, '<span class="price">$11.00</span>'],
			['10.99999', '<span class="price">$11.00</span>'],
			[null, '<span class="price">$0.00</span>'],
		];
	}

	/**
	 * Test that helper method ebayenterprise_order/data::formatPrice()
	 * when invoked will be passed in either string, float, and even null
	 * value and it will return an HTML price formatted string.
	 *
	 * @param string | null | float
	 * @dataProvider providerFormatPrice
	 */
	public function testFormatPrice($amount, $result)
	{
		/** @var Mage_Core_Helper_Data */
		$coreHelper = Mage::helper('core');
		/** @var Mage_Core_Model_Session */
		$session = $this->getModelMock('core/session', ['init']);
		$session->expects($this->any())
			// Mocking this method to prevent session from starting.
			->method('init')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'core/session', $session);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($this->_recent, [
			'_coreHelper' => $coreHelper,
		]);
		$this->assertSame($result, $this->_recent->formatPrice($amount));
	}
}
