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

class EbayEnterprise_Order_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = Mage::helper('ebayenterprise_order');
	}

	public function testGetOrderHistoryUrl()
	{
		$expectedPath = parse_url(Mage::getBaseUrl() . 'sales/guest/view/')['path'];
		$expectedParams = [
			'oar_billing_lastname' => 'Sibelius',
			'oar_email' => 'foo@bar.com',
			'oar_order_id' => '908123987143079814',
			'oar_type' => 'email',
		];
		$order = $this->getModelMockBuilder('sales/order')
			->setMethods(['getCustomerEmail', 'getCustomerLastname', 'getIncrementId'])
			->getMock();
		$order->expects($this->any())
			->method('getCustomerEmail')
			->will($this->returnValue($expectedParams['oar_email']));
		$order->expects($this->any())
			->method('getCustomerLastname')
			->will($this->returnValue($expectedParams['oar_billing_lastname']));
		$order->expects($this->any())
			->method('getIncrementId')
			->will($this->returnValue($expectedParams['oar_order_id']));
		$url = parse_url($this->_helper->getOrderHistoryUrl($order));
		$path = $url['path'];
		$query = [];
		parse_str($url['query'], $query);
		$this->assertSame($expectedPath, $path);
		$this->assertSame($expectedParams, $query);
	}

	/**
	 * @return array
	 */
	public function providerGetOrderCancelReason()
	{
		return [
			[$this->buildCoreConfigRegistry(['cancelReasonMap' => []]), []],
			[$this->buildCoreConfigRegistry(['cancelReasonMap' => "\n\t\t"]), null],
		];
	}

	/**
	 * Test that helper method ebayenterprise_order/data::_getOrderCancelReason()
	 * when invoked will called the helper method ebayenterprise_order/data::getConfigModel()
	 * which will return an instance of eb2ccore/config_registry. Then the magic property
	 * 'cancelReasonMap' will we tested if is an array the method
	 * ebayenterprise_order/data::_getOrderCancelReason() return that array otherwise it
	 * will return a null value.
	 * @param Mock_EbayEnterprise_Eb2cCore_Model_Config_Registry
	 * @param mixed
	 * @dataProvider providerGetOrderCancelReason
	 */
	public function testGetOrderCancelReason($config, $result)
	{
		$helper = $this->getHelperMock('ebayenterprise_order/data', ['getConfigModel']);
		$helper->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo(null))
			->will($this->returnValue($config));
		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$helper, '_getOrderCancelReason', []
		));
	}

	/**
	 * @return array
	 */
	public function providerHasOrderCancelReason()
	{
		return [
			[['reason_code_001' => 'Wrong Products'], true],
			[[], false],
			[null, false],
		];
	}

	/**
	 * Test that helper method ebayenterprise_order/data::hasOrderCancelReason()
	 * when invoked will called the helper method ebayenterprise_order/data::_getOrderCancelReason()
	 * which when return a non empty array the method ebayenterprise_order/data::hasOrderCancelReason()
	 * return the boolean value true, otherwise it return false.
	 * @param mixed
	 * @param bool
	 * @dataProvider providerHasOrderCancelReason
	 */
	public function testHasOrderCancelReason($reasons, $result)
	{
		$helper = $this->getHelperMock('ebayenterprise_order/data', ['_getOrderCancelReason']);
		$helper->expects($this->once())
			->method('_getOrderCancelReason')
			->will($this->returnValue($reasons));
		$this->assertSame($result, $helper->hasOrderCancelReason());
	}

	/**
	 * @return array
	 */
	public function providerGetCancelReasonOptionArray()
	{
		return [
			[['reason_code_001' => 'Wrong Products'], [['value' => '', 'label' => ''], ['value' => 'reason_code_001', 'label' => 'Wrong Products']]],
			[[], [['value' => '', 'label' => '']]],
			[null, [['value' => '', 'label' => '']]],
		];
	}

	/**
	 * Test that helper method ebayenterprise_order/data::getCancelReasonOptionArray()
	 * when invoked will called the helper method ebayenterprise_order/data::_getOrderCancelReason()
	 * which when return a non empty array with key/value pair the method
	 * ebayenterprise_order/data::getCancelReasonOptionArray() will loop through each key/value
	 * and append a new element array with key mapped to the key 'value'  and value mapped to the key
	 * value, along with the first index element which has a default array element with key value and
	 * label mapped an empty string. Otherwise, if the method ebayenterprise_order/data::_getOrderCancelReason()
	 * return an empty array or null only the default array element with empty value for the key value
	 * and label is will be return from the ebayenterprise_order/data::getCancelReasonOptionArray() method.
	 * @param mixed
	 * @param bool
	 * @dataProvider providerGetCancelReasonOptionArray
	 */
	public function testGetCancelReasonOptionArray($reasons, $result)
	{
		$helper = $this->getHelperMock('ebayenterprise_order/data', ['_getOrderCancelReason']);
		$helper->expects($this->once())
			->method('_getOrderCancelReason')
			->will($this->returnValue($reasons));
		$this->assertSame($result, $helper->getCancelReasonOptionArray());
	}

	/**
	 * @return array
	 */
	public function providerGetCancelReasonDescription()
	{
		return [
			[['reason_code_001' => 'Wrong Products'], 'reason_code_001', 'Wrong Products'],
			[[], 'reason_code_001', null],
			[null, 'reason_code_001', null],
		];
	}

	/**
	 * Test that helper method ebayenterprise_order/data::getCancelReasonDescription()
	 * when invoked will be passed in an order reason code if when we call the helper method
	 * ebayenterprise_order/data::_getOrderCancelReason() it return a non empty array
	 * with a key that match the passed in reason code, then the method
	 * ebayenterprise_order/data::getCancelReasonDescription() will return the order cancel
	 * reason description for the passed in reason code. Otherwise it will return null.
	 * @param mixed
	 * @param bool
	 * @dataProvider providerGetCancelReasonDescription
	 */
	public function testGetCancelReasonDescription($reasons, $code, $result)
	{
		$helper = $this->getHelperMock('ebayenterprise_order/data', ['_getOrderCancelReason']);
		$helper->expects($this->once())
			->method('_getOrderCancelReason')
			->will($this->returnValue($reasons));
		$this->assertSame($result, $helper->getCancelReasonDescription($code));
	}
}
