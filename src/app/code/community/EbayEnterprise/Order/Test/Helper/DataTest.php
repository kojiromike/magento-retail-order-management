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
}
