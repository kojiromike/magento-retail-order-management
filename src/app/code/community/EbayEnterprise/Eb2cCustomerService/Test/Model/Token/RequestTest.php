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


class EbayEnterprise_Eb2cCustomerService_Test_Model_Token_RequestTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test making a request for a token. The token should be set on the request
	 * instance as a "token" magic data property.
	 * @test
	 */
	public function testMakeRequestSuccess()
	{
		$responseMessage = '<MockTokenResponse/>';
		$xsdName = 'TokenXsd.xml';
		$requestUri = 'http://example.com/mock/endpoint.xml';
		$token = 'abc-123';

		$cfg = $this->buildCoreConfigRegistry(
			array('xsdFileTokenValidation' => $xsdName)
		);
		$csrHelper = $this->getHelperMock('eb2ccsr/data', array('getConfigModel'));
		$this->replaceByMock('helper', 'eb2ccsr', $csrHelper);
		$requestMessage = $this->getMock('EbayEnterprise_Dom_Document');
		$api = $this->getModelMock('eb2ccore/api', array('request', 'setStatusHandlerPath'));
		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$request = $this->getModelMock(
			'eb2ccsr/token_request',
			array('_buildRequest', 'getToken', '_getApiUri')
		);

		$csrHelper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($cfg));
		$request->expects($this->once())
			->method('_buildRequest')
			->will($this->returnValue($requestMessage));
		$request->expects($this->once())
			->method('getToken')
			->will($this->returnValue($token));
		$request->expects($this->once())
			->method('_getApiUri')
			->will($this->returnValue($requestUri));
		$api->expects($this->once())
			->method('setStatusHandlerPath')
			->with($this->identicalTo('eb2ccore/customer_service/api/status_handlers'))
			->will($this->returnSelf());
		$api->expects($this->once())
			->method('request')
			->with(
				$this->identicalTo($requestMessage),
				$this->identicalTo($xsdName),
				$this->identicalTo($requestUri)
			)
			->will($this->returnValue($responseMessage));

		$this->assertSame(
			$responseMessage,
			$request->makeRequest()
		);
	}
	/**
	 * Test building the DOM message to be sent to the token service.
	 * @test
	 */
	public function testBuildRequest()
	{
		$xmlNs = 'http://schema.gspt.net/token/1.0';
		$token = 'abc-123';
		$expected = new DOMDocument();
		$expected->loadXML(sprintf(
			'<TokenValidateRequest xmlns="%s"><Token>%s</Token></TokenValidateRequest>',
			$xmlNs, $token
		));

		$cfg = $this->buildCoreConfigRegistry(array('apiXmlNs' => $xmlNs));
		$helper = $this->getHelperMock('eb2ccsr/data', array('getConfigModel'));
		$this->replaceByMock('helper', 'eb2ccsr', $helper);
		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($cfg));

		$request = Mage::getModel('eb2ccsr/token_request', array('token' => 'abc-123'));
		$this->assertSame(
			$expected->C14N(),
			EcomDev_Utils_Reflection::invokeRestrictedMethod($request, '_buildRequest')->C14N()
		);
	}
	/**
	 * When a token hasn't been set on the request model, don't make a request,
	 * simply return an empty response.
	 * @test
	 */
	public function testNoRequestForNoToken()
	{
		$api = $this->getModelMock('eb2ccore/api', array('request'));
		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$api->expects($this->never())
			->method('request');
		$request = Mage::getModel('eb2ccsr/token_request');
		$this->assertSame('', $request->makeRequest());
	}
	/**
	 * Test the special API URI building needed for this service. Should include
	 * the domain, major version and minor version from config as well as the
	 * service and operation set as consts on the class.
	 * @test
	 */
	public function testGetApiUri()
	{
		$cfg = $this->buildCoreConfigRegistry(array(
			'apiHostname' => 'example.com',
			'apiMajorVersion' => '1',
			'apiMinorVersion' => '0',
		));

		$helperMock = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($cfg));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$this->assertSame(
			'https://example.com/v1.0/token/validate.xml',
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				Mage::getModel('eb2ccsr/token_request'),
				'_getApiUri'
			)
		);
	}
}
