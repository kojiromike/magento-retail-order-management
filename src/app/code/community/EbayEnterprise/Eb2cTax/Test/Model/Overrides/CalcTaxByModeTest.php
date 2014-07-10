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

/**
 * tests the tax calculation class.
 */
class EbayEnterprise_Eb2cTax_Test_Model_Overrides_CalcTaxByModeTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/*
	 * This tests that Merchandise, Gifting and ShipGroup Gifting are grouped as one amount.
	 * Shipping should not be included.
	 */
	public function testGroupingOfTypes()
	{
		$dummyTaxQuote1 = Mage::getModel('eb2ctax/response_quote', array(
			'type'           => EbayEnterprise_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE,
			'calculated_tax' => 1.10,
		));

		$dummyTaxQuote2 = Mage::getModel('eb2ctax/response_quote', array(
			'type'           => EbayEnterprise_Eb2cTax_Overrides_Model_Calculation::GIFTING_TYPE,
			'calculated_tax' => 2.20,
		));
		$dummyTaxQuote3 = Mage::getModel('eb2ctax/response_quote', array(
			'type'           => EbayEnterprise_Eb2cTax_Overrides_Model_Calculation::SHIPGROUP_GIFTING_TYPE,
			'calculated_tax' => 3.30,
		));
		$dummyTaxQuote4 = Mage::getModel('eb2ctax/response_quote', array(
			'type'           => EbayEnterprise_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE,
			'calculated_tax' => 150.00,
		));

		$dummyTaxQuotes = array(
			$dummyTaxQuote1,
			$dummyTaxQuote2,
			$dummyTaxQuote3,
			$dummyTaxQuote4,
		);

		$calculator = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(
				array(
					'_getItemResponse',	// Return an eb2ctax/response_orderitem to get into calculation logic
					'_isForAmountMode',	// False means 'getCalculatedTax' (as opposed to doing a calculation)
					'_extractTax',		// Return an array of taxQuotes to be processed
				)
			)
			->getMock();

		$calculator->expects($this->once())
			->method('_getItemResponse')
			->will($this->returnValue(
					Mage::getModel('eb2ctax/response_orderitem')
			));

		$calculator->expects($this->once())
			->method('_isForAmountMode')
			->will($this->returnValue(false));

		$calculator->expects($this->once())
			->method('_extractTax')
			->will($this->returnValue($dummyTaxQuotes));

		$this->assertSame(6.60, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$calculator,
			'_calcTaxByMode',
			array(
				0, // Amount to calculate tax on - but we don't want that. We want what ROM told us.
				new Varien_Object(),
				EbayEnterprise_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE
				)
			)
		);
	}
}
