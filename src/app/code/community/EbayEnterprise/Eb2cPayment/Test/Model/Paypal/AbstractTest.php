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
		EcomDev_Utils_Reflection::invokeRestrictedMethod($abstract, '_savePaymentData', array($checkoutObject, $quote));
		$this->assertSame($quoteId, $paypal->getQuoteId());
		$this->assertSame($transId, $paypal->getEb2cPaypalSomeField());
	}
}
