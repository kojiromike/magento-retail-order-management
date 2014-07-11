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

class EbayEnterprise_Eb2cProduct_Test_Model_Price_EventTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{

	public function setUp()
	{
		parent::setUp();
		$this->doc = new DOMDocument();
	}

	/**
	 * Test extracting data from a price `Event` node. The price_event model
	 * should take the XMLNode it gets instantiated with and extract the
	 * appropriate price event data from the XML and expose it via the model's
	 * public getters.
	 * @param  string $xml          XML representing the pricing event being processed
	 * @param  string $price        The expected product "price" attribute
	 * @param  string $specialPrice The expected product "special_price" attribute
	 * @param  string $start        The expected product "special_from_date"
	 * @param  string $end          The expected product "special_to_date"
	 * @dataProvider dataProvider
	 */
	public function testExtractPriceEvent($xml, $price, $specialPrice, $start, $end)
	{
		$this->doc->loadXML($xml);

		$priceEvent = Mage::getModel('eb2cproduct/price_event',
			array('event_node' => $this->doc->documentElement->firstChild)
		);
		// price and special price will be passed as strings from the data provider
		// yaml (or null in some cases for special price) - cast them to floats when
		// possible to make proper type comparisons
		$price = (float) $price;
		$specialPrice = !is_null($specialPrice) ? (float) $specialPrice : null;
		$this->assertSame($price, $priceEvent->getPrice());
		$this->assertSame($specialPrice, $priceEvent->getSpecialPrice());
		$this->assertSame($start, $priceEvent->getSpecialFromDate());
		$this->assertSame($end, $priceEvent->getSpecialToDate());
	}

}
