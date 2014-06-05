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

class EbayEnterprise_Eb2cPayment_Test_Model_Resource_PaypalTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * verify the paypal resource model calls the load method correctly.
	 */
	public function testLoadByQuoteId()
	{
		$quoteId = 10;
		$model = $this->getModelMock('eb2cpayment/paypal');
		$testModel = $this->getResourceModelMockBuilder('eb2cpayment/paypal')
			->disableOriginalConstructor()
			->setMethods(array('load'))
			->getMock();
		$testModel->expects($this->once())
			->method('load')
			->with(
				$this->identicalTo($model),
				$this->identicalTo($quoteId),
				$this->identicalTo('quote_id')
			)
			->will($this->returnSelf());
		$this->assertSame(
			$testModel,
			$testModel->loadByQuoteId($model, $quoteId)
		);
	}
}
