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

class EbayEnterprise_Eb2cPayment_Test_Model_Paypal_AbstractTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test _savePaymentData method
	 */
	public function testSavePaymentData()
	{
		$quoteId = 51;
		$transId = '12354666233';
		$checkoutObject = new Varien_Object(array(
			'some_field' => $transId,
		));
		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getEntityId'))
			->getMock();
		$quote->expects($this->once())
			->method('getEntityId')
			->will($this->returnValue($quoteId));
		$paypal = $this->getModelMockBuilder('eb2cpayment/paypal')
			->disableOriginalConstructor()
			->setMethods(array('loadByQuoteId', 'save'))
			->getMock();
		$paypal->expects($this->once())
			->method('loadByQuoteId')
			->with($this->equalTo($quoteId))
			->will($this->returnSelf());
		$paypal->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypal);
		$abstract = new EbayEnterprise_Eb2cPayment_Test_Model_Paypal_AbstractTest_Stub();
		EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_savePaymentData', array(
			$checkoutObject,
			$quote
		));
		$this->assertSame($quoteId, $paypal->getQuoteId());
		$this->assertSame($transId, $paypal->getEb2cPaypalSomeField());
	}

	/**
	 * an exception should be thrown if the ResponseCode field is not 'SUCCESS'
	 */
	public function testBlockIfRequestFailed()
	{
		$responseCode = 'FAILURE';
		$translatedMessage = 'this is the translated error message';
		$errorMessage1 = 'errorcode1:paypal error message 1';
		$errorMessage2 = 'errorcode2:paypal error message 2';
		$translateKey = EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract::PAYPAL_REQUEST_FAILED_TRANSLATE_KEY;
		$errorMessages = "{$errorMessage1} {$errorMessage2}";

		$helper = $this->getHelperMock('eb2cpayment/data', array('__'));
		$helper->expects($this->once())
			->method('__')
			->with($this->identicalTo($translateKey), $this->identicalTo($errorMessages))
			->will($this->returnValue($translatedMessage));
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', array('_extractMessages'), true);
		$abstract->expects($this->once())
			->method('_extractMessages')
			->with($this->isType('string'), $this->isInstanceOf('DOMXPath'))
			->will($this->returnValue($errorMessages));

		$this->setExpectedException('EbayEnterprise_Eb2cPayment_Model_Paypal_Exception', $translatedMessage);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_blockIfRequestFailed', array(
			$responseCode,
			new DOMXPath($doc)
		));
	}

	/**
	 * messages should be logged
	 */
	public function testBlockIfRequestFailedOkWithWarnings()
	{
		$responseCode = EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract::SUCCESSWITHWARNINGS;
		$logErrorMessages = 'code1:errormessage1 code2:errormessage2';

		$helper = $this->getHelperMock('ebayenterprise_magelog/data', array('logWarn'));
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', array('_extractMessages'), true);

		$helper->expects($this->never())
			->method('__');
		$abstract->expects($this->once())
			->method('_extractMessages')
			->will($this->returnValue($logErrorMessages));

		$this->replaceByMock('helper', 'ebayenterprise_magelog', $helper);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_blockIfRequestFailed', array(
			$responseCode,
			new DOMXPath($doc)
		));
	}

	/**
	 * no exception will be thrown
	 */
	public function testBlockIfRequestFailedWithSuccess()
	{
		$responseCode = EbayEnterprise_Eb2cPayment_Model_Paypal_Abstract::SUCCESS;
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', array('_extractMessages'), true);

		$abstract->expects($this->never())
			->method('_extractMessages');

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$obj = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_blockIfRequestFailed', array(
			$responseCode,
			new DOMXPath($doc)
		));
		$this->assertNull($obj);
	}

	/**
	 * extract message strings from the response
	 */
	public function testExtractMessages()
	{
		$errorMessage1 = 'errorcode1:paypal error message 1';
		$errorMessage2 = 'errorcode2:paypal error message 2';
		$errorMessages = "{$errorMessage1} {$errorMessage2}";
		$errorPath = '//ErrorMessage';

		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', array('none'), true);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			"<t><ErrorMessage>{$errorMessage1}</ErrorMessage><ErrorMessage>{$errorMessage2}</ErrorMessage></t>"
		);
		$result = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_extractMessages', array(
			$errorPath,
			new DOMXPath($doc)
		));
		$this->assertSame($errorMessages, $result);
	}
	/**
	 * Test that the line item totals match the expected line item totals.
	 * Asserts that there are the same number of totals and if there are more
	 * than 0 totals, that the totals in each list match.
	 * @param  array $expectedTotals Key/value pairs of totals data
	 * @param  EbayEnterprise_Eb2cPayment_Model_Paypal_Line_Total[] $actualTotals
	 */
	public function assertLineTotals(array $expectedTotals, array $actualTotals)
	{
		$this->assertSame(count($actualTotals), count($expectedTotals), 'got unexpected number of line totals');
		// where there are lines, there will only be one and it should match the expected data
		foreach($expectedTotals as $idx => $expectedTotal) {
			$actualTotal = $actualTotals[$idx];
			$this->assertSame($expectedTotal['name'], $actualTotal->getName());
			$this->assertSame($expectedTotal['price'], $actualTotal->getFormattedPrice('%.2F'));
			$this->assertSame($expectedTotal['qty'], $actualTotal->getQty());
		}
	}
	/**
	 * Test building a line items data from quote gift wrapping
	 * @param  array $quoteData Quote data
	 * @dataProvider dataProvider
	 */
	public function testCalculateQuoteGiftWrapLines(array $quoteData)
	{
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', null, true);
		$quote = Mage::getModel('sales/quote', $quoteData);
		$quoteLines = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_calculateQuoteGiftWrapLines', array($quote));
		$expectedLines = $this->expected('quote-' . $quoteData['id'])->getLinesData();
		$this->assertLineTotals($expectedLines, $quoteLines);
	}
	/**
	 * Test calculating item totals for a quote item
	 * @param array $itemData
	 * @dataProvider dataProvider
	 */
	public function testCalculateItemLines(array $itemData)
	{
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', null, true);
		$quoteItem = Mage::getModel('sales/quote_item', $itemData);
		$expectedLines = $this->expected('item-' . $itemData['id'])->getLinesData();
		$itemLines = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_calculateItemLines', array($quoteItem));

		$this->assertLineTotals($expectedLines, $itemLines);
	}
	/**
	 * Test calculating the rounding adjustment line total based on a quote
	 * and a set of pre-calculated line totals.
	 * @param array $quoteData  Quote data
	 * @param array $totals previously calculated totals
	 * @dataProvider dataProvider
	 */
	public function testCalculateRoundingAdjustmentLine(array $quoteData, array $totals)
	{
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', null, true);
		$quote = Mage::getModel('sales/quote', $quoteData);
		// wrap totals data in line total objects
		$lineTotals = array_map(
			function ($item) { return Mage::getModel('eb2cpayment/paypal_line_total', $item); },
			$totals
		);
		$expectedLines = $this->expected('quote-' . $quoteData['id'])->getLinesData();
		$adjustmentLines = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_calculateRoundingAdjustmentLines', array($quote, $lineTotals));

		$this->assertLineTotals($expectedLines, $adjustmentLines);
	}
	/**
	 * Test building out a full list of line item totals to be included in the
	 * request.
	 * @param array $quoteData Data to populate the quote with
	 * @param array $itemsData array of arrays of quote item data
	 * @dataProvider dataProvider
	 */
	public function testCalculateLineItemTotals(array $quoteData, array $itemsData)
	{
		$items = new Varien_Data_Collection();
		foreach ($itemsData as $itemData) {
			$items->addItem(Mage::getModel('sales/quote_item', $itemData));
		}
		$quoteData['items_collection'] = $items;
		$quote = Mage::getModel('sales/quote', $quoteData);

		$expectedLines = $this->expected('quote-' . $quoteData['id'])->getTotals();
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', null, true);
		$totalLines = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_calculateLineItemTotals', array($quote));

		$this->assertLineTotals($expectedLines, $totalLines);
	}
	/**
	 * Test the method 'eb2cpayment/paypal_set_express_checkout::_calculateUnitAmount' passed in a known
	 * 'sales/quote_item' class instance, as parameter, then expects the proper ‘UnitAmount’ calculation value to be returned.
	 * @param array $itemData
	 * @param float $expected
	 * @dataProvider dataProvider
	 */
	public function testCalculateUnitAmount(array $itemData, $expected)
	{
		$item = Mage::getModel('sales/quote_item', $itemData);
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', null, true);
		$actual = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_calculateUnitAmount', array($item));
		// Spyc.php (used by EcomDev) only "supports" YAML 1.0 which doesn't really
		// support floats so need to ensure the actual value is tested against an
		// actual float value
		$this->assertSame((float) $expected, $actual);
	}
	/**
	 * Test the method 'eb2cpayment/paypal_set_express_checkout::_calculateLineItemsTotal' passed in a known 'sales/quote'
	 * class instance as the  first parameter and an array of totals as second parameter, then expects the proper LineItemsTotal
	 * calculated value to be returned.
	 * @param array $quoteData
	 * @param float $expected
	 * @dataProvider dataProvider
	 */
	public function testCalculateQuoteSubtotal(array $quoteData, $expected)
	{
		// This is a hack because yaml is converting the data from float to string
		$expected = (float) $expected;
		$quote = Mage::getModel('sales/quote', $quoteData);
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', null, true);
		$actual = EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_calculateQuoteSubtotal', array($quote));
		// Spyc.php (used by EcomDev) only "supports" YAML 1.0 which doesn't really
		// support floats so need to ensure the actual value is tested against an
		// actual float value
		$this->assertSame((float) $expected, $actual);
	}
}
