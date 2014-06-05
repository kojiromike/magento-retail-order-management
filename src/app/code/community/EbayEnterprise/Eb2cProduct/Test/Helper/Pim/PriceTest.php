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

class EbayEnterprise_Eb2cProduct_Test_Helper_Pim_PriceTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceEventNumber method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceEventNumber
	 *                given a string attribute value, a string attribute, a mocked Mage_Catalog_Model_Product object and
	 *                a mocked EbayEnterprise_Dom_Document object and expects the following methods to be invoked
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_buildEventNumber given the mocked product object which
	 *                will return a known value and then another called to the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createStringNode given the return value of the mocked method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_buildEventNumber and the mocked EbayEnterprise_Dom_Document
	 *                object which will return a mocked DOMNode object
	 */
	public function testPassPriceEventNumber()
	{
		$attrValue = '';
		$attribute = 'price_event_number';
		$eventNumber = '20120315-20120415';

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createStringNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createStringNode')
			->with($this->identicalTo($eventNumber), $this->identicalTo($domMock))
			->will($this->returnValue($nodeMock));
		$this->replaceByMock('helper', 'eb2cproduct/pim', $pimHelperMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(array('_buildEventNumber'))
			->getMock();
		$priceMock->expects($this->once())
			->method('_buildEventNumber')
			->with($this->identicalTo($productMock))
			->will($this->returnValue($eventNumber));

		$this->assertSame($nodeMock, $priceMock->passPriceEventNumber($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPrice method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPrice
	 *                given a string attribute value, a string attribute, a mocked Mage_Catalog_Model_Product object and
	 *                a mocked EbayEnterprise_Dom_Document object and expects the following methods to be invoked
	 *                Mage_Catalog_Model_Product::getSpecialPrice when the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_hasSpecialPrice is invoked given the mocked
	 *                Mage_Catalog_Model_Product object as parameter and return a know value of true, the
	 *                Mage_Catalog_Model_Product::getSpecialPrice value is then pass as first parameter to the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode and the mocked EbayEnterprise_Dom_Document
	 *                mocked object as second parameter and return a mocked DOMNode object
	 */
	public function testPassPrice()
	{
		$attrValue = '';
		$attribute = 'price';
		$specialPrice = 9.999;
		$specialPriceRound = 9.99;
		$hasSpecialPrice = true;

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getSpecialPrice'))
			->getMock();
		$productMock->expects($this->once())
			->method('getSpecialPrice')
			->will($this->returnValue($specialPrice));

		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($specialPriceRound), $this->identicalTo($domMock))
			->will($this->returnValue($nodeMock));
		$this->replaceByMock('helper', 'eb2cproduct/pim', $pimHelperMock);

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('roundPrice'))
			->getMock();
		$storeMock->expects($this->once())
			->method('roundPrice')
			->with($this->identicalTo($specialPrice))
			->will($this->returnValue($specialPriceRound));
		$this->replaceByMock('model', 'core/store', $storeMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(array('_hasSpecialPrice'))
			->getMock();
		$priceMock->expects($this->once())
			->method('_hasSpecialPrice')
			->with($this->identicalTo($productMock))
			->will($this->returnValue($hasSpecialPrice));

		$this->assertSame($nodeMock, $priceMock->passPrice($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passMsrp method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passMsrp
	 *                given a string attribute value, a string attribute, a mocked Mage_Catalog_Model_Product object and
	 *                a mocked EbayEnterprise_Dom_Document object and expects the following methods to be invoked
	 *                Mage_Catalog_Model_Product::getMsrp and return a known value which is then pass as first parameter
	 *                to the method EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode and the mocked
	 *                EbayEnterprise_Dom_Document mocked object as second parameter and return a mocked DOMNode object
	 */
	public function testPassMsrp()
	{
		$attrValue = '';
		$attribute = 'msrp';
		$msrp = 7.99;

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getMsrp'))
			->getMock();
		$productMock->expects($this->once())
			->method('getMsrp')
			->will($this->returnValue($msrp));

		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($msrp), $this->identicalTo($domMock))
			->will($this->returnValue($nodeMock));
		$this->replaceByMock('helper', 'eb2cproduct/pim', $pimHelperMock);

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('roundPrice'))
			->getMock();
		$storeMock->expects($this->once())
			->method('roundPrice')
			->with($this->identicalTo($msrp))
			->will($this->returnValue($msrp));
		$this->replaceByMock('model', 'core/store', $storeMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($nodeMock, $priceMock->passMsrp($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passAlternatePrice method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passAlternatePrice
	 *                given a string attribute value, a string attribute, a mocked Mage_Catalog_Model_Product object and
	 *                a mocked EbayEnterprise_Dom_Document object and expects the following methods to be invoked
	 *                Mage_Catalog_Model_Product::getSpecialPrice when the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_hasSpecialPrice is invoked given the mocked
	 *                Mage_Catalog_Model_Product object as parameter and return a know value of true, the
	 *                Mage_Catalog_Model_Product::getSpecialPrice value is then pass as first parameter to the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode and the mocked EbayEnterprise_Dom_Document
	 *                mocked object as second parameter and return a mocked DOMNode object
	 */
	public function testPassAlternatePrice()
	{
		$attrValue = '';
		$attribute = 'alternate_price1';
		$specialPrice = 9.99;
		$hasSpecialPrice = true;

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getSpecialPrice'))
			->getMock();
		$productMock->expects($this->once())
			->method('getSpecialPrice')
			->will($this->returnValue($specialPrice));

		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($specialPrice), $this->identicalTo($domMock))
			->will($this->returnValue($nodeMock));
		$this->replaceByMock('helper', 'eb2cproduct/pim', $pimHelperMock);

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('roundPrice'))
			->getMock();
		$storeMock->expects($this->once())
			->method('roundPrice')
			->with($this->identicalTo($specialPrice))
			->will($this->returnValue($specialPrice));
		$this->replaceByMock('model', 'core/store', $storeMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(array('_hasSpecialPrice'))
			->getMock();
		$priceMock->expects($this->once())
			->method('_hasSpecialPrice')
			->with($this->identicalTo($productMock))
			->will($this->returnValue($hasSpecialPrice));

		$this->assertSame($nodeMock, $priceMock->passAlternatePrice($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceDateFrom method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceDateFrom
	 *                given a string attribute value, a string attribute, a mocked Mage_Catalog_Model_Product object and
	 *                a mocked EbayEnterprise_Dom_Document object and expects the following methods to be invoked
	 *                Mage_Catalog_Model_Product::getSpecialFromDate when the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_hasSpecialPrice is invoked given the mocked
	 *                Mage_Catalog_Model_Product object as parameter and return a know value of true, the
	 *                Mage_Catalog_Model_Product::getSpecialFromDate value is then pass as first parameter to the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode and the mocked EbayEnterprise_Dom_Document
	 *                mocked object as second parameter and return a mocked DOMNode object
	 */
	public function testPassPriceDateFrom()
	{
		$attrValue = '';
		$attribute = 'special_from_date';
		$from = '2014-04-01 12:00:00:34';
		$hasSpecialPrice = true;

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getSpecialFromDate'))
			->getMock();
		$productMock->expects($this->once())
			->method('getSpecialFromDate')
			->will($this->returnValue($from));

		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode', 'createDateTime'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($from), $this->identicalTo($domMock))
			->will($this->returnValue($nodeMock));
		$pimHelperMock->expects($this->once())
			->method('createDateTime')
			->with($this->identicalTo($from))
			->will($this->returnValue($from));
		$this->replaceByMock('helper', 'eb2cproduct/pim', $pimHelperMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(array('_hasSpecialPrice'))
			->getMock();
		$priceMock->expects($this->once())
			->method('_hasSpecialPrice')
			->with($this->identicalTo($productMock))
			->will($this->returnValue($hasSpecialPrice));

		$this->assertSame($nodeMock, $priceMock->passPriceDateFrom($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceDateTo method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceDateTo
	 *                given a string attribute value, a string attribute, a mocked Mage_Catalog_Model_Product object and
	 *                a mocked EbayEnterprise_Dom_Document object and expects the following methods to be invoked
	 *                Mage_Catalog_Model_Product::getSpecialToDate when the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_hasSpecialPrice is invoked given the mocked
	 *                Mage_Catalog_Model_Product object as parameter and return a know value of true, the
	 *                Mage_Catalog_Model_Product::getSpecialToDate value is then pass as first parameter to the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode and the mocked EbayEnterprise_Dom_Document
	 *                mocked object as second parameter and return a mocked DOMNode object
	 */
	public function testPassPriceDateTo()
	{
		$attrValue = '';
		$attribute = 'special_to_date';
		$to = '2014-04-15 12:00:00:34';
		$hasSpecialPrice = true;

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getSpecialToDate'))
			->getMock();
		$productMock->expects($this->once())
			->method('getSpecialToDate')
			->will($this->returnValue($to));

		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode', 'createDateTime'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($to), $this->identicalTo($domMock))
			->will($this->returnValue($nodeMock));
		$pimHelperMock->expects($this->once())
			->method('createDateTime')
			->with($this->identicalTo($to))
			->will($this->returnValue($to));
		$this->replaceByMock('helper', 'eb2cproduct/pim', $pimHelperMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(array('_hasSpecialPrice'))
			->getMock();
		$priceMock->expects($this->once())
			->method('_hasSpecialPrice')
			->with($this->identicalTo($productMock))
			->will($this->returnValue($hasSpecialPrice));

		$this->assertSame($nodeMock, $priceMock->passPriceDateTo($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceVatInclusive method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::passPriceVatInclusive
	 *                given a string attribute value, a string attribute, a mocked Mage_Catalog_Model_Product object and
	 *                a mocked EbayEnterprise_Dom_Document object and expects the method
	 *                EbayEnterprise_Eb2cTax_Helper_Data::getVatInclusivePricingFlag will be invoked which
	 *                will return a known value which will be passed as the first parameter to the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim::createTextNode and then the mocked EbayEnterprise_Dom_Document
	 *                object will be pass as second parameter which will return a mocked DOMNode object
	 */
	public function testPassPriceVatInclusive()
	{
		$attrValue = '';
		$attribute = 'price_vat_inclusive';
		$vat = 1;
		$boolMap = array($vat => 'true');

		$helperMock = $this->getHelperMockBuilder('eb2ctax/data')
			->disableOriginalConstructor()
			->setMethods(array('getVatInclusivePricingFlag'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getVatInclusivePricingFlag')
			->will($this->returnValue($vat));
		$this->replaceByMock('helper', 'eb2ctax', $helperMock);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$domMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$nodeMock = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('createTextNode'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('createTextNode')
			->with($this->identicalTo($boolMap[$vat]), $this->identicalTo($domMock))
			->will($this->returnValue($nodeMock));
		$this->replaceByMock('helper', 'eb2cproduct/pim', $pimHelperMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($nodeMock, $priceMock->passPriceVatInclusive($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_getTimeStamp method for the following expectations
	 * Expectation 1: when this test invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_getTimeStamp given
	 *                string of date it will convert the date string into an integer value representing the date
	 *                and then pass it as the scond parameter to the method Mage_Core_Model_Date::gmtDate and a string
	 *                 of date format as the first parameter which turn the method will return known date
	 */
	public function testGetTimeStamp()
	{
		$time = '2014-03-15 12:00:24:33';
		$strToTime = strtotime($time);
		$stamp = '20140315';
		$format = 'Ymd';

		$dateMock = $this->getModelMockBuilder('core/date')
			->disableOriginalConstructor()
			->setMethods(array('gmtDate'))
			->getMock();
		$dateMock->expects($this->once())
			->method('gmtDate')
			->with($this->identicalTo($format), $this->identicalTo($strToTime))
			->will($this->returnValue($stamp));
		$this->replaceByMock('model', 'core/date', $dateMock);

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($stamp, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$priceMock, '_getTimeStamp', array($time)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_hasSpecialPrice method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_hasSpecialPrice
	 *                given mocked Mage_Catalog_Model_Product object in which will return the boolean value of
	 *                the comparison of the method Mage_Catalog_Model_Product::getSpecialPrice value greater than zero
	 */
	public function testHasSpecialPrice()
	{
		$result = true;
		$specialPrice = 10.99;

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getSpecialPrice'))
			->getMock();
		$productMock->expects($this->once())
			->method('getSpecialPrice')
			->will($this->returnValue($specialPrice));

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$priceMock, '_hasSpecialPrice', array($productMock)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_buildEventNumber method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_buildEventNumber
	 *                given mocked Mage_Catalog_Model_Product object in which will called the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_hasSpecialPrice given the mocked product object
	 *                which will then return true, which will allow the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Pim_Price::_getTimeStamp to be called 2 time given the
	 *                value from the methods Mage_Catalog_Model_Product::getSpecialFromDate and getSpecialToDate
	 */
	public function testBuildEventNumber()
	{
		$hasSpecialPrice = true;
		$fromDate = '2014-03-15 12:00:00:34';
		$toDate = '2014-04-17 05:00:12:47';

		$formatFrom = '20140315';
		$formatTo = '2014-04-17';

		$result = $formatFrom . '-' . $formatTo;

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getSpecialFromDate', 'getSpecialToDate'))
			->getMock();
		$productMock->expects($this->once())
			->method('getSpecialFromDate')
			->will($this->returnValue($fromDate));
		$productMock->expects($this->once())
			->method('getSpecialToDate')
			->will($this->returnValue($toDate));

		$priceMock = $this->getHelperMockBuilder('eb2cproduct/pim_price')
			->disableOriginalConstructor()
			->setMethods(array('_hasSpecialPrice', '_getTimeStamp'))
			->getMock();
		$priceMock->expects($this->once())
			->method('_hasSpecialPrice')
			->with($this->identicalTo($productMock))
			->will($this->returnValue($hasSpecialPrice));
		$priceMock->expects($this->exactly(2))
			->method('_getTimeStamp')
			->will($this->returnValueMap(array(
				array($fromDate, $formatFrom),
				array($toDate, $formatTo)
			)));

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$priceMock, '_buildEventNumber', array($productMock)
		));
	}

}
