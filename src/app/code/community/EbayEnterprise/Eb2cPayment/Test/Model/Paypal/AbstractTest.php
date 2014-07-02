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
	 * @test
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
		$abstract = $this->getModelMock('eb2cpayment/paypal_abstract', array('_extractMessages'), true);

		$abstract->expects($this->once())
			->method('_extractMessages')
			->with($this->isType('string'), $this->isInstanceOf('DOMXPath'))
			->will($this->returnValue($errorMessages));

		$helper->expects($this->once())
			->method('__')
			->with($this->identicalTo($translateKey), $this->identicalTo($errorMessages))
			->will($this->returnValue($translatedMessage));

		$this->replaceByMock('helper', 'eb2cpayment', $helper);
		$this->setExpectedException('EbayEnterprise_Eb2cPayment_Model_Paypal_Exception', $translatedMessage);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_blockIfRequestFailed', array(
			$responseCode,
			new DOMXPath($doc)
		));
	}

	/**
	 * messages should be logged
	 * @test
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
	 * @test
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
	 * @test
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
}
