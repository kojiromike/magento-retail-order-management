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

class EbayEnterprise_Order_Test_Helper_Search_MapTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	const SUMMARY_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummary';

	/** @var EbayEnterprise_Order_Helper_Search_Map */
	protected $_map;
	/** @var Mock_IOrderSummary */
	protected $_orderSummary;

	public function setUp()
	{
		parent::setUp();
		$this->_map = Mage::helper('ebayenterprise_order/search_map');
		$this->_orderSummary = $this->getMockBuilder(static::SUMMARY_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->setMethods(['getId', 'getOrderDate', 'getOrderTotal', 'getCancellable'])
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * Test that the helper method ebayenterprise_order/search_map::getUniqueValue()
	 * when invoked will return a unique string literal that start with 'OCS_'.
	 */
	public function testGetUniqueValue()
	{
		$this->assertStringStartsWith('OCS_', $this->_map->getUniqueValue());
	}

	/**
	 * Test that the helper method ebayenterprise_order/search_map::getStringValue()
	 * is invoked, it will be passed in an instance of type IOrderSummary as its first
	 * parameter and then a string literal getter as its second parameter. Then, we
	 * expect the method IOrderSummary::getId() to be invoked. The method
	 * ebayenterprise_order/search_map::getStringValue() will return the return
	 * value from calling IOrderSummary::getId().
	 */
	public function testGetStringValue()
	{
		$id = 'order-13838839299299';
		$getter = 'getId';
		$this->_orderSummary->expects($this->once())
			->method($getter)
			->will($this->returnValue($id));
		$this->assertSame($id, $this->_map->getStringValue($this->_orderSummary, $getter));
	}

	public function providerGetDatetimeValue()
	{
		return [
			[new DateTime('2015-05-14T20:32:33+00:00'), '2015-05-14T20:32:33+00:00'],
			[null, null],
			[new stdClass() , null],
		];
	}

	/**
	 * Test that the helper method ebayenterprise_order/search_map::getDatetimeValue()
	 * is invoked, it will be passed in an instance of type IOrderSummary as its first
	 * parameter and then a string literal getter as its second parameter. Then, we
	 * expect the method IOrderSummary::getOrderDate() to be invoked. when the return
	 * value from IOrderSummary::getOrderDate() method return an object of type DateTime
	 * then the string value in that DateTime object will be returned, otherwise, only
	 * null value will be returned.
	 *
	 * @param DateTime | stdClass | null
	 * @param string | null
	 * @dataProvider providerGetDatetimeValue
	 */
	public function testGetDatetimeValue($orderDate, $result)
	{
		$getter = 'getOrderDate';
		$this->_orderSummary->expects($this->once())
			->method($getter)
			->will($this->returnValue($orderDate));
		$this->assertSame($result, $this->_map->getDatetimeValue($this->_orderSummary, $getter));
	}

	public function providerGetFloatValue()
	{
		return [
			[7.65, 7.65],
			['10.87', 10.87],
			[null , 0.0],
		];
	}

	/**
	 * Test that the helper method ebayenterprise_order/search_map::getFloatValue()
	 * is invoked, it will be passed in an instance of type IOrderSummary as its first
	 * parameter and then a string literal getter as its second parameter. Then, we
	 * expect the method IOrderSummary::getOrderTotal() to be invoked. Finally,
	 * the method ebayenterprise_order/search_map::getFloatValue() will return a float value.
	 *
	 * @param string | float
	 * @param float
	 * @dataProvider providerGetFloatValue
	 */
	public function testGetFloatValue($orderTotal, $result)
	{
		$getter = 'getOrderTotal';
		$this->_orderSummary->expects($this->once())
			->method($getter)
			->will($this->returnValue($orderTotal));
		$this->assertSame($result, $this->_map->getFloatValue($this->_orderSummary, $getter));
	}

	public function providerGetBooleanValue()
	{
		return [
			['true', true],
			['false', false],
			[null , false],
		];
	}

	/**
	 * Test that the helper method ebayenterprise_order/search_map::getBooleanValue()
	 * is invoked, it will be passed in an instance of type IOrderSummary as its first
	 * parameter and then a string literal getter as its second parameter. Then, we
	 * expect the method IOrderSummary::getCancellable() to be invoked. Finally,
	 * the method ebayenterprise_order/search_map::getBooleanValue() will return a boolean value.
	 *
	 * @param string | bool
	 * @param bool
	 * @dataProvider providerGetBooleanValue
	 */
	public function testGetBooleanValue($isCancellable, $result)
	{
		$getter = 'getCancellable';
		$this->_orderSummary->expects($this->once())
			->method($getter)
			->will($this->returnValue($isCancellable));
		$this->assertSame($result, $this->_map->getBooleanValue($this->_orderSummary, $getter));
	}
}
