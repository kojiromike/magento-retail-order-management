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

class EbayEnterprise_Order_Test_Model_Search_Process_Response_CollectionTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test that the method ebayenterprise_order/search_process_response_collection::sort()
	 * is invoked, and call the PHP usort() function and pass it the class property
	 * ebayenterprise_order/search_process_response_collection::$_items as first parameter
	 * and an array with one element as the ebayenterprise_order/search_process_response_collection
	 * object and another element as the string literal for the method _sortOrdersMostRecentFirst.
	 * Then, the method ebayenterprise_order/search_process_response_collection::_sortOrdersMostRecentFirst()
	 * will be invoked and passed in the Varien_Object parameters in the class property
	 * ebayenterprise_order/search_process_response_collection::$_items. Finally, the method
	 * ebayenterprise_order/search_process_response_collection::sort() will return itself.
	 */
	public function testSearchProcessResponseCollectionSort()
	{
		/** @var Varien_Object */
		$varienObjectA = new Varien_Object();
		/** @var Varien_Object */
		$varienObjectB = new Varien_Object();

		/** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
		$collection = $this->getModelMock('ebayenterprise_order/search_process_response_collection', ['_sortOrdersMostRecentFirst']);
		$collection->expects($this->once())
			->method('_sortOrdersMostRecentFirst')
			->with($this->identicalTo($varienObjectB), $this->identicalTo($varienObjectA))
			->will($this->returnValue(true));

		// Set the class property ebayenterprise_order/search_process_response_collection::$_items
		// to an array of Varien_Object.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collection, '_items', [$varienObjectA, $varienObjectB]);

		$this->assertSame($collection, $collection->sort());
	}

	/**
	 * @return array
	 */
	public function providerSortOrdersMostRecentFirst()
	{
		return [
			['2015-05-14T20:36:30+00:00', '2015-04-14T20:36:30+00:00', false],
			['2015-04-14T20:36:30+00:00', '2015-05-14T20:36:30+00:00', true],
		];
	}

	/**
	 * Test that the method ebayenterprise_order/search_process_response_collection::_sortOrdersMostRecentFirst()
	 * is invoked, and it will be passed in an object of type Varien_Object as parameter 1 and 2. When the
	 * Varien_Object instance of parameter one has an order date greater and the Varien_Object instance of
	 * parameter two, then the method ebayenterprise_order/search_process_response_collection::_sortOrdersMostRecentFirst()
	 * will return boolean false, otherwise it will return true.
	 *
	 * @param string
	 * @param string
	 * @param bool
	 * @dataProvider providerSortOrdersMostRecentFirst
	 */
	public function testSortOrdersMostRecentFirst($orderDateA, $orderDateB, $result)
	{
		/** @var EbayEnterprise_Order_Model_Search_Process_Response_ICollection */
		$collection = Mage::getModel('ebayenterprise_order/search_process_response_collection');

		/** @var Varien_Object */
		$varienObjectA = new Varien_Object(['order_date' => $orderDateA]);
		/** @var Varien_Object */
		$varienObjectB = new Varien_Object(['order_date' => $orderDateB]);

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$collection, '_sortOrdersMostRecentFirst', [$varienObjectA, $varienObjectB]
		));
	}
}
