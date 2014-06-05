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

class EbayEnterprise_Eb2cOrder_Test_Model_Custom_Attribute_OrderTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test that EbayEnterprise_Eb2cOrder_Model_Custom_Attribute::extractData
	 * will return an array of data when invoke by this test
	 * @test
	 */
	public function testExtractData()
	{
		$incrementId = '00000000833';
		$grandTotal = '299.83';
		$configPath = EbayEnterprise_Eb2cOrder_Model_Custom_Attribute_Order::MAPPING_PATH;
		$data = array(
			'increment_id' => $incrementId,
			'grand_total' => $grandTotal
		);

		$order = Mage::getModel('sales/order', $data);

		$mappings = array(
			'increment_id' => array(
				'type' => 'helper',
				'class' => 'eb2corder/map',
				'method' => 'getAttributeValue'
			),
			'grand_total' => array(
				'type' => 'helper',
				'class' => 'eb2corder/map',
				'method' => 'getAttributeValue'
			),
		);

		$configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
		$configRegistryMock->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo($configPath))
			->will($this->returnValue($mappings));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

		$this->assertSame($data, Mage::getModel('eb2corder/custom_attribute_order')->extractData($order));
	}

}
