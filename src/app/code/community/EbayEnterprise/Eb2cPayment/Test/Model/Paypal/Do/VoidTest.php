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

class EbayEnterprise_Eb2cPayment_Test_Model_Paypal_Do_VoidTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_void;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_void = Mage::getModel('eb2cpayment/paypal_do_void');
	}

	public function buildQuoteMock()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getQuoteCurrencyCode', 'getBaseGrandTotal')
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1234567)
			);
		$quoteMock->expects($this->any())
			->method('getBaseGrandTotal')
			->will($this->returnValue(50.00)
			);
		$quoteMock->expects($this->any())
			->method('getQuoteCurrencyCode')
			->will($this->returnValue('USD')
			);

		return $quoteMock;
	}
	public function providerDoVoid()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}
	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/VoidTest/fixtures/PayPalDoVoidReply.xml', true))
		);
	}
	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalDoVoidReply)
	{
		$this->assertInstanceOf(
			'Varien_Object',
			$this->_void->parseResponse($payPalDoVoidReply)
		);
	}
	public function testDoVoid()
	{
		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->getMock();
		$api = $this->getModelMock('eb2ccore/api', array('request', 'setStatusHandlerPath'));
		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'getOperationUri'))
			->getMock();
		$testModel = $this->getModelMock('eb2cpayment/paypal_do_void', array('buildPayPalDoVoidRequest'));
		$config = $this->buildCoreConfigRegistry(array('xsdFilePaypalVoidAuth' => 'xsdfile'));
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();

		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($config));
		$helper->expects($this->once())
			->method('getOperationUri')
			->with($this->identicalTo('get_paypal_do_void'))
			->will($this->returnValue('/uri/'));

		$testModel->expects($this->once())
			->method('buildPayPalDoVoidRequest')
			->with($this->identicalto($quote))
			->will($this->returnValue($doc));
		$api->expects($this->once())
			->method('setStatusHandlerPath')
			->with($this->identicalto(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH))
			->will($this->returnSelf());
		$api->expects($this->once())
			->method('request')
			->with(
				$this->identicalto($doc),
				$this->identicalTo('xsdfile'),
				$this->identicalTo('/uri/')
			)
			->will($this->returnValue('responseText'));

		$this->assertSame('responseText', $testModel->doVoid($quote));
	}
}
