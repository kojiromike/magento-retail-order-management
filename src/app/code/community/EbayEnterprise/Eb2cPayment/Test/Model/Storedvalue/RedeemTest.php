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
	 * Test making the SVC redeem request
	 */
	public function testMakeRedeemRequest()
	{
		// test data
		$pan = '80000000000000';
		$pin = '1234';
		$amount = 1.0;
		$card = array(
			'i' => 'gca-id',
			'c' => $pan,
			'a' => $amount,
			'ba' => $amount,
			'pan' => $pan,
			'pin' => $pin,
		);
		$orderId = '1234567890';
		$order = Mage::getModel('sales/order', array('increment_id' => $orderId));

		// mock values for the test
		$requestUri = 'http://example.com/store_value_redeem.xml';
		$xsdFileConfigValue = 'mock_xsd_file_config.xsd';
		$requestMessage = '<SVC_Mock_Request/>';
		$responseMessage = '<SVC_Mock_Response/>';
		$requestDoc = Mage::helper('eb2ccore')->getNewDomDocument();
		$requestDoc->loadXML($requestMessage);
		$requestId = 'REQUEST_ID_12345';

		$coreHelperMock = $this->getHelperMock('eb2ccore/data', array('generateRequestId'));
		$coreHelperMock->expects($this->any())
			->method('generateRequestId')
			->will($this->returnValue($requestId));

		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->setMethods(array('getSvcUri', 'getConfigModel', 'buildRedeemRequest', ))
			// disable to prevent config lookups in the constructor, which, as the config
			// is being mocked, will simply blow up - getConfigModel is mocked but not yet
			// scripted to return anything so getConfigModel() returns null in the constructor
			// and then property access causes an error
			->disableOriginalConstructor()
			->getMock();
		// getSvcUri must be given the PAN and a hardcoded operation "key" to properly generate a request uri
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			// First param is a special "key" string used in the helper to
			// associate an "operation" with a service URI. A copy-paste of the
			// value into the test won't really prove anything meaningful so
			// just letting it be anything in the test.
			->with($this->anything(), $this->equalTo($pan))
			->will($this->returnValue($requestUri));
		// stub out the payment config to get a consistent xsd file config value out
		$paymentHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'xsdFileStoredValueRedeem' => $xsdFileConfigValue
			))));
		// to build the proper redeem request, must be given gift card data as well as a flag
		// indicating that the request is not a void request
		$paymentHelperMock->expects($this->once())
			->method('buildRedeemRequest')
			->with(
				$this->equalTo($pan),
				$this->equalTo($pin),
				$this->equalTo($orderId),
				$this->equalTo($amount),
				$this->equalTo($requestId),
				$this->isFalse() // this last flag indicates void request, should be false for redeem request
			)
			->will($this->returnValue($requestDoc));

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->setMethods(array('request', 'setStatusHandlerPath'))
			->getMock();
		// ensure proper handling of the response by the API
		$apiModelMock->expects($this->once())
			->method('setStatusHandlerPath')
			->with($this->equalTo(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH))
			->will($this->returnSelf());
		// make the request with the expected message, xsd file and request uri
		$apiModelMock->expects($this->once())
			->method('request')
			->with(
				$this->identicalTo($requestDoc),
				$this->identicalTo($xsdFileConfigValue),
				$this->identicalTo($requestUri)
			)->will($this->returnValue($responseMessage));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$redeemRequest = Mage::getModel(
			'eb2cpayment/storedvalue_redeem',
			array('order' => $order, 'card' => $card, 'payment_helper' => $paymentHelperMock, 'core_helper' => $coreHelperMock)
		);
		$this->assertSame($redeemRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($redeemRequest, '_makeRedeemRequest'));
		$this->assertSame($responseMessage, EcomDev_Utils_Reflection::getRestrictedPropertyValue($redeemRequest, '_responseMessage'));
		$this->assertSame($requestId, $redeemRequest->getRequestId());
	}
	/**
	 * Test getRedeem method, where getSvcUri return an empty url
	 */
	public function testGetRedeemWithEmptyUrl()
	{
		// test data
		$pan = '00000000000000';
		$pin = '1234';
		$orderId = 1;
		$amount = 1.0;
		$card = array(
			'i' => 'gc_id',
			'c' => $pin,
			'a' => $amount,
			'ba' => $amount,
			'pin' => $pin,
			'pan' => $pan,
		);
		$order = Mage::getModel('sales/order', array('increment_id' => $orderId));

		$payHelper = $this->getHelperMockBuilder('eb2cpayment/data')
			->setMethods(array('getSvcUri'))
			->getMock();
		// stub out the getSvcUri method to return an empty string, indicating a gift card
		// pan that does not fit into any configured SVC bin range
		$payHelper->expects($this->once())
			->method('getSvcUri')
			// First argument is const value the payment helper uses to map an operation
			// to a request uri. Copy-paste of the value in this test won't prove anything
			// meaningful so allowing it to be anything here.
			->with($this->anything(), $this->equalTo($pan))
			->will($this->returnValue(''));

		$redeemRequest = Mage::getModel('eb2cpayment/storedvalue_redeem', array('order' => $order, 'card' => $card, 'payment_helper' => $payHelper));

		$this->assertSame($redeemRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($redeemRequest, '_makeRedeemRequest'));
		$this->assertSame('', EcomDev_Utils_Reflection::getRestrictedPropertyValue($redeemRequest, '_responseMessage'));
	}
	/**
	 * testing parseResponse method
	 *
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemReply)
	{
		$redeem = Mage::getModel(
			'eb2cpayment/storedvalue_redeem',
			array('card' => array('pin' => 12345, 'pan' => 1234, 'ba' => 12.34), 'order' => Mage::getModel('sales/order'))
		);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($redeem, '_responseMessage', $storeValueRedeemReply);
		EcomDev_Utils_Reflection::invokeRestrictedMethod($redeem, '_extractResponse');
		$this->assertSame($redeem->getResponseOrderId(), '1');
		$this->assertSame($redeem->getPaymentAccountUniqueId(), '4111111ak4idq1111');
		$this->assertSame($redeem->getResponseCode(), 'SUCCESS');
		$this->assertSame($redeem->getAmountRedeemed(), '50.00');
		$this->assertSame($redeem->getBalanceAmount(), '150.00');
	}
	/**
	 * Data provider of invalid or missing order constructor params value.
	 * @return array Array of argument arrays
	 */
	public function provideInvalidOrderConstructorParam()
	{
		return array(
			array(array('card' => array('pan' => 123, 'pin' => 5555, 'ba' => 12.34))),
			array(array('order' => 'not an order', 'card' => array('pan' => 123, 'pin' => 5555, 'ba' => 12.34))),
		);
	}
	/**
	 * Test validating the constructor params includes a valid order instance.
	 * @param  array $constructorParams Constructor params to pass to the model
	 * @dataProvider provideInvalidOrderConstructorParam
	 */
	public function testInvalidOrderConstructorParams($constructorParams)
	{
		$this->setExpectedException('Mage_Core_Exception', 'Mage_Sales_Model_Order instance must be provided.');
		Mage::getModel('eb2cpayment/storedvalue_redeem', $constructorParams);
	}
	/**
	 * Data provider of invalid or missing order constructor params value.
	 * @return array Array of argument arrays
	 */
	public function provideInvalidCardConstructorParam()
	{
		$order = Mage::getModel('sales/order');
		return array(
			array(array('order' => $order), "A 'card' must be included in the params."),
			array(array('card' => array(), 'order' => $order), "'card' is missing fields: pin, pan, ba"),
		);
	}
	/**
	 * Test validating the constructor params includes a valid order instance.
	 * @param  array $constructorParams Constructor params to pass to the model
	 * @dataProvider provideInvalidCardConstructorParam
	 */
	public function testInvalidCardConstructorParams($constructorParams, $message)
	{
		$this->setExpectedException('Mage_Core_Exception', $message);
		Mage::getModel('eb2cpayment/storedvalue_redeem', $constructorParams);
	}
}
