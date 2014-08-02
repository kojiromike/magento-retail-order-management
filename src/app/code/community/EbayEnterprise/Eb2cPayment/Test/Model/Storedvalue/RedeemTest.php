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

class EbayEnterprise_Eb2cPayment_Test_Model_Storedvalue_RedeemTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test getRedeem method
	 */
	public function testGetRedeem()
	{
		$pan = '80000000000000';
		$pin = '1234';
		$entityId = 1;
		$amount = 1.0;
		$isVoid = false;
		$operation = 'get_gift_card_redeem';

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			'<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="1">
				<PaymentContext>
					<OrderId>1</OrderId>
					<PaymentAccountUniqueId isToken="false">' . $pan . '</PaymentAccountUniqueId>
				</PaymentContext>
				<Pin>1234</Pin>
				<Amount currencyCode="USD">1.0</Amount>
			</StoredValueRedeemRequest>'
		);

		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri', 'getConfigModel', 'buildRedeemRequest'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo($operation), $this->equalTo($pan))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml'));
		$paymentHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'xsdFileStoredValueRedeem' => 'Payment-Service-StoredValueRedeem-1.0.xsd'
			)));
		$paymentHelperMock->expects($this->once())
			->method('buildRedeemRequest')
			->with(
				$this->equalTo($pan),
				$this->equalTo($pin),
				$this->equalTo($entityId),
				$this->equalTo($amount),
				$this->equalTo($isVoid)
			)
			->will($this->returnValue($doc));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);
		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->setMethods(array('request', 'setStatusHandlerPath'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('setStatusHandlerPath')
			->with($this->equalTo(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('request')
			->with(
				$this->isInstanceOf('EbayEnterprise_Dom_Document'),
				'Payment-Service-StoredValueRedeem-1.0.xsd',
				'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml'
			)->will($this->returnValue(
				'<StoredValueRedeemReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">' . $pan . '</PaymentAccountUniqueId>
					</PaymentContext>
					<ResponseCode>Success</ResponseCode>
					<AmountRedeemed currencyCode="USD">1.00</AmountRedeemed>
					<BalanceAmount currencyCode="USD">1.00</BalanceAmount>
				</StoredValueRedeemReply>'
			));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$testData = array(
			array(
				'expect' => '<StoredValueRedeemReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">' . $pan . '</PaymentAccountUniqueId>
					</PaymentContext>
					<ResponseCode>Success</ResponseCode>
					<AmountRedeemed currencyCode="USD">1.00</AmountRedeemed>
					<BalanceAmount currencyCode="USD">1.00</BalanceAmount>
				</StoredValueRedeemReply>',
				'pan' => $pan,
				'pin' => $pin,
				'entityId' => $entityId,
				'amount' => $amount
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], Mage::getModel('eb2cpayment/storedvalue_redeem')->getRedeem(
				$data['pan'], $data['pin'], $data['entityId'], $data['amount']
			));
		}
	}
	/**
	 * Test getRedeem method, where getSvcUri return an empty url
	 */
	public function testGetRedeemWithEmptyUrl()
	{
		$pan = '00000000000000';
		$pin = '1234';
		$entityId = 1;
		$amount = 1.0;
		$operation = 'get_gift_card_redeem';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			"<StoredValueRedeemRequest xmlns='http://api.gsicommerce.com/schema/checkout/1.0' requestId='1'>
				<PaymentContext>
					<OrderId>$entityId</OrderId>
					<PaymentAccountUniqueId isToken='false'>$pan</PaymentAccountUniqueId>
				</PaymentContext>
				<Pin>$pin</Pin>
				<Amount currencyCode='USD'>$amount</Amount>
			</StoredValueRedeemRequest>"
		);
		$payHelper = $this->getHelperMockBuilder('eb2cpayment/data')
			->setMethods(array('getSvcUri'))
			->getMock();
		$payHelper->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo($operation), $this->equalTo($pan))
			->will($this->returnValue(''));
		$this->replaceByMock('helper', 'eb2cpayment', $payHelper);
		$this->assertSame('', Mage::getModel('eb2cpayment/storedvalue_redeem')->getRedeem($pan, $pin, $entityId, $amount));
	}
	/**
	 * testing parseResponse method
	 *
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemReply)
	{
		$this->assertSame(
			array(
				// If you change the order of the elements in this array the test will fail.
				'orderId'                => 1,
				'paymentAccountUniqueId' => '4111111ak4idq1111',
				'responseCode'           => 'Success',
				'amountRedeemed'         => 50.00,
				'balanceAmount'          => 150.00,
			),
			Mage::getModel('eb2cpayment/storedvalue_redeem')->parseResponse($storeValueRedeemReply)
		);
	}
}
