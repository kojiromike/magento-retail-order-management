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

class EbayEnterprise_Eb2cProduct_Test_Helper_Map_Price
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public $price = 12.34;
	public $specialPrice = 15.00;
	public $start = '2010-01-01T00:00:00-00:00';
	public $specialFrom = '2010-01-01';
	public $end = '2020-01-01T00:00:00-00:00';
	public $specialTo = '2020-01-01';
	public $eventNodeList;

	public function setUp()
	{
		parent::setUp();
		$doc = new DOMDocument();
		$doc->loadXML(sprintf('<_>
			<Event>
				<Price>%.2f</Price>
				<AlternatePrice1>%.2f</AlternatePrice1>
				<StartDate>%s</StartDate>
				<EndDate>%s</EndDate>
			</Event>
		</_>', $this->specialPrice, $this->price, $this->start, $this->end));
		$this->eventNodeList = $doc->documentElement->getElementsByTagName('Event');
	}
	/**
	 * Test extracting a price from the `Event` node.
	 */
	public function testExtractPrice()
	{
		$this->assertSame(
			$this->price,
			Mage::helper('eb2cproduct/map_price')->extractPrice($this->eventNodeList)
		);
	}
	/**
	 * Test extracting a price from the `Event` node.
	 */
	public function testExtractSpecialPrice()
	{
		$this->assertSame(
			$this->specialPrice,
			Mage::helper('eb2cproduct/map_price')->extractSpecialPrice($this->eventNodeList)
		);
	}
	/**
	 * Test extracting a price from the `Event` node.
	 */
	public function testExtractPriceEventFromDate()
	{
		$this->assertSame(
			$this->specialFrom,
			Mage::helper('eb2cproduct/map_price')->extractPriceEventFromDate($this->eventNodeList)
		);
	}
	/**
	 * Test extracting a price from the `Event` node.
	 */
	public function testExtractPriceEventToDate()
	{
		$this->assertSame(
			$this->specialTo,
			Mage::helper('eb2cproduct/map_price')->extractPriceEventToDate($this->eventNodeList)
		);
	}
}
