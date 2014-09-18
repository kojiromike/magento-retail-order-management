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

class EbayEnterprise_Eb2cPayment_Test_Model_Paypal_Do_AuthorizationTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_authorization;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_authorization = Mage::getModel('eb2cpayment/paypal_do_authorization');
	}

	public function buildQuoteMock()
	{
		$totals = array();
		$totals['grand_total'] = Mage::getModel('sales/quote_address_total', array(
			'code' => 'grand_total', 'value' => 50.00
		));

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getReservedOrderId', 'reserveOrderId', 'getQuoteCurrencyCode', 'getTotals')
		);
		$quoteMock->expects($this->any())
			->method('getReservedOrderId')
			->will($this->returnValue(1234567));
		$quoteMock->expects($this->any())
			->method('reserveOrderId')
			->will($this->returnSelf());
		$quoteMock->expects($this->any())
			->method('getTotals')
			->will($this->returnValue($totals));
		$quoteMock->expects($this->any())
			->method('getQuoteCurrencyCode')
			->will($this->returnValue('USD'));

		return $quoteMock;
	}

	public function providerDoAuthorization()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}
	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/AuthorizationTest/fixtures/PayPalDoAuthorizationReply.xml', true))
		);
	}
	/**
	 * testing parseResponse method
	 *
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalDoAuthorizationReply)
	{
		$this->assertInstanceOf(
			'Varien_Object',
			$this->_authorization->parseResponse($payPalDoAuthorizationReply)
		);
	}
	public function testDoAuthorization()
	{
		$config = $this->buildCoreConfigRegistry(array('xsdFilePaypalDoAuth' => 'xsdfile'));
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$quote = Mage::getModel('sales/quote');

		$api = $this->getModelMock('eb2ccore/api', array('request', 'setStatusHandlerPath'));
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
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'getOperationUri'))
			->getMock();
		$helper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($config));
		$helper->expects($this->once())
			->method('getOperationUri')
			->with($this->identicalTo('get_paypal_do_authorization'))
			->will($this->returnValue('/uri/'));
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$auth = $this->getModelMock('eb2cpayment/paypal_do_authorization', array('buildPayPalDoAuthorizationRequest'));
		$auth->expects($this->once())
			->method('buildPayPalDoAuthorizationRequest')
			->with($this->identicalto($quote))
			->will($this->returnValue($doc));
		$this->assertSame('responseText', $auth->doAuthorization($quote));
	}
}
