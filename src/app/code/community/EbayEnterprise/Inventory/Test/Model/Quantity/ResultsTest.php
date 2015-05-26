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

class EbayEnterprise_Inventory_Test_Model_Quantity_ResultsTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Create a mock quantity, scipted to return
	 * an expected sku and item id.
	 *
	 * @param string
	 * @param int
	 * @return EbayEnterprise_Inventory_Model_Quantity
	 */
	protected function _mockQuantityResult($sku, $itemId)
	{
		$qty = $this->getModelMockBuilder('ebayenterprise_inventory/quantity')
			->disableOriginalConstructor()
			->setMethods(['getSku', 'getItemId'])
			->getMock();
		$qty->expects($this->any())
			->method('getSku')
			->will($this->returnValue($sku));
		$qty->expects($this->any())
			->method('getItemId')
			->will($this->returnValue($itemId));
		return $qty;
	}

	/**
	 * When getting a quantity result by sku, only
	 * the quantity result with a matching sku should be
	 * returned.
	 */
	public function testGetQuantityBySku()
	{
		$theSku = 'the-sku';
		$expectedQty = $this->_mockQuantityResult($theSku, 3);
		$unexpectedQty = $this->_mockQuantityResult('not-the-sku', 6);
		$results = [$expectedQty, $unexpectedQty];

		$results = Mage::getModel(
			'ebayenterprise_inventory/quantity_results',
			['quantities' => $results, 'expiration_time' => new DateTime, 'sku_quantity_data' => []]
		);

		$this->assertSame(
			$expectedQty,
			$results->getQuantityBySku($theSku),
			'did not recieve expected quantity for sku'
		);
	}

	/**
	 * When getting a quantity result by sku, if no quantity
	 * result with a matching sku is in the set of results,
	 * null should be returned.
	 */
	public function testGetQuantityBySkuNoMatching()
	{
		$theSku = 'the-sku';
		$unexpectedQty = $this->_mockQuantityResult('not-the-sku', 6);
		$results = [$unexpectedQty];

		$results = Mage::getModel(
			'ebayenterprise_inventory/quantity_results',
			['quantities' => $results, 'expiration_time' => new DateTime, 'sku_quantity_data' => []]
		);

		$this->assertSame(
			null,
			$results->getQuantityBySku($theSku),
			'did not recieve expected quantity for sku'
		);
	}

	/**
	 * When getting a quantity result by id, only
	 * the quantity result with a matching item id should
	 * be returned.
	 */
	public function testGetQuantityByItemId()
	{
		$theItemId = 3;
		$expectedQty = $this->_mockQuantityResult('a-sku', $theItemId);
		$unexpectedQty = $this->_mockQuantityResult('no-the-sku', 6);
		$results = [$expectedQty, $unexpectedQty];

		$results = Mage::getModel(
			'ebayenterprise_inventory/quantity_results',
			['quantities' => $results, 'expiration_time' => new DateTime, 'sku_quantity_data' => []]
		);

		$this->assertSame(
			$expectedQty,
			$results->getQuantityByItemId($theItemId),
			'did not recieve expected quantity for item id'
		);
	}

	/**
	 * When getting a quantity result by id, if no quantity
	 * result with a matching item id is in the set of results,
	 * null should be returned.
	 */
	public function testGetQuantityByItemIdNoMatching()
	{
		$theItemId = 3;
		$unexpectedQty = $this->_mockQuantityResult('no-the-sku', 6);
		$results = [$unexpectedQty];

		$results = Mage::getModel(
			'ebayenterprise_inventory/quantity_results',
			['quantities' => $results, 'expiration_time' => new DateTime, 'sku_quantity_data' => []]
		);

		$this->assertSame(
			null,
			$results->getQuantityByItemId($theItemId),
			'did not recieve expected quantity for item id'
		);
	}

	/**
	 * Provide a lifetime and whether a result with that lifetime should have
	 * expired.
	 */
	public function provideExpirationTimes()
	{
		return [
			[new DateTime('tomorrow'), false],
			[new DateTime('yesterday'), true],
		];
	}

	/**
	 * When the expiration time for a result set has passed, the results
	 * should indicate that it has expired.
	 *
	 * @param DateTime
	 * @param bool
	 * @dataProvider provideExpirationTimes
	 */
	public function testCheckExpiration($expirationTime, $isExpired)
	{
		$results = Mage::getModel(
			'ebayenterprise_inventory/quantity_results',
			['quantities' => [], 'expiration_time' => $expirationTime, 'sku_quantity_data' => []]
		);
		$this->assertSame($isExpired, $results->isExpired());
	}

	/**
	 * Provide arrays of sku => quantity data for the original and new sku
	 * quantity data for testing if the results should apply to the new data.
	 *
	 * @return array
	 */
	public function provideSkuQuantityData()
	{
		return [
			[['one' => 1, 'two' => 2], ['one' => 1, 'two' => 2], true],
			[['one' => 1, 'two' => 2], ['two' => 2, 'one' => 1], true],
			[['one' => 1, 'two' => 2], ['one' => 3, 'two' => 2], false],
			[['one' => 1, 'two' => 2], ['two' => 2], false],
			[['one' => 1, 'two' => 2], ['three' => 3], false],
		];
	}
	/**
	 * When checking if results apply to a set of item data, only results
	 * with sku quantity data matching the given data should apply.
	 *
	 * @param array
	 * @param array
	 * @param bool $apply If the results should apply to the given data
	 * @dataProvider provideSkuQuantityData
	 */
	public function testCheckResultsApplyToItems($originalSkuData, $currentSkuData, $apply)
	{
		$results = Mage::getModel(
			'ebayenterprise_inventory/quantity_results',
			['quantities' => [], 'expiration_time' => new DateTime, 'sku_quantity_data' => $originalSkuData]
		);
		$this->assertSame($apply, $results->checkResultsApplyToItems($currentSkuData));
	}
}
