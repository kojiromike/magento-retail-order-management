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

class EbayEnterprise_Order_Test_Model_Search_Process_Response_MapTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const SUMMARY_CLASS = '\eBayEnterprise\RetailOrderManagement\Payload\Customer\OrderSummary';

	/**
	 * Test that the method ebayenterprise_order/search_process_response_map::extract()
	 * is invoked, and passed in as parameter an instance of type IOrderSummary. Then,
	 * the method ebayenterprise_order/search_process_response_map::_extractData() will called
	 * and passed in the instance of type IOrderSummary. The method
	 * ebayenterprise_order/search_process_response_map::_extractData() will return an array with key/value pairs.
	 * Finally, the method ebayenterprise_order/search_process_response_map::extract() will return
	 * this array of key/value pairs.
	 */
	public function testSearchProcessResponseMapExtract()
	{
		/** @var array */
		$summaryData = [];

		/** @var Mock_IOrderSummary */
		$orderSummary = $this->getMockBuilder(static::SUMMARY_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var Mock_EbayEnterprise_Order_Model_Search_Process_Response_Map */
		$map = $this->getModelMock('ebayenterprise_order/search_process_response_map', ['_extractData']);
		$map->expects($this->once())
			->method('_extractData')
			->with($this->identicalTo($orderSummary))
			->will($this->returnValue($summaryData));

		$this->assertSame($summaryData, $map->extract($orderSummary));
	}

	/**
	 * Test that the method ebayenterprise_order/search_process_response_map::_extractData()
	 * is invoked, and it will loop through the magic config registry eb2ccore/config_registry::$mapSearchResponse.
	 * For each key that has an array with key 'type' that is not equal to disabled, then
	 * Add a key 'parameters' mapped to an array with element of type IOrderSummary and the value
	 * from the key 'getter'. Then, invoked the method eb2ccore/data::invokeCallback() passing
	 * in an array with key/value pairs. When loop has ended the method
	 * ebayenterprise_order/search_process_response_map::_extractData() will simply return
	 * an array with with key/value pairs.
	 */
	public function testSearchProcessResponseCollectionSort()
	{
		/** @var array */
		$mapSearchResponse = [
			'rom_id' => [
				'class' => 'ebayenterprise_order/search_map',
				'type' => 'helper',
				'method' => 'getStringValue',
				'getter' => 'getId'
			],
		];
		/** @var string */
		$callbackReturnValue = 'order-73772877776671622716767';
		/** @var array */
		$summaryData = ['rom_id' => $callbackReturnValue];
		/** @var Mock_IOrderSummary */
		$orderSummary = $this->getMockBuilder(static::SUMMARY_CLASS)
			// Disabling the constructor because it requires the following parameters: IValidatorIterator
			// ISchemaValidator, IPayloadMap, LoggerInterface
			->disableOriginalConstructor()
			->getMock();

		/** @var Mock_EbayEnterprise_Eb2cCore_Helper_Data */
		$coreHelper = $this->getHelperMock('eb2ccore/data', ['invokeCallback']);
		$coreHelper->expects($this->once())
			->method('invokeCallback')
			->with($this->isType('array'))
			->will($this->returnValue($callbackReturnValue));

		$config = $this->buildCoreConfigRegistry(['mapSearchResponse' => $mapSearchResponse]);

		/** @var EbayEnterprise_Order_Model_Search_Process_Response_Map */
		$map = Mage::getModel('ebayenterprise_order/search_process_response_map', [
			// This key is optional
			'config' => $config,
			// This key is optional
			'core_helper' => $coreHelper,
		]);

		$this->assertSame($summaryData, EcomDev_Utils_Reflection::invokeRestrictedMethod($map, '_extractData', [$orderSummary]));
	}
}
