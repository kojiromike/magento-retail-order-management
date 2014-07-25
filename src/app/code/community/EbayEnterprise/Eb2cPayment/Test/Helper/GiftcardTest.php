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

class EbayEnterprise_Eb2cPayment_Test_Helper_GiftcardTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test that the method EbayEnterprise_Eb2cPayment_Helper_Giftcard::synchStorevalue
	 * when invoked will make a request to rom storevalue api replace the data in the passed
	 * in 'enterprise_giftcardaccount/giftcardaccount' object and save it
	 * if it hasn't been loaded.
	 */
	public function testSynchStorevalue()
	{
		$pan = '8099939000000';
		$pin = '8843';
		$reply = '<_/>';
		$amount = 99.99;
		$parseData = array(
			'paymentAccountUniqueId' => $pan,
			'pin' => $pin,
			'balanceAmount' => $amount,
		);

		$balanceMock = $this->getModelMock('eb2cpayment/storedvalue_balance', array('getBalance', 'parseResponse'));
		$balanceMock->expects($this->once())
			->method('getBalance')
			->with($this->identicalTo($pan), $this->identicalTo($pin))
			->will($this->returnValue($reply));
		$balanceMock->expects($this->once())
			->method('parseResponse')
			->with($this->identicalTo($reply))
			->will($this->returnValue($parseData));
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_balance', $balanceMock);

		$gca = $this->getModelMock('enterprise_giftcardaccount/giftcardaccount', array('addData', 'save'));
		$gca->expects($this->once())
			->method('addData')
			->with($this->identicalTo(array(
				'code' => $pan,
				'eb2c_pan' => $pan,
				'eb2c_pin' => $pin,
				'status' => 1,
				'state' => 1,
				'balance' => $amount,
				'is_redeemable' => 1,
				'website_id' => '0'
			)))
			->will($this->returnSelf());
		$gca->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$giftcard = Mage::helper('eb2cpayment/giftcard');

		$this->assertSame($giftcard, $giftcard->synchStorevalue($pan, $pin, $gca));
	}
}
