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

class EbayEnterprise_Eb2cTax_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function setUp()
	{
		parent::setUp();
		// make sure there's a fresh instance of the tax helper for each test
		Mage::unregister('_helper/eb2ctax');
	}
	/**
	 * Mock out the core config registry used to retrieve config values in the helper.
	 * $config arg will be used as the returnValueMap for the config_registry's
	 * magic __get method.
	 * @param array $config
	 * @return Mock_Eb2cCore_Model_Config_Registry
	 */
	protected function _mockConfig($config)
	{
		$mock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get', 'addConfigModel', 'setStore'))
			->getMock();
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($config));
		$mock->expects($this->any())
			->method('addConfigModel')
			->will($this->returnSelf());
		// ensure the config model's "store" gets set before retrieving the value
		$mock->expects($this->once())
			->method('setStore')
			->with($this->equalTo(null))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
		return $mock;
	}
	/**
	 * Test the retrieval of the tax caluculation sequence config value. Expecting true
	 * @dataProvider dataProvider
	 */
	public function testGetApplyTaxAfterDiscount($configValue)
	{
		$this->_mockConfig(array(
			array('taxApplyAfterDiscount', $configValue),
		));
		$val = Mage::helper('eb2ctax')->getApplyTaxAfterDiscount();
		$this->assertSame($configValue, $val);
	}
	/**
	 */
	public function testNamespaceUri()
	{
		$this->_mockConfig(array(
			array('apiNamespace', 'http://api.gsicommerce.com/schema/checkout/1.0'),
		));
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			Mage::helper('eb2ctax')->getNamespaceUri()
		);
	}
	/**
	 * @loadFixture sendRequestConfig.yaml
	 */
	public function testSendRequest()
	{
		$responseMessage = '<foo>something</foo>';
		$requestDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$request = $this->getModelMock('eb2ctax/request', array('getDocument'));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue($requestDocument));

		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2ctax/response', $response);

		$apiModelMock = $this->getModelMock('eb2ccore/api', array('request'));
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->returnValue($responseMessage));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);
		$this->assertSame(
			$response,
			Mage::helper('eb2ctax')->sendRequest($request)
		);
	}
	/**
	 * @loadFixture sendRequestConfig.yaml
	 */
	public function testSendRequestWithExceptionThrown()
	{
		$requestDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$request = $this->getModelMock('eb2ctax/request', array('getDocument'));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue($requestDocument));
		$apiModelMock = $this->getModelMock('eb2ccore/api', array('addData', 'request'));
		$apiModelMock->expects($this->any())
			->method('addData')
			->with($this->identicalTo(array(
				'uri' => 'https://api.example.com/vM.m/stores/store_id/taxes/quote.xml',
				'xsd' => 'TaxDutyFee-QuoteRequest-1.0.xsd'
			)))
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception('this is an exception thrown by the api')));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);
		$this->setExpectedException('Exception', 'this is an exception thrown by the api');
		Mage::helper('eb2ctax')->sendRequest($request);
	}
	/**
	 */
	public function testGetVatInclusivePricingFlag()
	{
		$this->_mockConfig(array(
			array('taxVatInclusivePricing', false),
		));
		$val = Mage::helper('eb2ctax')->getVatInclusivePricingFlag();
		$this->assertFalse($val);
	}
	/**
	 */
	public function testGetVatInclusivePricingFlagEnabled()
	{
		$this->_mockConfig(array(
			array('taxVatInclusivePricing', true),
		));
		$val = Mage::helper('eb2ctax')->getVatInclusivePricingFlag();
		$this->assertTrue($val);
	}
	/**
	 */
	public function testTaxDutyRateCode()
	{
		$code = 'eb2c-duty-amount';
		$this->_mockConfig(array(
			array('taxDutyRateCode', $code),
		));
		$val = Mage::helper('eb2ctax')->taxDutyAmountRateCode();
		$this->assertSame($code, $val);
	}
	public function provideIsRequestForAddressRequired()
	{
		$quote = Mage::getModel('sales/quote');
		$addressWithItems = $this->getModelMock('sales/quote_address', array('getAllItems', 'getQuote'));
		$addressNoItems = $this->getModelMock('sales/quote_address', array('getAllItems', 'getQuote'));

		$addressWithItems->expects($this->any())->method('getQuote')->will($this->returnValue($quote));
		$addressWithItems
			->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array(Mage::getModel('sales/quote_item'))));
		$addressNoItems->expects($this->any())->method('getQuote')->will($this->returnValue($quote));
		$addressNoItems
			->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array()));

		return array(
			array(true, false, $addressWithItems, true),
			array(true, false, $addressNoItems, false),
			array(true, true, $addressWithItems, false),
			array(false, false, $addressWithItems, false),
		);
	}
	/**
	 * Test checking if a tax request is necessary for a given quote. Should only
	 * be required when the session flag indicates it is, no previous errors have been
	 * encountered when making tax requests and the address has items.
	 * @param  bool                        $sessionFlag     State of the session tax flag
	 * @param  bool                        $requestFailFlag State of previous failures flag
	 * @param  Mage_Sales_Model_Quote_Address $address         Address object the request would be for
	 * @param  bool                        $isRequired      Is it required
	 * @dataProvider provideIsRequestForAddressRequired
	 */
	public function testIsRequestForAddressRequired($sessionFlag, $requestFailFlag, $address, $isRequired)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('isTaxUpdateRequired', 'getHaveTaxRequestsFailed', 'updateWithQuote'))
			->getMock();
		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$session->expects($this->any())->method('isTaxUpdateRequired')->will($this->returnValue($sessionFlag));
		$session->expects($this->any())->method('getHaveTaxRequestsFailed')->will($this->returnValue($requestFailFlag));
		$session->expects($this->once())
			->method('updateWithQuote')
			->with($this->identicalTo($address->getQuote()))
			->will($this->returnSelf());

		$this->assertSame($isRequired, Mage::helper('eb2ctax')->isRequestForAddressRequired($address));

	}
	/**
	 * Data provider for the cleanupSessionFlags test. Provides whether or not
	 * tax collections have encountered any errors. If so, the flag should not be
	 * reset, forcing tax requests to be attempted at the next possible chance.
	 * @return array Args array
	 */
	public function provideCleanupSessionFlags()
	{
		return array(array(true), array(false));
	}
	/**
	 * verify the tax request flag is unset from the session.
	 * @dataProvider provideCleanupSessionFlags
	 */
	public function testCleanupSessionFlags($hasFailed)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('getHaveTaxRequestsFailed', 'resetTaxUpdateRequired', 'unsHaveTaxRequestsFailed'))
			->getMock();
		$this->replaceByMock('singleton', 'eb2ccore/session', $session);

		$session->expects($this->once())
			->method('getHaveTaxRequestsFailed')
			->will($this->returnValue($hasFailed));
		$session->expects($this->once())->method('unsHaveTaxRequestsFailed')->will($this->returnSelf());
		if ($hasFailed) {
			$session->expects($this->never())->method('resetTaxUpdateRequired');
		} else {
			$session->expects($this->once())->method('resetTaxUpdateRequired')->will($this->returnSelf());
		}

		$helper = Mage::helper('eb2ctax');
		$this->assertSame($helper, $helper->cleanupSessionFlags());
	}
}
