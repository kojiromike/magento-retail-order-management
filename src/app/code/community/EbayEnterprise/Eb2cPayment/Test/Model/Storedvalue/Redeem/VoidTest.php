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
	 */
	public function testVoidCardRedemption()
	{
		$pan = '123456789';
		$pin = '4321';
		$quoteId = 3;
		$amt = 50.50;
		$isVoid = true;

		$requestMessage = new DOMDocument();
		$responseMessage = '<MockResponse/>';
		$responseData = array();

		$helperMock = $this->getHelperMock('eb2cpayment/data', array('buildRedeemRequest'));
		$helperMock->expects($this->once())
			->method('buildRedeemRequest')
			->with(
				$this->identicalTo($pan),
				$this->identicalTo($pin),
				$this->identicalTo($quoteId),
				$this->identicalTo($amt),
				$this->identicalTo($isVoid)
			)
			->will($this->returnValue($requestMessage));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$voidRequest = $this->getModelMock(
			'eb2cpayment/storedvalue_redeem_void',
			array('_parseResponse', '_makeVoidRequest')
		);
		$voidRequest->expects($this->once())
			->method('_parseResponse')
			->with($this->identicalTo($responseMessage))
			->will($this->returnValue($responseData));
		$voidRequest->expects($this->once())
			->method('_makeVoidRequest')
			->with($this->identicalTo($pan), $this->identicalTo($requestMessage))
			->will($this->returnValue($responseMessage));

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
	 */
	public function testMakeVoidRequest()
	{
		$requestUri = 'https://example.com/endpoint';
		$xsdFile = 'stored_valid_xsd_file.xsd';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$responseMessage = '<MockResponse/>';
		$pan = '123412341234';
		$operation = 'get_gift_card_redeem_void';
		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri', 'getConfigModel'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo($operation), $this->equalTo($pan))
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
	 */
	public function testGetRedeemVoidWithEmptyUrl()
	{
		$pan = '00000000000000';
		$operation = 'get_gift_card_redeem_void';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$payHelper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$payHelper->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo($operation), $this->equalTo($pan))
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
}
