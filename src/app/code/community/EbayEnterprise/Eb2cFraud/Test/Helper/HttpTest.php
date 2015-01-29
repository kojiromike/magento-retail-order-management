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

class EbayEnterprise_Eb2cFraud_Test_Helper_HttpTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_helper;
	protected $_jsModuleName;
	/** @var Mage_Core_Model_Cookie stub */
	protected $_cookieStub;
	protected $_coreHelperStub;
	protected $_requestStub;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_jsModuleName = EbayEnterprise_Eb2cFraud_Helper_Http::JSC_JS_PATH;

		$this->_requestStub = $this->getMock('Mage_Core_Controller_Request_Http', ['getServer', 'getCookie', 'getPost']);
		$this->_requestStub->expects($this->any())
			->method('getServer')
			->will($this->returnValue('arequestvalue'));
		$this->_requestStub->expects($this->any())
			->method('getCookie')
			->will($this->returnValue(['foo' => 'bar', 'foo1' => 'bar1']));
		$this->_requestStub->expects($this->any())
			->method('getPost')
			->will($this->returnValueMap([
				['eb2cszyvl', '', 'random_field_name'],
				['random_field_name', '', 'javascript_data'],
			]));

		$this->_cookieStub = $this->getModelMock('core/cookie', ['_getRequest', 'get']);
		$this->_cookieStub->expects($this->any())
			->method('_getRequest')
			->will($this->returnValue($this->_requestStub));
	}

	/**
	 * Get back sensible URL
	 */
	public function testGetJscUrl()
	{
		$url = Mage::helper('eb2cfraud/http')->getJscUrl();
		$this->assertStringEndsWith($this->_jsModuleName, $url);
	}

	/**
	 * verify javascript data is returned
	 */
	public function testGetJavaScriptFraudData()
	{
		$helper = Mage::helper('eb2cfraud/http');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($helper, [
			'_request' => $this->_requestStub,
		]);
		$this->assertSame('javascript_data', $helper->getJavaScriptFraudData());
	}

	/**
	 * return a set of cookie arrays
	 * @return array
	 */
	public function provideCookieArray()
	{
		return [
			[['foo' => 'bar'], 'foo=bar'],
			[['foo' => 'bar', 'foo1' => 'bar1'], 'foo=bar;foo1=bar1'],
		];
	}
	/**
	 * verify the cookie array is converted to a string
	 * @dataProvider provideCookieArray
	 */
	public function testGetCookiesString(array $arr, $expected)
	{
		$helper = Mage::helper('eb2cfraud/http');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($helper, [
			'_request' => $this->_requestStub,
			'_cookie' => $this->_cookieStub,
		]);
		$this->_cookieStub->expects($this->any())
			->method('get')
			->will($this->returnValue($arr));
		$this->assertSame($expected, $helper->getCookiesString());
	}
}
