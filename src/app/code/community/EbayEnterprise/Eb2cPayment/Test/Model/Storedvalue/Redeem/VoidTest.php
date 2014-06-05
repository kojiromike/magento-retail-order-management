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

class EbayEnterprise_Eb2cPayment_Test_Model_Storedvalue_Redeem_VoidTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test voiding a card redemption - use the pan, pin, quote id and amount
	 * to build a request message, send it to the service and return the data
	 * extracted from the response message.
	 * @test
	 */
	public function testVoidCardRedemption()
	{
		$pan = '123456789';
		$pin = '4321';
		$quoteId = 3;
		$amt = 50.50;

		$requestMessage = new DOMDocument();
		$responseMessage = '<MockResponse/>';
		$responseData = array();

		$voidRequest = $this->getModelMock(
			'eb2cpayment/storedvalue_redeem_void',
			array('_parseResponse', '_buildRequest', '_makeVoidRequest')
		);
		$voidRequest->expects($this->once())
			->method('_parseResponse')
			->with($this->identicalTo($responseMessage))
			->will($this->returnValue($responseData));
		$voidRequest->expects($this->once())
			->method('_makeVoidRequest')
			->with($this->identicalTo($pan), $this->identicalTo($requestMessage))
			->will($this->returnValue($responseMessage));
		$voidRequest->expects($this->once())
			->method('_buildRequest')
			->with($this->identicalto($pan, $pin, $quoteId, $amt))
			->will($this->returnValue($requestMessage));
		$this->assertSame(
			$responseData,
			$voidRequest->voidCardRedemption($pan, $pin, $quoteId, $amt)
		);
	}
	/**
	 * Test making the void request. Get the endpoint uri using the existing
	 * eb2cpayment/data helper's getSvcUri method. Make sure the API model is
	 * conifigured to use the proper status handler and then make the request
	 * using the given request message, xsd file set in config and the URI from
	 * the helper method.
	 * @test
	 */
	public function testMakeVoidRequest()
	{
		$requestUri = 'https://example.com/endpoint';
		$xsdFile = 'stored_valid_xsd_file.xsd';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$responseMessage = '<MockResponse/>';
		$pan = '123412341234';
		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri', 'getConfigModel'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_redeem_void'), $this->equalTo($pan))
			->will($this->returnValue($requestUri));
		$paymentHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'xsdFileStoredValueVoidRedeem' => $xsdFile
			)));
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
				$this->identicalTo($doc),
				$this->identicalTo($xsdFile),
				$this->identicalTo($requestUri)
			)->will($this->returnValue($responseMessage));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame($responseMessage,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				Mage::getModel('eb2cpayment/storedvalue_redeem_void'),
				'_makeVoidRequest',
				array($pan, $doc)
			)
		);
	}
	/**
	 * Test getRedeemVoid method, where getSvcUri return an empty url
	 * @test
	 */
	public function testGetRedeemVoidWithEmptyUrl()
	{
		$pan = '00000000000000';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$payHelper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$payHelper->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_redeem_void'), $this->equalTo($pan))
			->will($this->returnValue(''));
		$this->replaceByMock('helper', 'eb2cpayment', $payHelper);

		$this->assertSame(
			'',
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				Mage::getModel('eb2cpayment/storedvalue_redeem_void'),
				'_makeVoidRequest',
				array($pan, $doc)
			)
		);
	}
	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemVoidReply)
	{
		$this->assertSame(
			array(
				// If you change the order of this array the test will fail.
				'orderId'                => 1,
				'paymentAccountUniqueId' => '4111111ak4idq1111',
				'responseCode'           => 'Success',
			),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				Mage::getModel('eb2cpayment/storedvalue_redeem_void'),
				'_parseResponse',
				array($storeValueRedeemVoidReply)
			)
		);
	}
	/**
	 * When given an empty response message, should just return an empty array.
	 * @test
	 */
	public function testParseEmptyResponse()
	{
		$this->assertSame(
			array(),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				Mage::getModel('eb2cpayment/storedvalue_redeem_void'),
				'_parseResponse',
				array('')
			)
		);
	}
	/**
	 * Test building a request message for a given quote id, PAN, PIN and amount.
	 * @test
	 */
	public function testBuildStoredValueVoidRequest()
	{
		$pan = '4111111ak4idq1111';
		$pin = '1234';
		$entityId = 1;
		$amount = 50.00;
		$xmlNs = 'http://api.example.com/ns';
		$requestId = 'request-id-1';

		$paymentHelper = $this->getHelperMock(
			'eb2cpayment/data',
			array('getXmlNs', 'getRequestId')
		);
		$paymentHelper->expects($this->any())
			->method('getXmlNs')
			->will($this->returnValue($xmlNs));
		$paymentHelper->expects($this->once())
			->method('getRequestId')
			->with($this->identicalTo($entityId))
			->will($this->returnValue($requestId));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelper);

		$expected = new DOMDocument();
		// Do not preserve whitespace in the XML being compared to the XML the
		// method is generating, otherwise the whitespace used to make the expected
		// XML readable will cause the test to fail.
		$expected->preserveWhiteSpace = false;
		$expected->loadXML(sprintf('<StoredValueRedeemVoidRequest xmlns="%s" requestId="%s">
	<PaymentContext>
		<OrderId>%s</OrderId>
		<PaymentAccountUniqueId isToken="false">%s</PaymentAccountUniqueId>
	</PaymentContext>
	<Pin>%s</Pin>
	<Amount currencyCode="USD">%s</Amount>
</StoredValueRedeemVoidRequest>', $xmlNs, $requestId, $entityId, $pan, $pin, $amount));

		$this->assertSame(
			$expected->C14N(),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				Mage::getModel('eb2cpayment/storedvalue_redeem_void'),
				'_buildRequest',
				array($pan, $pin, $entityId, $amount)
			)->C14N()
		);
	}
}
